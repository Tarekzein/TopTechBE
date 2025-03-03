<?php

namespace Modules\Vendor\Services;

use Modules\Vendor\Interfaces\VendorRepositoryInterface;
use Modules\Vendor\Interfaces\VendorServiceInterface;

class VendorService implements VendorServiceInterface
{
    public $vendor_repository;

    public function __construct(VendorRepositoryInterface $vendor_repository)
    {
        $this->vendor_repository = $vendor_repository;
    }

    public function getAll()
    {
        return $this->vendor_repository->getAll();
    }

    public function getById($id)
    {
        return $this->vendor_repository->findById($id);
    }

    public function create(array $data)
    {
        return $this->vendor_repository->create($data);
    }

    public function update($id, array $data)
    {
        return $this->vendor_repository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->vendor_repository->delete($id);
    }
}
