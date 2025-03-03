<?php

namespace Modules\User\Services;

use Modules\User\Interfaces\UserRepositoryInterface;
use Modules\User\Interfaces\UserServiceInterface;

class UserService implements UserServiceInterface
{
    protected $user_repository;

    public function __construct(UserRepositoryInterface $user_repository)
    {
        $this->user_repository = $user_repository;
    }

    public function getAll()
    {
        return $this->user_repository->getAll();
    }

    public function getById($id)
    {
        return $this->user_repository->findById($id);
    }

    public function create(array $data)
    {
        return $this->user_repository->create($data);
    }

    public function update($id, array $data)
    {
        return $this->user_repository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->user_repository->delete($id);
    }
}
