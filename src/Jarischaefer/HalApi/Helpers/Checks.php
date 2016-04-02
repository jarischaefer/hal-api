<?php namespace Jarischaefer\HalApi\Helpers;

use Exception;

/**
 * Class Checks
 * @package Jarischaefer\HalApi\Helpers
 */
final class Checks
{

	private function __construct()
	{
	}

	/**
	 * @param array $array
	 * @param string $class
	 * @throws Exception
	 */
	public static function arrayType(array $array, string $class)
	{
		if (!class_exists($class) && !interface_exists($class)) {
			throw new Exception('Class not found');
		}

		foreach ($array as $item) {
			if (!($item instanceof $class)) {
				throw new Exception('Expected class ' . $class . ', but got ' . gettype($item));
			}
		}
	}

}
