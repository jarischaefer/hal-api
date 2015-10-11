<?php namespace Jarischaefer\HalApi\Helpers;

use ArrayAccess;

/**
 * Class SafeIndexArray
 * @package Jarischaefer\HalApi\Helpers
 */
class SafeIndexArray implements ArrayAccess
{

	/**
	 * @var array
	 */
	private $array;
	/**
	 * @var mixed
	 */
	private $default;

	/**
	 * @param array $array
	 * @param null $default
	 */
	public function __construct(array $array, $default = null)
	{
		$this->array = $array;
		$this->default = $default;
	}

	/**
	 * @return array
	 */
	public function getArray()
	{
		return $this->array;
	}

	/**
	 * @return mixed|null
	 */
	public function getDefault()
	{
		return $this->default;
	}

	/**
	 * @inheritdoc
	 */
	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->array);
	}

	/**
	 * @inheritdoc
	 */
	public function offsetGet($offset)
	{
		return array_key_exists($offset, $this->array) ? $this->array[$offset] : $this->default;
	}

	/**
	 * @inheritdoc
	 */
	public function offsetSet($offset, $value)
	{
		$this->array[$offset] = $value;
	}

	/**
	 * @inheritdoc
	 */
	public function offsetUnset($offset)
	{
		unset($this->array[$offset]);
	}

}
