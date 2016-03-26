<?php namespace Jarischaefer\HalApi\Controllers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Jarischaefer\HalApi\Exceptions\BadPostRequestException;
use Jarischaefer\HalApi\Exceptions\BadPutRequestException;
use Jarischaefer\HalApi\Exceptions\DatabaseConflictException;
use Jarischaefer\HalApi\Exceptions\DatabaseSaveException;
use Jarischaefer\HalApi\Transformers\HalApiTransformerContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface HalApiResourceControllerContract
 * @package Jarischaefer\HalApi\Controllers
 */
interface HalApiResourceControllerContract extends HalApiControllerContract
{

	/**
	 * Returns the model's class name.
	 *
	 * @return Model|string
	 */
	public static function getModel();

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
	 * will be the original route parameter.
	 * Otherwise, an instance of your model with the ->exists property set to false is passed.
	 *
	 * public function update($user)
	 * {
	 *        var_dump($user) // original route parameter (e.g. new resource's ID) if not found in database
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
	public static function getModelBindingCallback();

	/**
	 * Returns an instance of a transformer to be used for all transformations in this controller.
	 *
	 * @return HalApiTransformerContract
	 */
	public function getTransformer();

	/**
	 * Returns a paginated API response containing n models where n equals either the default number of models per page
	 * or the number specified by the user. The models are embedded into the response.
	 *
	 * @return Response
	 * @throws Exception
	 */
	public function index();

	/**
	 * Returns an API response containing the data of the specified model.
	 *
	 * @param $model
	 * @return Response
	 */
	public function show($model);

	/**
	 * Takes attributes from the JSON request body and stores them inside a new instance of the controller's model.
	 * This method also ensures that all the model's fillable (see the guarded variable inside the model class)
	 * attributes are present inside the JSON request body. Make sure you use this method for POST requests only.
	 *
	 * @return Response
	 * @throws BadPostRequestException
	 * @throws DatabaseSaveException
	 */
	public function store();

	/**
	 * Handles PUT and PATCH requests trying to create or update a model. Parameters are taken from the JSON request
	 * body. PUT requests must contain all fillable attributes.
	 *
	 * @param Model|mixed $model
	 * @return array
	 * @throws BadPutRequestException
	 * @throws DatabaseSaveException
	 */
	public function update($model);

	/**
	 * @param $model
	 * @return Response
	 * @throws DatabaseConflictException
	 */
	public function destroy($model);

}
