<?php

namespace Modules\Vendor\Repositories;

use Modules\Vendor\Interfaces\VendorRepositoryInterface;
use Modules\Vendor\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Exception;

class VendorRepository implements VendorRepositoryInterface
{
    public function getAll()
    {
        try {
            return Vendor::all();
        } catch (Exception $e) {
            throw new Exception('Error fetching vendors: ' . $e->getMessage());
        }
    }

    public function findById($id)
    {
        try {
            return Vendor::findOrFail($id);
        } catch (Exception $e) {
            throw new Exception('Error finding vendor: ' . $e->getMessage());
        }
    }

    public function create(array $data)
    {
        try {
            DB::beginTransaction();
            
            $vendor = Vendor::create($data);
            
            DB::commit();
            return $vendor;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Error creating vendor: ' . $e->getMessage());
        }
    }

    public function update($id, array $data)
    {
        try {
            DB::beginTransaction();
            
            $vendor = Vendor::findOrFail($id);
            $vendor->update($data);
            
            DB::commit();
            return $vendor;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Error updating vendor: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            
            $vendor = Vendor::findOrFail($id);
            $result = $vendor->delete();
            
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Error deleting vendor: ' . $e->getMessage());
        }
    }
}
