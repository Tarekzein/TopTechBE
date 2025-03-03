<?php

namespace Modules\Vendor\Repositories;

use Modules\Vendor\Interfaces\VendorRepositoryInterface;
use Modules\Vendor\Models\Vendor;

class VendorRepository implements VendorRepositoryInterface
{
    public function getAll()
    {
        return Vendor::all();
    }

    public function findById($id)
    {
        return Vendor::findOrFail($id);
    }

    public function create(array $data)
    {
        return Vendor::create($data);
    }

    public function update($id, array $data)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->update($data);
        return $vendor;
    }

    public function delete($id)
    {
        $vendor = Vendor::findOrFail($id);
        return $vendor->delete();
    }
}
