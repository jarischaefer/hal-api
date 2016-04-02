<?php namespace Jarischaefer\HalApi\Controllers;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Jarischaefer\HalApi\Exceptions\BadPostRequestException;
use Jarischaefer\HalApi\Exceptions\BadPutRequestException;
use Jarischaefer\HalApi\Repositories\HalApiRepository;
use Jarischaefer\HalApi\Representations\HalApiPaginatedRepresentation;
use Jarischaefer\HalApi\Helpers\RouteHelper;
use Jarischaefer\HalApi\Transformers\HalApiTransformerContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class HalApiResourceController
 * @package Jarischaefer\HalApi\Controllers
 */
abstract class HalApiResourceController extends HalApiController implements HalApiResourceControllerContract
{

	/**
	 * The model's transformer.
	 *
	 * @var HalApiTransformerContract
	 */
	protected $transformer;
	/**
	 * The repository being used for data retrieval.
	 *
	 * @var HalApiRepository
	 */
	protected $repository;

	/**
	 * @param HalApiControllerParameters $parameters
	 * @param HalApiTransformerContract $transformer
	 * @param HalApiRepository $repository
	 */
	public function __construct(HalApiControllerParameters $parameters, HalApiTransformerContract $transformer, HalApiRepository $repository)
	{
		parent::__construct($parameters);

		$this->transformer = $transformer;
		$this->repository = $repository;

		$this->boot();
	}

	/**
	 * @inheritdoc
	 */
	public function getTransformer(): HalApiTransformerContract
	{
		return $this->transformer;
	}

	/**
	 * @inheritdoc
	 */
	public function getRepository(): HalApiRepository
	{
		return $this->repository;
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
	 * @param HalApiRequestParameters $parameters
	 * @param Paginator $paginator
	 * @return HalApiPaginatedRepresentation
	 */
	protected function paginate(HalApiRequestParameters $parameters, Paginator $paginator): HalApiPaginatedRepresentation
	{
		$self = $parameters->getSelf();
		$parent = $parameters->getParent();
		$relation = static::getRelation(RouteHelper::SHOW);

		return $this->representationFactory->paginated($self, $parent, $paginator, $this->transformer, $relation);
	}

	/**
	 * @inheritdoc
	 */
	public function index(HalApiRequestParameters $parameters): Response
	{
		$paginator = $this->repository->paginate($parameters->getPage(), $parameters->getPerPage());

		return $this->responseFactory->json($this->paginate($parameters, $paginator)->build());
	}

	/**
	 * @inheritdoc
	 */
	public function show(HalApiRequestParameters $parameters, Model $model): Response
	{
		return $this->responseFactory->json($this->transformer->item($model)->build());
	}

	/**
	 * @inheritdoc
	 */
	public function store(HalApiRequestParameters $parameters): Response
	{
		$missingAttributes = $this->repository->getMissingFillableAttributes($parameters->getBody()->keys());

		if (!empty($missingAttributes)) {
			throw new BadPostRequestException('POST requests must contain all attributes. Failed for: ' . join(',', $missingAttributes));
		}

		$model = $this->repository->create($parameters->getBody()->getArray());

		return $this->show($parameters, $model)->setStatusCode(Response::HTTP_CREATED);
	}

	/**
	 * @inheritdoc
	 */
	public function update(HalApiRequestParameters $parameters, $model): Response
	{
		/** @var Model $model */
		if (!($model instanceof Model)) {
			$id = $model;
			$model = $this->repository->create();
			$model->{$model->getKeyName()} = $id;
		}

		switch ($parameters->getRequest()->getMethod()) {
			case Request::METHOD_PUT:
				$missingAttributes = $this->repository->getMissingFillableAttributes($parameters->getBody()->keys());

				if (!empty($missingAttributes)) {
					throw new BadPutRequestException('PUT requests must contain all attributes. Failed for: ' . join(',', $missingAttributes));
				}

				$existed = $model->exists;
				$model = $this->repository->save($model->fill($parameters->getBody()->getArray()));

				return $existed ? $this->show($parameters, $model) : $this->show($parameters, $model)->setStatusCode(Response::HTTP_CREATED);
			case Request::METHOD_PATCH:
				$this->repository->save($model->fill($parameters->getBody()->getArray()));

				return $this->show($parameters, $model);
			default:
				return $this->responseFactory->make('', Response::HTTP_METHOD_NOT_ALLOWED);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function destroy(HalApiRequestParameters $parameters, Model $model): Response
	{
		$this->repository->remove($model);

		return $this->responseFactory->make('', Response::HTTP_NO_CONTENT);
	}

}
