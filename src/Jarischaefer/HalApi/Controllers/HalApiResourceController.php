<?php namespace Jarischaefer\HalApi\Controllers;

use Exception;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Jarischaefer\HalApi\Exceptions\BadPostRequestException;
use Jarischaefer\HalApi\Exceptions\BadPutRequestException;
use Jarischaefer\HalApi\Exceptions\DatabaseConflictException;
use Jarischaefer\HalApi\Exceptions\DatabaseSaveException;
use Jarischaefer\HalApi\Representations\HalApiPaginatedRepresentation;
use Jarischaefer\HalApi\Helpers\RouteHelper;
use Jarischaefer\HalApi\Transformers\HalApiTransformer;
use Jarischaefer\HalApi\Transformers\HalApiTransformerContract;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class HalApiResourceController
 * @package Jarischaefer\HalApi\Controllers
 */
abstract class HalApiResourceController extends HalApiController implements HalApiResourceControllerContract
{

	/**
	 * Query parameter name used for pagination's current page.
	 */
	const PAGINATION_URI_PAGE = 'page';
	/**
	 * Query parameter name used for pagination's item count per page.
	 */
	const PAGINATION_URI_PER_PAGE = 'per_page';
	/**
	 * Default number of items per pagination page. Used as a fallback if the value
	 * provided via configuration is invalid.
	 */
	const PAGINATION_DEFAULT_ITEMS_PER_PAGE = 10;

	/**
	 * The model's transformer.
	 *
	 * @var HalApiTransformerContract
	 */
	protected $transformer;
	/**
	 * @var Builder
	 */
	protected $schemaBuilder;
	/**
	 * The model's namespace + class name (e.g. App\Job::class).
	 *
	 * @var string
	 */
	protected $model;
	/**
	 * Holds the current page for pagination purposes.
	 *
	 * @var int
	 */
	protected $page;
	/**
	 * Holds the number of entries per page for pagination purposes.
	 *
	 * @var int
	 */
	protected $perPage;

	/**
	 * @param HalApiControllerParameters $parameters
	 * @param HalApiTransformerContract $transformer
	 * @param Builder $schemaBuilder
	 */
	public function __construct(HalApiControllerParameters $parameters, HalApiTransformerContract $transformer, Builder $schemaBuilder)
	{
		parent::__construct($parameters);

		if ($this->app->runningInConsole() && !$this->app->runningUnitTests()) {
			return;
		}

		$this->transformer = $transformer;
		$this->schemaBuilder = $schemaBuilder;
		$this->model = static::getModel();

		if (!is_subclass_of($this->model, Model::class)) {
			throw new RuntimeException('Model must be child of ' . Model::class);
		}

		$this->preparePagination();
		$this->boot();
	}

	/**
	 * @inheritdoc
	 */
	public static function getModelBindingCallback()
	{
		return function ($value) {
			switch (\Request::getMethod()) {
				case Request::METHOD_GET:
					throw new NotFoundHttpException;
				case Request::METHOD_POST:
					throw new NotFoundHttpException;
				case Request::METHOD_PUT:
					return $value;
				case Request::METHOD_PATCH:
					throw new NotFoundHttpException;
				case Request::METHOD_DELETE:
					throw new NotFoundHttpException;
				default:
					return null;
			}
		};
	}

	/**
	 * @inheritdoc
	 */
	public function getTransformer()
	{
		return $this->transformer;
	}

	/**
	 * Initializes page and perPage variables based on user input.
	 */
	private function preparePagination()
	{
		$this->page = (int)$this->request->get(self::PAGINATION_URI_PAGE, 1);
		$this->perPage = (int)$this->request->get(self::PAGINATION_URI_PER_PAGE, self::PAGINATION_DEFAULT_ITEMS_PER_PAGE);

		if (!is_numeric($this->page) && $this->page <= 0) {
			$this->page = 1;
		}

		if (!is_numeric($this->perPage) || $this->perPage < 1) {
			$this->perPage = self::PAGINATION_DEFAULT_ITEMS_PER_PAGE;
		}
	}

