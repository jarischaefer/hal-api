<?php namespace Jarischaefer\HalApi\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use Jarischaefer\HalApi\Exceptions\FieldNotSearchableException;

/**
 * Class HalApiEloquentSearchRepository
 * @package Jarischaefer\HalApi\Repositories
 */
abstract class HalApiEloquentSearchRepository extends HalApiEloquentRepository implements HalApiSearchRepository
{

	/**
	 * @inheritdoc
	 */
	public static function isFieldSearchable(string $field): bool
	{
		$searchableFields = static::searchableFields();

		return in_array('*', $searchableFields) || in_array($field, $searchableFields);
	}

	/**
	 * @inheritdoc
	 */
	public function search(string $field, $value, int $page, int $perPage): LengthAwarePaginator
	{
		if (!self::isFieldSearchable($field) || !$this->fieldExists($field)) {
			throw new FieldNotSearchableException($field);
		}

		return $this->model->newQuery()
			->where($field, 'LIKE', '%' . $value . '%')
			->paginate($perPage, ['*'], 'page', $page);
	}

	/**
	 * @inheritdoc
	 */
	public function searchMulti(array $searchAttributes, int $page, int $perPage): LengthAwarePaginator
	{
		$query = $this->model->newQuery();

		foreach ($searchAttributes as $field => $term) {
			if (empty($term)) {
				continue;
			}

			if (!self::isFieldSearchable($field)) {
				throw new FieldNotSearchableException($field);
			}

			if ($this->fieldExists($field)) {
				$query->where($field, 'LIKE', '%' . $term . '%');
			}
		}

		return $query->paginate($perPage, ['*'], 'page', $page);
	}

	/**
	 * @param string $field
	 * @return bool
	 */
	private function fieldExists(string $field): bool
	{
		$columnNames = $this->schemaBuilder->getColumnListing($this->model->getTable());

		return in_array($field, $columnNames);
	}

}
