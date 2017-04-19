<?php namespace Jarischaefer\HalApi\Repositories;

use Illuminate\Contracts\Pagination\Paginator;
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
	public function search(string $field, string $term, int $page, int $perPage): Paginator
	{
		if (!self::isFieldSearchable($field) || !$this->fieldExists($field)) {
			throw new FieldNotSearchableException($field);
		}

		$query = $this->newInstance()->newQuery();
		
		static::appendSearchTerm($query, $field, $term);

		return static::execute($query, $page, $perPage);
	}

	/**
	 * @inheritdoc
	 */
	public function searchMulti(array $searchAttributes, int $page, int $perPage): Paginator
	{
		$query = $this->newInstance()->newQuery();

		foreach ($searchAttributes as $field => $term) {
			if ($term === null) {
				continue;
			}

			if (!self::isFieldSearchable($field)) {
				throw new FieldNotSearchableException($field);
			}

			if ($this->fieldExists($field)) {
				static::appendSearchTerm($query, $field, $term);
			}
		}

		return static::execute($query, $page, $perPage);
	}

	/**
	 * @param Builder $builder
	 * @param int $page
	 * @param int $perPage
	 * @return Paginator
	 */
	protected static function execute(Builder $builder, int $page, int $perPage): Paginator
	{
		return $builder->simplePaginate($perPage, ['*'], 'page', $page);
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
		$schemaBuilder = $this->databaseManager->connection()->getSchemaBuilder();
		$columnNames = $schemaBuilder->getColumnListing($this->newInstance()->getTable());

		return in_array($field, $columnNames);
	}

}