	/**
	 * Helps if one does not wish to override the constructor and consequently inherit all its default parameters.
	 */
	protected function boot()
	{
		// do not put anything here, children should not have to call this method
	}

	/**
	 * Embeds data from a paginator instance inside the API response. Pagination metadata indicating number of pages,
	 * totals, ... will be automatically added as well. Furthermore, links to the first, next, previous and last
	 * pages will be added if applicable.
	 *
	 * @param Paginator $paginator
	 * @return HalApiPaginatedRepresentation
	 */
	protected function paginate(Paginator $paginator)
	{
		return $this->representationFactory->paginated($this->self, $this->parent, $paginator, $this->transformer, static::getRelation(RouteHelper::SHOW));
	}

	/**
	 * @inheritdoc
	 */
	public function index()
	{
		/** @var Model $model */
		$model = $this->model;
		$paginator = $model::paginate($this->perPage);

		return $this->responseFactory->json($this->paginate($paginator)->build());
	}

	/**
	 * @inheritdoc
	 */
	public function show($model)
	{
		/** @var Model $model */
		return $this->responseFactory->json($this->transformer->item($model)->build());
	}

	/**
	 * POST and PUT requests must contain all attributes.
	 * This method returns all fillable attributes which are missing.
	 *
	 * @param Model $model
	 * @return array
	 */
	protected function getMissingUpdateAttributes(Model $model)
	{
		$keys = array_keys($this->body->getArray());
		$columnNames = $this->schemaBuilder->getColumnListing($model->getTable());
		$attributes = [];

		foreach ($columnNames as $column) {
			if ($model->isFillable($column) && !in_array($column, $keys)) {
				$attributes[] = $column;
			}
		}

		return $attributes;
	}

	/**
	 * @inheritdoc
	 */
	public function store()
	{
		/** @var Model $model */
		$model = new $this->model;
		$missingAttributes = $this->getMissingUpdateAttributes($model);

		if (!empty($missingAttributes)) {
			throw new BadPostRequestException('POST requests must contain all attributes. Failed for: ' . join(',', $missingAttributes));
		}

		try {
			$model->fill($this->body->getArray())->save();
		} catch (Exception $e) {
			throw new DatabaseSaveException('Model could not be created.', 0, $e);
		}

		return $this->show($model)->setStatusCode(Response::HTTP_CREATED);
	}

	/**
	 * @inheritdoc
	 */
	public function update($model)
	{
		/** @var Model $model */
		if (!($model instanceof Model)) {
			$id = $model;
			$model = new $this->model;
			$model->{$model->getKeyName()} = $id;
		}

		switch ($this->request->getMethod()) {
			case Request::METHOD_PUT:
				$missingAttributes = $this->getMissingUpdateAttributes($model);

				if (!empty($missingAttributes)) {
					throw new BadPutRequestException('PUT requests must contain all attributes. Failed for: ' . join(',', $missingAttributes));
				}

				$existed = $model->exists;

				try {
					$model->fill($this->body->getArray())->save();
				} catch (Exception $e) {
					throw new DatabaseSaveException('Model could not be saved.', 0, $e);
				}

				return $existed ? $this->show($model) : $this->show($model)->setStatusCode(Response::HTTP_CREATED);
			case Request::METHOD_PATCH:
				try {
					$model->fill($this->body->getArray())->save();
				} catch (Exception $e) {
					throw new DatabaseSaveException('Model could not be updated.', 0, $e);
				}

				return $this->show($model);
			default:
				return $this->responseFactory->make('', Response::HTTP_METHOD_NOT_ALLOWED);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function destroy($model)
	{
		try {
			/** @var Model $model */
			$model->delete();
		} catch (Exception $e) {
			throw new DatabaseConflictException('Model could not be deleted: ' . $model->getKey());
		}

		return $this->responseFactory->json('', Response::HTTP_NO_CONTENT);
	}

}
