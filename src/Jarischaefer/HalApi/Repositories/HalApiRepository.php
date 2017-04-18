<?php namespace Jarischaefer\HalApi\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Jarischaefer\HalApi\Exceptions\DatabaseConflictException;
use Jarischaefer\HalApi\Exceptions\DatabaseSaveException;

/**
 * Interface HalApiRepository
 * @package Jarischaefer\HalApi\Repositories
 */
interface HalApiRepository
{

	/**
	 * @return string
	 */
	public static function getModelClass(): string;

	/**
	 * @param array $attributes
	 * @return Model
	 * @throws DatabaseSaveException
	 */
	public function create(array $attributes = []): Model;

	/**
	 * @return Collection
	 */
	public function all(): Collection;

	/**
	 * @param Model $model
	 * @return Model
	 * @throws DatabaseSaveException
	 */
	public function save(Model $model): Model;

	/**
	 * @param iterable|Model[] $models
	 */
	public function saveMany(iterable $models): void;

	/**
	 * @param Model $model
	 * @return void
	 * @throws DatabaseConflictException
	 */
	public function remove(Model $model);

	/**
	 * @param int $page
	 * @param int $perPage
	 * @return LengthAwarePaginator
	 */
	public function paginate(int $page, int $perPage): LengthAwarePaginator;

	/**
	 * @param int $page
	 * @param int $perPage
	 * @return Paginator
	 */
	public function simplePaginate(int $page, int $perPage): Paginator;

	/**
	 * POST and PUT requests must contain all attributes.
	 * This method returns all fillable attributes which are missing.
	 *
	 * @param array $attributes
	 * @return array
	 */
	public function getMissingFillableAttributes(array $attributes): array;

}
