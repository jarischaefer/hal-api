<?php namespace Jarischaefer\HalApi\Repositories;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Jarischaefer\HalApi\Exceptions\DatabaseConflictException;
use Jarischaefer\HalApi\Exceptions\DatabaseSaveException;

/**
 * Class HalApiEloquentRepository
 * @package Jarischaefer\HalApi\Repositories
 */
abstract class HalApiEloquentRepository implements HalApiRepository
{

	/**
	 * @var DatabaseManager
	 */
	protected $databaseManager;
	/**
	 * @var Model
	 */
	protected $model;

	/**
	 * @param DatabaseManager $databaseManager
	 */
	public function __construct(DatabaseManager $databaseManager)
	{
		$this->databaseManager = $databaseManager;

		$class = static::getModelClass();
		$this->model = new $class;
	}

	/**
	 * @param array $attributes
	 * @return Model
	 */
	public function create(array $attributes = []): Model
	{
		$class = $this->model;
		/** @var Model $model */
		$model = new $class;

		if (empty($attributes)) {
			return $model;
		}

		$model->fill($attributes);
		return $this->save($model);
	}

	/**
	 * @inheritdoc
	 */
	public function all(): Collection
	{
		$model = $this->model;
		return $model::all();
	}

	/**
	 * @inheritdoc
	 */
	public function save(Model $model): Model
	{
		try {
			$model->save();
			return $model;
		} catch (Exception $e) {
			throw new DatabaseSaveException('Model could not be saved.', 0, $e);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function remove(Model $model)
	{
		try {
			$deleted = $model->delete();
		} catch (Exception $e) {
			throw new DatabaseConflictException('Model could not be deleted: ' . $model->getKey(), $e);
		}

		if (!$deleted) {
			throw new DatabaseConflictException('Model could not be deleted: ' . $model->getKey());
		}
	}

	/**
	 * @inheritdoc
	 */
	public function paginate(int $page, int $perPage): LengthAwarePaginator
	{
		return $this->model->newQuery()->paginate($perPage, ['*'], 'page', $page);
	}

	/**
	 * @inheritdoc
	 */
	public function simplePaginate(int $page, int $perPage): Paginator
	{
		return $this->model->newQuery()->simplePaginate($perPage, ['*'], 'page', $page);
	}

	/**
	 * @inheritdoc
	 */
	public function getMissingFillableAttributes(array $attributes): array
	{
		$schemaBuilder = $this->databaseManager->connection()->getSchemaBuilder();
		$columnNames = $schemaBuilder->getColumnListing($this->model->getTable());
		$missing = [];

		foreach ($columnNames as $column) {
			if ($this->model->isFillable($column) && !in_array($column, $attributes)) {
				$missing[] = $column;
			}
		}

		return $missing;
	}

}
