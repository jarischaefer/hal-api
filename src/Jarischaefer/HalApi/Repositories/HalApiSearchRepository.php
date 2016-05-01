<?php namespace Jarischaefer\HalApi\Repositories;

use Illuminate\Contracts\Pagination\Paginator;
use Jarischaefer\HalApi\Exceptions\FieldNotSearchableException;

/**
 * Interface HalApiSearchRepository
 * @package Jarischaefer\HalApi\Repositories
 */
interface HalApiSearchRepository extends HalApiRepository
{

	/**
	 * @return array
	 */
	public static function searchableFields(): array;

	/**
	 * @param string $field
	 * @return bool
	 */
	public static function isFieldSearchable(string $field): bool;

	/**
	 * @param string $field
	 * @param string $term
	 * @param int $page
	 * @param int $perPage
	 * @return Paginator
	 * @throws FieldNotSearchableException
	 */
	public function search(string $field, string $term, int $page, int $perPage): Paginator;

	/**
	 * @param array $searchAttributes
	 * @param int $page
	 * @param int $perPage
	 * @return Paginator
	 */
	public function searchMulti(array $searchAttributes, int $page, int $perPage): Paginator;

}
