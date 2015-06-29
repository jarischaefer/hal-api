<?php namespace Jarischaefer\HalApi\Helpers;

use Exception;

final class Checks
{

	private function __construct()
	{
	}

	public static function arrayType(array $array, $class)
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