<?php namespace Jarischaefer\HalApi\Controllers;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Jarischaefer\HalApi\Exceptions\BadPostRequestException;
use Jarischaefer\HalApi\Exceptions\BadPutRequestException;
use Jarischaefer\HalApi\Exceptions\DatabaseConflictException;
use Jarischaefer\HalApi\Exceptions\DatabaseSaveException;
use Jarischaefer\HalApi\Representations\HalApiRepresentation;
use Jarischaefer\HalApi\Representations\RepresentationFactory;
use Jarischaefer\HalApi\Helpers\RouteHelper;
use Jarischaefer\HalApi\Routing\LinkFactory;
use Jarischaefer\HalApi\Transformers\HalApiTransformer;
use Jarischaefer\HalApi\Transformers\HalApiTransformerContract;
use Jarischaefer\HalApi\Transformers\TransformerFactory;
use RuntimeException;
use Schema;
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
	 * @var HalApiTransformer
	 */
	protected $transformer;
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
	 * @param HalApiTransformer $transformer
	 */
	public function __construct(HalApiControllerParameters $parameters, HalApiTransformer $transformer)
	{
		parent::__construct($parameters);

		if ($this->app->runningInConsole() && !$this->app->runningUnitTests()) {
			return;
		}

		$this->transformer = $transformer;
		$this->model = static::getModel();

		if (!is_subclass_of($this->transformer, HalApiTransformerContract::class)) {
			throw new RuntimeException('Transformer must be child of ' . HalApiTransformerContract::class);
		}

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
		return function () {
			switch (\Request::getMethod()) {
				case Request::METHOD_GET:
					throw new NotFoundHttpException;
				case Request::METHOD_POST:
					throw new NotFoundHttpException;
				case Request::METHOD_PUT:
					return null;
				case Request::METHOD_PATCH:
					throw new NotFoundHttpException;
				case Request::METHOD_DELETE:
					throw new NotFoundHttpException;
			}

			return null;
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

		if (!is_numeric($this->page)) {
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
	 * @param LengthAwarePaginator $paginator
	 * @return HalApiRepresentation
	 * @throws Exception
	 */
	protected function paginate(LengthAwarePaginator $paginator)
	{
		$route = $this->self->getRoute();
		$routeParameters = array_merge($this->self->getParameters(), ['per_page' => $this->perPage]);
		$items = $paginator->items();

		$response = $this->representationFactory->create($this->self, $this->parent)
			->embedFromArray([
				static::getRelation(RouteHelper::SHOW) => $this->transformer->collection($items),
			])
			->meta('pagination', [
				'page' => $paginator->currentPage(),
				'per_page' => $paginator->perPage(),
				'count' => count($items),
				'total' => $paginator->total(),
				'pages' => $paginator->lastPage() ?: 1,
			])
			->link('first', $this->linkFactory->create($route, array_merge($routeParameters, ['page' => 1])));

		if ($paginator->currentPage() > 1) {
			$prev = $this->linkFactory->create($route, array_merge($routeParameters, ['page' => $paginator->currentPage() - 1]));
			$response->link('prev', $prev);
		}

		if ($paginator->currentPage() < $paginator->lastPage()) {
			$next = $this->linkFactory->create($route, array_merge($routeParameters, ['page' => $paginator->currentPage() + 1]));
			$response->link('next', $next);
		}

		$response->link('last', $this->linkFactory->create($route, array_merge($routeParameters, ['page' => $paginator->lastPage() ?: 1])));

		return $response;
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
	 * @inheritdoc
	 */
	public function store()
	{
		/** @var Model $model */
		$model = new $this->model;
		$keys = array_keys($this->body->getArray());
		$columnNames = Schema::getColumnListing($model->getTable());

		foreach ($columnNames as $column) {
			if (!$model->isFillable($column)) {
				continue; // only check columns that can actually be filled into the database
			}

			if (!in_array($column, $keys)) {
				throw new BadPostRequestException('POST requests must contain all attributes. Failed for: ' . $column);
			}
		}

		try {
			$model->setRawAttributes($this->body->getArray());
			$model->save();
		} catch (Exception $e) {
			throw new DatabaseSaveException('Model could not be created.', 0, $e);
		}

		return $this->show($model)->setStatusCode(Response::HTTP_CREATED);
	}

	/**
	 * @inheritdoc
	 */
	public function update($model = null)
	{
		/** @var Model $model */
		if ($model == null) {
			$model = new $this->model;
		}

		switch ($this->request->getMethod()) {
			case Request::METHOD_PUT:
				$existed = $model->exists;
				$keys = array_keys($this->body->getArray());
				$columnNames = Schema::getColumnListing($model->getTable());

				foreach ($columnNames as $column) {
					if (!$model->isFillable($column)) {
						continue; // only check columns that can actually be filled into the database
					}

					if (!in_array($column, $keys)) {
						throw new BadPutRequestException('PUT requests must contain all attributes. Failed for: ' . $column);
					}
				}

				try {
					if ($model->exists) {
						$model->update($this->body->getArray());
						$model->syncOriginal();
					} else {
						$model->setRawAttributes($this->body->getArray(), true);
						$model->save();
					}
				} catch (Exception $e) {
					throw new DatabaseSaveException('Model could not be saved.', 0, $e);
				}

				return $existed ? $this->show($model) : $this->show($model)->setStatusCode(Response::HTTP_CREATED);
			case Request::METHOD_PATCH:
				try {
					$model->update($this->body->getArray());
					$model->syncOriginal();
				} catch (Exception $e) {
					throw new DatabaseSaveException('Model could not be updated.', 0, $e);
				}

				return $this->show($model);
			default:
				return $this->responseFactory->json('', Response::HTTP_METHOD_NOT_ALLOWED);
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
