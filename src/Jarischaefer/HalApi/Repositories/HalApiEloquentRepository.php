<?php namespace Jarischaefer\HalApi\Repositories;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Builder;
use Jarischaefer\HalApi\Exceptions\DatabaseConflictException;
use Jarischaefer\HalApi\Exceptions\DatabaseSaveException;
use ReflectionClass;
use ReflectionException;

/**
 * Class HalApiEloquentRepository
 * @package Jarischaefer\HalApi\Repositories
 */
abstract class HalApiEloquentRepository implements HalApiRepository
{

	/**
	 * @var Model
	 */
	protected $model;

	/**
	 * @var Builder
	 */
	private $schemaBuilder;

	/**
	 * @param DatabaseManager $databaseManager
	 */
	public function __construct(DatabaseManager $databaseManager)
	{
		$this->schemaBuilder = $databaseManager->connection()->getSchemaBuilder();

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
			$saved = $model->save();
		} catch (Exception $e) {
			throw new DatabaseSaveException('Model could not be saved.', 0, $e);
		}

		if (!$saved) {
			throw new DatabaseSaveException('Model could not be saved.');
		}

		return $model;
	}

	/**
	 * @inheritdoc
	 */
	public function remove(Model $model)
	{
		try {
			$deleted = $model->delete();
		} catch (Exception $e) {
			throw new DatabaseConflictException('Model could not be deleted: ' . $model->getKey());
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
		// TODO reflection hack

		$originalResolver = self::getOriginalResolver();
		\Illuminate\Pagination\Paginator::currentPageResolver(function () use ($page) {
			return $page;
		});

		try {
			return $this->model->newQuery()->simplePaginate($perPage);
		} finally {
			\Illuminate\Pagination\Paginator::currentPageResolver($originalResolver);
		}
	}

	/**
	 * @return mixed
	 * @throws ReflectionException
	 */
	private static function getOriginalResolver()
	{
		$reflectionClass = new ReflectionClass(\Illuminate\Pagination\Paginator::class);

		if (!$reflectionClass->hasProperty('currentPageResolver')) {
			throw new ReflectionException('Could not find currentPageResolver');
		}

		$property = $reflectionClass->getProperty('currentPageResolver');

		if (!$property->isPublic()) {
			$property->setAccessible(true);
		}

		return $property->getValue();
	}

	/**
	 * @inheritdoc
	 */
	public function getMissingFillableAttributes(array $attributes): array
	{
		$columnNames = $this->schemaBuilder->getColumnListing($this->model->getTable());
		$missing = [];

		foreach ($columnNames as $column) {
			if ($this->model->isFillable($column) && !in_array($column, $attributes)) {
				$missing[] = $column;
			}
		}

		return $missing;
	}

}
