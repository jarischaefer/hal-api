<?php namespace Jarischaefer\HalApi\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
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
	public function search(string $field, string $term, int $page, int $perPage): LengthAwarePaginator
	{
		if (!self::isFieldSearchable($field) || !$this->fieldExists($field)) {
			throw new FieldNotSearchableException($field);
		}

		$query = $this->model->newQuery();
		
		static::appendSearchTerm($query, $field, $term);

		return $query->paginate($perPage, ['*'], 'page', $page);
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
				static::appendSearchTerm($query, $field, $term);
			}
		}

		return $query->paginate($perPage, ['*'], 'page', $page);
	}

	/**
	 * This method should be overridden if the default search term is undesirable.
	 * Very large tables might require native queries (e.g. full-text using MATCH AGAINST)
	 * or no leading wildcard (e.g. TERM% instead of %TERM%).
	 *
	 * @param Builder $builder
	 * @param string $field
	 * @param string $term
	 */
	protected static function appendSearchTerm(Builder $builder, string $field, string $term)
	{
		$builder->where($field, 'LIKE', '%' . $term . '%');
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
