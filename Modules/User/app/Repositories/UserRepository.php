<?php

namespace Modules\User\Repositories;

use Modules\User\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class UserRepository implements UserRepositoryInterface
{
    public function getAll()
    {
        try {
            return User::all();
        } catch (Exception $e) {
            throw new Exception('Error fetching users: ' . $e->getMessage());
        }
    }

    public function findById($id)
    {
        try {
            return User::findOrFail($id);
        } catch (Exception $e) {
            throw new Exception('Error finding user: ' . $e->getMessage());
        }
    }

    public function create(array $data)
    {
        try {
            DB::beginTransaction();
            
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
            ]);
            
            DB::commit();
            return $user;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Error creating user: ' . $e->getMessage());
        }
    }

    public function update($id, array $data)
    {
        try {
            DB::beginTransaction();
            
            $user = User::findOrFail($id);
            if (isset($data['password'])) {
                $data['password'] = bcrypt($data['password']);
            }
            $user->update($data);
            
            DB::commit();
            return $user;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Error updating user: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            
            $user = User::findOrFail($id);
            $result = $user->delete();
            
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Error deleting user: ' . $e->getMessage());
        }
    }
    public function getWithRoles($perPage = 20)
    {
        return \App\Models\User::with('roles')->paginate($perPage);
    }
    
}
