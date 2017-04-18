<?php namespace Jarischaefer\HalApi\Repositories;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Jarischaefer\HalApi\Exceptions\DatabaseConflictException;
use Jarischaefer\HalApi\Exceptions\DatabaseSaveException;
use RuntimeException;

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
	 * @var string
	 */
	protected $class;

	/**
	 * @param DatabaseManager $databaseManager
	 */
	public function __construct(DatabaseManager $databaseManager)
	{
		$this->databaseManager = $databaseManager;
		$this->class = static::getModelClass();

		if (!is_subclass_of($this->class, Model::class)) {
			throw new RuntimeException('Model class must be subclass of ' . Model::class . ', but was ' . $this->class);
		}
	}

	/**
	 * @param array $attributes
	 * @return Model
	 */
	public function create(array $attributes = []): Model
	{
		return $this->newInstance()->fill($attributes);
	}

	/**
	 * @inheritdoc
	 */
	public function all(): Collection
	{
		return $this->newInstance()->newQuery()->get();
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
	public function saveMany(iterable $models): void
	{
		try {
			$this->databaseManager->connection()->transaction(function () use ($models) {
				/** @var Model $model */
				foreach ($models as $model) {
					$model->save();
				}
			});
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
		return $this->newInstance()->newQuery()->paginate($perPage, ['*'], 'page', $page);
	}

	/**
	 * @inheritdoc
	 */
	public function simplePaginate(int $page, int $perPage): Paginator
	{
		return $this->newInstance()->newQuery()->simplePaginate($perPage, ['*'], 'page', $page);
	}

	/**
	 * @inheritdoc
	 */
	public function getMissingFillableAttributes(array $attributes): array
	{
		$schemaBuilder = $this->databaseManager->connection()->getSchemaBuilder();
		$model = $this->newInstance();
		$columnNames = $schemaBuilder->getColumnListing($model->getTable());
		$missing = [];

		foreach ($columnNames as $column) {
			if ($model->isFillable($column) && !in_array($column, $attributes)) {
				$missing[] = $column;
			}
		}

		return $missing;
	}

	/**
	 * @return Model
	 */
	protected function newInstance(): Model
	{
		return new $this->class;
	}

}
