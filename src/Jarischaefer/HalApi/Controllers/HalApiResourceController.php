<?php namespace Jarischaefer\HalApi\Controllers;

use App;
use Config;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Input;
use Jarischaefer\HalApi\Exceptions\BadPostRequestException;
use Jarischaefer\HalApi\Exceptions\BadPutRequestException;
use Jarischaefer\HalApi\Exceptions\DatabaseConflictException;
use Jarischaefer\HalApi\Exceptions\DatabaseSaveException;
use Jarischaefer\HalApi\Representations\HalApiRepresentation;
use Jarischaefer\HalApi\Representations\RepresentationFactory;
use Jarischaefer\HalApi\Helpers\RouteHelper;
use Jarischaefer\HalApi\Routing\LinkFactory;
use Jarischaefer\HalApi\Transformers\HalApiTransformer;
use Jarischaefer\HalApi\Transformers\TransformerFactory;
use RuntimeException;
use Schema;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class HalApiResourceController
 * @package Jarischaefer\HalApi\Controllers
 */
abstract class HalApiResourceController extends HalApiController
{

	/**
	 * Configuration key for default pagination size (number of items per page).
	 */
	const CONFIG_PAGINATION_DEFAULT_PER_PAGE = 'pagination.default.per_page';
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
	 * @var int
	 */
	private $defaultPerPage;
	/**
	 * @var TransformerFactory
	 */
	protected $transformerFactory;
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
	 * Returns an instance of a transformer to be used for all transformations in this controller.
	 *
	 * @return HalApiTransformer
	 */
	abstract protected function getTransformer();

	/**
	 * The full class name for the controller's model.
	 *
	 * @return string
	 */
	abstract protected function getModel();

	/**
	 * @param Application $application
	 * @param Request $request
	 * @param LinkFactory $linkFactory
	 * @param RepresentationFactory $representationFactory
	 * @param RouteHelper $routeHelper
	 * @param TransformerFactory $transformerFactory
	 * @param ResponseFactory $responseFactory
	 */
	public function __construct(Application $application, Request $request, LinkFactory $linkFactory, RepresentationFactory $representationFactory, RouteHelper $routeHelper, TransformerFactory $transformerFactory, ResponseFactory $responseFactory)
	{
		parent::__construct($application, $request, $linkFactory, $representationFactory, $routeHelper, $responseFactory);

		$this->transformerFactory = $transformerFactory;
		$this->boot();

		if (App::runningInConsole() && !App::runningUnitTests()) {
			return;
		}

		$this->transformer = $this->getTransformer();
		$this->model = (string)$this->getModel();

		if (!is_subclass_of($this->transformer, HalApiTransformer::class)) {
			throw new RuntimeException('Transformer must be child of ' . HalApiTransformer::class);
		}

		if (!is_subclass_of($this->model, Model::class)) {
			throw new RuntimeException('Model must be child of ' . Model::class);
		}

		$this->defaultPerPage = (int)Config::get(self::CONFIG_PAGINATION_DEFAULT_PER_PAGE);

		if ($this->defaultPerPage < 1) {
			$this->defaultPerPage = self::PAGINATION_DEFAULT_ITEMS_PER_PAGE;
		}

		$this->preparePagination();
	}

