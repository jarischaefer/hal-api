<?php namespace Jarischaefer\HalApi;

use ArrayAccess;

/**
 * Class SafeIndexArray
 * @package Jarischaefer\HalApi
 */
class SafeIndexArray implements ArrayAccess
{

	private $array;
	private $default;

	public function __construct(array $array, $default = null)
	{
		$this->array = $array;
		$this->default = $default;
	}

	public function getArray()
	{
		return $this->array;
	}

	public function getDefault()
	{
		return $this->default;
	}

	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->array);
	}

	public function offsetGet($offset)
	{
		return array_key_exists($offset, $this->array) ? $this->array[$offset] : $this->default;
	}

	public function offsetSet($offset, $value)
	{
		$this->array[$offset] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->array[$offset]);
	}

}
