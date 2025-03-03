<?php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\User\Interfaces\UserServiceInterface;

class UserController extends Controller
{
    protected $user_service;

    public function __construct(UserServiceInterface $user_service)
    {
        $this->user_service = $user_service;
    }

    public function index()
    {
        return response()->json($this->user_service->getAll(), 200);
    }

    public function show($id)
    {
        return response()->json($this->user_service->getById($id), 200);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);

        $data['password'] = bcrypt($data['password']);

        return response()->json($this->user_service->create($data), 201);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'nullable|min:6',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        return response()->json($this->user_service->update($id, $data), 200);
    }

    public function destroy($id)
    {
        return response()->json(['message' => 'User deleted successfully'], 200);
    }
}