	/**
	 * Default callback for route model bindings (typically registered in the RouteServiceProvider).
	 * A GET route to /users/{users} needs a binding so the model is automatically passed to the controller's
	 * method. In this example, we're showing a single user via the show method. The show method could look like this:
	 *
	 * public function show(User $user)
	 * {
	 *        // do fancy stuff with user model
	 * }
	 *
	 * The PUT request is a special case though. It can handle both creating a new record and updating an existing one.
	 * Therefore we cannot simply return 404 if the model does not exist. Instead, the PUT request throws no
	 * exception.
	 *
	 * If you do not typehint your method (take a look at the update method in this class), the variable passed
	 * will be null. Otherwise an instance of your model with the ->exists property set to false is passed.
	 *
	 * public function update($user = null)
	 * {
	 *        var_dump($user) // null if not found in database
	 * }
	 *
	 * public function update(User $user)
	 * {
	 *        var_dump($user->exists) // false if not found in database
	 * }
	 *
	 * The latter is preferred for specific implementations where you know the model's type. Of course, we have no
	 * knowledge of the implementing class's model (since we have no generics in PHP).
	 *
	 * @return callable
	 */
	public static function getModelBindingCallback()
	{
		return function () {
			$method = \Request::getMethod();

			switch ($method) {
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
	 * Initializes page and perPage variables based on user input.
	 */
	private function preparePagination()
	{
		$this->page = (int)Input::get(self::PAGINATION_URI_PAGE, 1);
		$this->perPage = (int)Input::get(self::PAGINATION_URI_PER_PAGE, $this->defaultPerPage);

		if (!is_numeric($this->page)) {
			$this->page = 1;
		}

		if (!is_numeric($this->perPage)) {
			$this->perPage = $this->defaultPerPage;
		}
	}

	/**
	 * Executed as an early step in the constructor.
	 * Helps if one does not wish to override the constructor and consequently inherit all its default parameters.
	 */
	protected function boot()
	{
		// do not put anything here, children should not call this method
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
		$routeParameters = $this->self->getParameters();
		$queryString = self::PAGINATION_URI_PER_PAGE . '=' . $this->perPage . '&' . self::PAGINATION_URI_PAGE . '=';

		$response = $this->representationFactory->create($this->self, $this->parent)
			->embedFromArray([
				static::getRelation(RouteHelper::SHOW) => $this->transformer->collection($paginator->items()),
			])
			->meta('pagination', [
				'page' => $paginator->currentPage(),
				'per_page' => $paginator->perPage(),
				'count' => $paginator->count(),
				'total' => $paginator->total(),
				'pages' => $paginator->lastPage() ?: 1,
			])
			->link('first', $this->linkFactory->create($route, $routeParameters, $queryString . '1'));

		if ($paginator->currentPage() > 1) {
			$prev = $this->linkFactory->create($route, $routeParameters, $queryString . ($paginator->currentPage() - 1));
			$response->link('prev', $prev);
		}

		if ($paginator->currentPage() < $paginator->lastPage()) {
			$next = $this->linkFactory->create($route, $routeParameters, $queryString . ($paginator->currentPage() + 1));
			$response->link('next', $next);
		}

		$response->link('last', $this->linkFactory->create($route, $routeParameters, $queryString . ($paginator->lastPage() ?: 1)));

		return $response;
	}

	/**
	 * Returns a paginated API response containing n models where n equals either the default number of models per page
	 * or the number specified by the user. The models are embedded into the response.
	 *
	 * @return Response
	 * @throws Exception
	 */
	public function index()
	{
		/* @var Model $model */
		$model = $this->model;
		$paginator = $model::paginate($this->perPage);

		return $this->responseFactory->json($this->paginate($paginator)->build());
	}

	/**
	 * Returns an API response containing the data of the specified model.
	 *
	 * @param $model
	 * @return Response
	 */
	public function show($model)
	{
		/** @var Model $model */
		return $this->responseFactory->json($this->transformer->item($model)->build());
	}

	/**
	 * Takes attributes from the JSON request body and stores them inside a new instance of the controller's model.
	 * This method also ensures that all the model's fillable (see the guarded variable inside the model class)
	 * attributes are present inside the JSON request body. Make sure you use this method for POST requests only.
	 *
	 * @return Response
	 * @throws BadPostRequestException
	 * @throws DatabaseSaveException
	 */
	public function store()
	{
		/* @var Model $model */
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
	 * Handles PUT and PATCH requests trying to create or update a model. Parameters are taken from the JSON request
	 * body. PUT requests must contain all fillable attributes.
	 *
	 * @param null $model
	 * @return array
	 * @throws BadPutRequestException
	 * @throws DatabaseSaveException
	 */
	public function update($model = null)
	{
		/* @var Model $model */
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
	 * @param $model
	 * @return Response
	 * @throws DatabaseConflictException
	 */
	public function destroy($model)
	{
		try {
			/* @var Model $model */
			$model->delete();
		} catch (Exception $e) {
			throw new DatabaseConflictException('Model could not be deleted: ' . $model->getKey());
		}

		return $this->responseFactory->json('', Response::HTTP_NO_CONTENT);
	}

}
