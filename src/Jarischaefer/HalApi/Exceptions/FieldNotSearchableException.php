<?php namespace Jarischaefer\HalApi\Exceptions;

use RuntimeException;

/**
 * Class FieldNotSearchableException
 * @package Jarischaefer\HalApi\Exceptions
 */
class FieldNotSearchableException extends RuntimeException
{

	/**
	 * @param string $field
	 */
	public function __construct(string $field)
	{
		parent::__construct('Field is not searchable: ' . $field);
	}

}
