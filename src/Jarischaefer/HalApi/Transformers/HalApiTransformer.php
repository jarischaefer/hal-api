<?php namespace Jarischaefer\HalApi\Transformers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Jarischaefer\HalApi\HalApiController;
use Jarischaefer\HalApi\HalLink;
use Jarischaefer\HalApi\Routing\RouteHelper;
use League\Fractal\TransformerAbstract;

/**
 * Class HalTransformer
 * @package Jarischaefer\HalApi\Transformers
 */
abstract class HalApiTransformer extends TransformerAbstract
{

	/**
	 * The model that is being transformed
	 *
	 * @var Model
	 */
	protected $model;
	/**
	 * The link to the current resource
	 *
	 * @var HalLink
	 */
	protected $self;
	/**
	 * The link to the parent resource
	 *
	 * @var HalLink
	 */
	protected $parent;
	/**
	 * The resource's data
	 *
	 * @var array
	 */
	protected $data = [];
	/**
	 * An array of links related to the current resource
	 *
	 * @var array
	 */
	protected $links = [];
	/**
	 * An array of embedded resources
	 *
	 * @var array
	 */
	protected $embedded = [];

	/**
	 * @return void
	 */
	abstract protected function boot();

	/**
	 * Transforms the model into a Hal response. This includes the model's data and all its relations and embedded data.
	 *
	 * @param Model $model
	 * @throws Exception
	 * @return array
	 */
	public final function transform(Model $model)
	{
		$this->model = $model;
		$this->boot();

		foreach ($this->links as $key => $value) {
			if (!is_string($key)) {
				throw new Exception('Transformer has invalid relations for its links: ' . get_called_class());
			}
		}

		$links['self'] = $this->self;
		$links['parent'] = $this->parent;
		$subordinateLinks = $this->generateSubordinateLinks();

		foreach ($subordinateLinks as $relation => $subordinateLink) {
			$links[$relation] = $subordinateLink;
		}

		foreach ($this->links as $relation => $transformedLink) {
			$links[$relation] = $transformedLink;
		}

		return [
			'data' => $this->data,
			'_links' => $links,
			'_embedded' => $this->embedded,
		];
	}

	/**
	 * Returns an array of child routes for the current resource with their respective relations as the array key.
	 *
	 * @return array
	 */
	private function generateSubordinateLinks()
	{
		$subordinateRoutes = RouteHelper::subordinates($this->self->getRoute());
		$links = [];

		/* @var Route $route */
		foreach ($subordinateRoutes as $route) {
			$actionName = $route->getActionName();

			if (!str_contains($actionName, '@')) {
				continue;
			}

			list($class, $action) = explode('@', $actionName);

			if (!is_subclass_of($class, HalApiController::class)) {
				continue;
			}

			/* @var HalApiController $class */
			$links[$class::getRelation($action)] = HalLink::make($route, $this->self->getParameters(), '', true);;
		}

		return $links;
	}

}
