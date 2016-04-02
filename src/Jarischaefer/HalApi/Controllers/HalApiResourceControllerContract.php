<?php namespace Jarischaefer\HalApi\Controllers;

use Illuminate\Database\Eloquent\Model;
use Jarischaefer\HalApi\Exceptions\BadPostRequestException;
use Jarischaefer\HalApi\Exceptions\BadPutRequestException;
use Jarischaefer\HalApi\Exceptions\DatabaseConflictException;
use Jarischaefer\HalApi\Exceptions\DatabaseSaveException;
use Jarischaefer\HalApi\Repositories\HalApiRepository;
use Jarischaefer\HalApi\Transformers\HalApiTransformerContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface HalApiResourceControllerContract
 * @package Jarischaefer\HalApi\Controllers
 */
interface HalApiResourceControllerContract extends HalApiControllerContract
{

	/**
	 * Returns an instance of a transformer to be used for all transformations in this controller.
	 *
	 * @return HalApiTransformerContract
	 */
	public function getTransformer(): HalApiTransformerContract;

	/**
	 * Returns the controller's repository.
	 * Repositories are used for data retrieval.
	 *
	 * @return HalApiRepository
	 */
	public function getRepository(): HalApiRepository;

	/**
	 * Returns a paginated API response containing n models where n equals either the default number of models per page
	 * or the number specified by the user. The models are embedded into the response.
	 *
	 * @param HalApiRequestParameters $parameters
	 * @return Response
	 */
	public function index(HalApiRequestParameters $parameters): Response;

	/**
	 * Returns an API response containing the data of the specified model.
	 *
	 * @param HalApiRequestParameters $parameters
	 * @param Model $model
	 * @return Response
	 */
	public function show(HalApiRequestParameters $parameters, Model $model): Response;

	/**
	 * Takes attributes from the JSON request body and stores them inside a new instance of the controller's model.
	 * This method also ensures that all the model's fillable (see the guarded variable inside the model class)
	 * attributes are present inside the JSON request body. Make sure you use this method for POST requests only.
	 *
	 * @param HalApiRequestParameters $parameters
	 * @return Response
	 * @throws BadPostRequestException
	 * @throws DatabaseSaveException
	 */
	public function store(HalApiRequestParameters $parameters): Response;

	/**
	 * Handles PUT and PATCH requests trying to create or update a model. Parameters are taken from the JSON request
	 * body. PUT requests must contain all fillable attributes.
	 *
	 * @param HalApiRequestParameters $parameters
	 * @param Model|mixed $model
	 * @return Response
	 * @throws BadPutRequestException
	 * @throws DatabaseSaveException
	 */
	public function update(HalApiRequestParameters $parameters, $model): Response;

	/**
	 * Handles DELETE requests.
	 *
	 * @param HalApiRequestParameters $parameters
	 * @param Model $model
	 * @return Response
	 * @throws DatabaseConflictException
	 */
	public function destroy(HalApiRequestParameters $parameters, Model $model): Response;

}
