<?php namespace Jarischaefer\HalApi\Transformers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Route;
use InvalidArgumentException;
use Jarischaefer\HalApi\Helpers\RouteHelper;
use Jarischaefer\HalApi\Representations\RepresentationFactory;
use Jarischaefer\HalApi\Routing\LinkFactory;

/**
 * Class TransformerFactoryImpl
 * @package Jarischaefer\HalApi\Transformers
 */
class TransformerFactoryImpl implements TransformerFactory
{

	/**
	 * @var Application
	 */
	private $application;
	/**
	 * @var LinkFactory
	 */
	private $linkFactory;
	/**
	 * @var RepresentationFactory
	 */
	private $representationFactory;
	/**
	 * @var RouteHelper
	 */
	private $routeHelper;

	/**
	 * @param Application $application
	 * @param LinkFactory $linkFactory
	 * @param RepresentationFactory $representationFactory
	 * @param RouteHelper $routeHelper
	 */
	public function __construct(Application $application, LinkFactory $linkFactory, RepresentationFactory $representationFactory, RouteHelper $routeHelper)
	{
		$this->application = $application;
		$this->linkFactory = $linkFactory;
		$this->representationFactory = $representationFactory;
		$this->routeHelper = $routeHelper;
	}

	/**
	 * @inheritdoc
	 */
	public function create($class, Route $self, Route $parent, array $arguments = [])
	{
		if (!is_subclass_of($class, HalApiTransformer::class)) {
			throw new InvalidArgumentException('Class must be a subclass of ' . HalApiTransformer::class);
		}

		$parameters = array_merge([
			$this->linkFactory,
			$this->representationFactory,
			$this->routeHelper,
			$self,
			$parent,
		], $arguments);

		return $this->application->make($class, $parameters);
	}

}
