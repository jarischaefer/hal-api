<?php namespace Jarischaefer\HalApi\Exceptions;

use Exception;
use RuntimeException;

/**
 * Class DatabaseConflictException
 * @package Jarischaefer\HalApi\Exceptions
 */
class DatabaseConflictException extends RuntimeException
{

	/**
	 * @param string $message
	 * @param Exception|null $previous
	 */
	public function __construct(string $message = "", Exception $previous = null)
	{
		parent::__construct($message, 0, $previous);
	}

}
