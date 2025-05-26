<?php

namespace Modules\Blog\App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Blog\App\Repositories\Interfaces\BaseRepositoryInterface;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function getAll(): Collection
    {
        return $this->model->all();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }

    public function findOrFail(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $model = $this->find($id);
        if (!$model) {
            return false;
        }
        return $model->update($data);
    }

    public function delete(int $id): bool
    {
        $model = $this->find($id);
        if (!$model) {
            return false;
        }
        return $model->delete();
    }

    public function restore(int $id): bool
    {
        $model = $this->model->withTrashed()->find($id);
        if (!$model) {
            return false;
        }
        return $model->restore();
    }

    public function forceDelete(int $id): bool
    {
        $model = $this->model->withTrashed()->find($id);
        if (!$model) {
            return false;
        }
        return $model->forceDelete();
    }

    protected function query(): Builder
    {
        return $this->model->newQuery();
    }

    protected function getModel(): Model
    {
        return $this->model;
    }
} 