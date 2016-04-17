<?php namespace Jarischaefer\HalApi\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
	 * @return LengthAwarePaginator
	 * @throws FieldNotSearchableException
	 */
	public function search(string $field, string $term, int $page, int $perPage): LengthAwarePaginator;

	/**
	 * @param array $searchAttributes
	 * @param int $page
	 * @param int $perPage
	 * @return LengthAwarePaginator
	 */
	public function searchMulti(array $searchAttributes, int $page, int $perPage): LengthAwarePaginator;

}
