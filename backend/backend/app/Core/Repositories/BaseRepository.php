<?php

declare(strict_types=1);

namespace App\Core\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Base Repository.
 *
 * Generic repository yang menyediakan operasi CRUD standar.
 * Domain repository meng-extend class ini dan menambahkan
 * query yang spesifik untuk domain tersebut.
 *
 * @template TModel of Model
 */
abstract class BaseRepository
{
    public function __construct(
        protected readonly Model $model
    ) {}

    /**
     * Ambil semua record.
     *
     * @return Collection<int, TModel>
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->all($columns);
    }

    /**
     * Ambil record dengan pagination.
     *
     * @return LengthAwarePaginator<TModel>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    /**
     * Cari berdasarkan ID.
     *
     * @return TModel|null
     */
    public function findById(int|string $id, array $columns = ['*']): ?Model
    {
        return $this->model->find($id, $columns);
    }

    /**
     * Cari berdasarkan ID atau throw exception.
     *
     * @return TModel
     */
    public function findOrFail(int|string $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Buat record baru.
     *
     * @return TModel
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update record berdasarkan ID.
     *
     * @return TModel
     */
    public function update(int|string $id, array $data): Model
    {
        $record = $this->findOrFail($id);
        $record->update($data);

        return $record->fresh();
    }

    /**
     * Hapus record berdasarkan ID.
     */
    public function delete(int|string $id): bool
    {
        $record = $this->findOrFail($id);

        return $record->delete();
    }

    /**
     * Cek apakah record dengan kondisi tertentu ada.
     */
    public function exists(array $conditions): bool
    {
        return $this->model->where($conditions)->exists();
    }

    /**
     * Akses query builder langsung untuk query kompleks.
     *
     * @return Builder<TModel>
     */
    protected function query(): Builder
    {
        return $this->model->newQuery();
    }
}
