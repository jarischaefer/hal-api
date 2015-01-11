<?php namespace Jarischaefer\HalApi;

use Config;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Input;
use Jarischaefer\HalApi\Exceptions\BadPostRequestException;
use Jarischaefer\HalApi\Exceptions\BadPutRequestException;
use Jarischaefer\HalApi\Exceptions\DatabaseConflictException;
use Jarischaefer\HalApi\Routing\RouteHelper;
use Jarischaefer\HalApi\Transformers\HalApiTransformer;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class HalApiResourceController
 * @package Jarischaefer\hal-api
 */
class HalApiResourceController extends HalApiController
{

	/**
	 * Query parameter name used for pagination's current page.
	 */
	const PAGINATION_CURRENT_PAGE = 'current_page';
	/**
	 * Query parameter name used for pagination's item count per page.
	 */
	const PAGINATION_PER_PAGE = 'per_page';

	/**
	 * @var int
	 */
	private $defaultPerPage;
	/**
	 * Should be initialized in the boot() method of the child class.
	 * Provides an instance of the underlying model's transformer class.
	 *
	 * @var HalApiTransformer
	 */
	protected $transformer;
	/**
	 * Should be initialized in the boot() method of the child class.
	 * Provides the full path to the underlying model class (e.g. App\Job).
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
	 * Boots the controller and calls necessary initialization methods.
	 */
	protected function boot()
	{
		parent::boot();

		$this->defaultPerPage = (int)Config::get('pagination.default.per_page');

		if ($this->defaultPerPage < 1) {
			$this->defaultPerPage = 5;
		}
		
		$this->preparePagination();
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
	 * Embeds data from a paginator instance inside the API response. Pagination metadata indicating number of pages,
	 * totals, ... will be automatically added as well. Furthermore, links to the first, next, previous and last
	 * pages will be added if applicable.
	 *
	 * @param LengthAwarePaginator $paginator
	 * @param null $controller
	 * @param HalApiTransformer $transformer
	 * @return HalApiContract
	 * @throws Exception
	 */
	protected function paginate(LengthAwarePaginator $paginator, $controller = null, HalApiTransformer $transformer = null)
	{
		/* @var HalApiResourceController $controller */
		if ($controller == null) {
			$controller = get_called_class(); // use self if no specific class was passed
		} else if (!is_subclass_of($controller, __CLASS__)) {
			throw new Exception('Non-default controller must extend ' . __CLASS__);
		}

		if ($transformer == null) {
			$transformer = $this->transformer;
		}

		$route = \Route::current();
		$resource = new Collection($paginator->items(), $transformer);
		$this->api->embedCollection($controller::getRelation(RouteHelper::SHOW), $this->manager, $resource);

		$this->api->meta('pagination', [
			'total'			=> $paginator->total(),
			'count'			=> $paginator->count(),
			'per_page'		=> $paginator->perPage(),
			'current_page'	=> $paginator->currentPage(),
			'pages'			=> $paginator->lastPage(),
		]);

		$this->api->link('first', HalLink::make($route, \Route::current()->parameters(), 'current_page=1', true));

		if ($paginator->currentPage() > 1) {
			$this->api->link('prev', HalLink::make($route, \Route::current()->parameters(), 'current_page=' . ($paginator->currentPage() - 1), true));
		}

		if ($paginator->currentPage() < $paginator->lastItem()) {
			$this->api->link('next', HalLink::make($route, \Route::current()->parameters(), 'current_page=' . ($paginator->currentPage() + 1), true));
		}

		$this->api->link('last', HalLink::make($route, \Route::current()->parameters(), 'current_page=' . $paginator->lastPage(), true));

		return $this->api;
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
	public function show($model = null)
	{
		if (!($model instanceof Model) || !$model->exists) {
			throw new ModelNotFoundException;
		}

		$resource = new Item($model, $this->transformer);

		return $this->api->item($this->manager, $resource)->build();
	}

	/**
	 * Takes attributes from the JSON request body and stores them inside a new instance of the controller's model.
	 * This method also ensures that all the model's fillable (see the guarded variable inside the model class)
	 * attributes are present inside the JSON request body. Make sure you use this method for POST requests only.
	 *
	 * @return Response
	 * @throws BadPostRequestException
	 */
	public function store()
	{
		/* @var Model $model */
		$model = new $this->model;
		$keys = array_keys($this->json->getArray());
		$columnNames = \Schema::getColumnListing($model->getTable());

		foreach ($columnNames as $column) {
			if (!$model->isFillable($column)) {
				continue; // only check columns that can actually be filled into the database
			}

			if (!in_array($column, $keys)) {
				throw new BadPostRequestException('POST requests must contain all attributes. Failed for: ' . $column);
			}
		}

		$model->setRawAttributes($this->json->getArray());
		$model->save();

		return \Response::make($this->show($model), Response::HTTP_CREATED);
	}

	/**
	 * Handles PUT and PATCH requests trying to create or update a model. Parameters are taken from the JSON request
	 * body. PUT requests must contain all fillable attributes.
	 *
	 * @param null $model
	 * @return array
	 * @throws Exception
	 */
	public function update($model = null)
	{
		/* @var Model $model */
		if ($model == null) {
			$model = new $this->model;
		}

		switch (Request::getMethod()) {
			case 'PUT':
				$existed = $model->exists;
				$keys = array_keys($this->json->getArray());
				$columnNames = \Schema::getColumnListing($model->getTable());

				foreach ($columnNames as $column) {
					if (!$model->isFillable($column)) {
						continue; // only check columns that can actually be filled into the database
					}

					if (!in_array($column, $keys)) {
						throw new BadPutRequestException('PUT requests must contain all attributes. Failed for: ' . $column);
					}
				}

				if ($model->exists) {
					$model->update($this->json->getArray());
					$model->syncOriginal();
				} else {
					$model->setRawAttributes($this->json->getArray(), true);
					$model->save();
				}

				return $existed ? $this->show($model) : \Response::make($this->show($model), Response::HTTP_CREATED);
			case 'PATCH':
				if (!$model->exists) {
					throw new ModelNotFoundException;
				}

				$model->update($this->json->getArray());
				$model->syncOriginal();

				return $this->show($model);
			default:
				throw new Exception('Update has to be called via PUT or PATCH.');
		}
	}

	/**
	 * @param null $model
	 * @return Response
	 * @throws DatabaseConflictException
	 */
	public function destroy($model = null)
	{
		if (!($model instanceof Model) || !$model->exists) {
			throw new ModelNotFoundException;
		}

		try {
			$model->delete();
		} catch (Exception $e) {
			throw new DatabaseConflictException('Model could not be deleted: ' . $model->{$model->getKeyName()});
		}

		return \Response::make($this->api->build(), Response::HTTP_NO_CONTENT);
	}
	
}
