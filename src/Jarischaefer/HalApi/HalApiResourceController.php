<?php namespace Jarischaefer\HalApi;

use Config;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Input;
use Jarischaefer\HalApi\Exceptions\BadPostRequestException;
use Jarischaefer\HalApi\Exceptions\BadPutRequestException;
use Jarischaefer\HalApi\Exceptions\DatabaseConflictException;
use Jarischaefer\HalApi\Exceptions\DatabaseSaveException;
use Jarischaefer\HalApi\Routing\RouteHelper;
use Schema;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class HalApiResourceController
 * @package Jarischaefer\hal-api
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
	const PAGINATION_CURRENT_PAGE = 'current_page';
	/**
	 * Query parameter name used for pagination's item count per page.
	 */
	const PAGINATION_PER_PAGE = 'per_page';
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
	 * @var
	 */
	protected $currentPage;
	/**
	 * Holds the number of entries per page for pagination purposes.
	 *
	 * @var
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

	public final function __construct()
	{
		parent::__construct();

		$this->transformer = $this->getTransformer();
		$this->model = $this->getModel();

		$this->defaultPerPage = (int)Config::get(self::CONFIG_PAGINATION_DEFAULT_PER_PAGE);

		if ($this->defaultPerPage < 1) {
			$this->defaultPerPage = self::PAGINATION_DEFAULT_ITEMS_PER_PAGE;
		}

		$this->preparePagination();
		$this->boot();
	}

	/**
	 * Children may override this method.
	 */
	protected function boot()
	{
	}

	/**
	 * Initializes currentPage and perPage variables based on user input.
	 */
	private function preparePagination()
	{
		$this->currentPage = (int)Input::get(self::PAGINATION_CURRENT_PAGE, 1);
		$this->perPage = (int)Input::get(self::PAGINATION_PER_PAGE, $this->defaultPerPage);

		if (!is_numeric($this->currentPage)) {
			$this->currentPage = 1;
		}

		if (!is_numeric($this->perPage)) {
			$this->perPage = $this->defaultPerPage;
		}
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
	 * Embeds data from a paginator instance inside the API response. Pagination metadata indicating number of pages,
	 * totals, ... will be automatically added as well. Furthermore, links to the first, next, previous and last
	 * pages will be added if applicable.
	 *
	 * @param LengthAwarePaginator $paginator
	 * @return HalApiContract
	 * @throws Exception
	 */
	protected function paginate(LengthAwarePaginator $paginator)
	{
		$route = \Route::current();
		$routeParameters = \Route::current()->parameters();
		$self = HalLink::make($route, $routeParameters, null, true);
		$parent = HalLink::make(RouteHelper::parent($route), $routeParameters);
		$response = new HalApiElement($self, $parent);
		$response->embedFromArray([
			static::getRelation(RouteHelper::SHOW) => $this->transformer->collection($paginator->items()),
		]);

		$response->meta('pagination', [
			'total' => $paginator->total(),
			'count' => $paginator->count(),
			'per_page' => $paginator->perPage(),
			'current_page' => $paginator->currentPage(),
			'pages' => $paginator->lastPage(),
		]);

		$response->link('first', HalLink::make($route, $routeParameters, 'current_page=1', true));

		if ($paginator->currentPage() > 1) {
			$response->link('prev', HalLink::make($route, $routeParameters, 'current_page=' . ($paginator->currentPage() - 1), true));
		}

		if ($paginator->currentPage() < $paginator->lastPage()) {
			$response->link('next', HalLink::make($route, $routeParameters, 'current_page=' . ($paginator->currentPage() + 1), true));
		}

		$response->link('last', HalLink::make($route, $routeParameters, 'current_page=' . $paginator->lastPage(), true));

		return $response;
	}

	/**
	 * Returns a paginated API response containing n models where n equals either the default number of models per page
	 * or the number specified by the user. The models are embedded into the response.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function index()
	{
		/* @var Model $model */
		$model = $this->model;
		$paginator = $model::paginate($this->perPage);

		return $this->paginate($paginator)->build();
	}

	/**
	 * Returns an API response containing the data of the specified model.
	 *
	 * @param $model
	 * @return array
	 */
	public function show($model)
	{
		return $this->transformer->item($model)->build();
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

		return response($this->show($model), Response::HTTP_CREATED);
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

		switch (\Request::getMethod()) {
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

				return $existed ? $this->show($model) : response($this->show($model), Response::HTTP_CREATED);
			case Request::METHOD_PATCH:
				try {
					$model->update($this->body->getArray());
					$model->syncOriginal();
				} catch (Exception $e) {
					throw new DatabaseSaveException('Model could not be updated.', 0, $e);
				}

				return $this->show($model);
			default:
				return response('', Response::HTTP_METHOD_NOT_ALLOWED);
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
			throw new DatabaseConflictException('Model could not be deleted: ' . $model->{$model->getKeyName()});
		}

		return response('', Response::HTTP_NO_CONTENT);
	}

}
