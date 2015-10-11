<?php namespace Jarischaefer\HalApi\Transformers;

use Illuminate\Routing\Route;

/**
 * Interface TransformerFactory
 * @package Jarischaefer\HalApi\Transformers
 */
interface TransformerFactory
{

	/**
	 * @param string $class
	 * @param Route $self
	 * @param Route $parent
	 * @param array $arguments
	 * @return HalApiTransformerContract
	 */
	public function create($class, Route $self, Route $parent, array $arguments = []);

}
