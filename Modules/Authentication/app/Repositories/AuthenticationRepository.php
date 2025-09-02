<?php

namespace Modules\Authentication\Repositories;

use Modules\Authentication\Interfaces\AuthenticationRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Modules\User\Interfaces\UserRepositoryInterface;
use Modules\Store\Services\CartService;
use Modules\Store\Services\WishlistService;
use Illuminate\Support\Facades\DB;
use Exception;
use Modules\Authentication\Events\NewUser;

class AuthenticationRepository implements AuthenticationRepositoryInterface
{
    protected $users_repository;
    protected $cartService;
    protected $wishlistService;

    public function __construct(
        UserRepositoryInterface $users_repository,
        CartService $cartService,
        WishlistService $wishlistService
    ) {
        $this->users_repository = $users_repository;
        $this->cartService = $cartService;
        $this->wishlistService = $wishlistService;
    }

    public function register(array $data)
    {
        try {
            DB::beginTransaction();
            
            $user = $this->users_repository->create($data);
            $user->assignRole('customer');
            $user->load('roles');
            $token = $user->createToken('auth_token')->plainTextToken;
            
            // Dispatch NewUser event to send welcome email
            event(new NewUser($user));
            
            DB::commit();
            return ['user' => $user, 'token' => $token];
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Error during registration: ' . $e->getMessage());
        }
    }

    public function vendorRegister(array $data)
    {
        try {
            DB::beginTransaction();
            
            $user = $this->users_repository->create($data);
            $user->assignRole('vendor');
            $user->load('roles');
            $token = $user->createToken('auth_token')->plainTextToken;
            
            // Dispatch NewUser event to send welcome email
            event(new NewUser($user));
            
            DB::commit();
            return ['user' => $user, 'token' => $token];
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Error during vendor registration: ' . $e->getMessage());
        }
    }

    public function login(array $credentials)
    {
        try {
            if (!Auth::attempt($credentials)) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            $user = Auth::user();
            $user->load('roles');
            $token = $user->createToken('auth_token')->plainTextToken;

            // Get user's cart and wishlist
            $cart = $this->cartService->getOrCreateCart($user->id);
            $wishlist = $this->wishlistService->getWishlist($user->id);

            return [
                'user' => $user,
                'token' => $token,
                'cart' => $cart->load('items.product'),
                'wishlist' => $wishlist ? $wishlist->load('items.product') : null
            ];
        } catch (Exception $e) {
            throw new Exception('Error during login: ' . $e->getMessage());
        }
    }

    public function logout($user)
    {
        try {
            DB::beginTransaction();
            
            $user->currentAccessToken()->delete();
            
            DB::commit();
            return ['message' => 'Logged out successfully'];
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Error during logout: ' . $e->getMessage());
        }
    }

    public function dashboardLogin(array $credentials)
    {
        try {
            if (!Auth::attempt($credentials)) {
                throw new Exception('Invalid credentials');
            }

            $user = Auth::user();
            if (!$user) {
                throw new Exception('User not found');
            }

            $user->load('roles');
            if (!$user->hasRole(['admin', 'super-admin'])) {
                Auth::logout();
                throw new Exception('You are not authorized to access this application');
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            return ['user' => $user, 'token' => $token];
        } catch (Exception $e) {
            throw new Exception('Error during dashboard login: ' . $e->getMessage());
        }
    }
    // admin 
    public function adminRegister(array $data)
{
    try {
        DB::beginTransaction();

        $user = $this->users_repository->create($data);
        $user->assignRole('admin'); // 🟢 الدور Admin
        $user->load('roles');

        $token = $user->createToken('auth_token')->plainTextToken;

        // 🟢 إرسال event (لو عندك Welcome email أو إشعارات)
        event(new NewUser($user));

        DB::commit();

        return [
            'user'  => $user,
            'token' => $token,
        ];
    } catch (Exception $e) {
        DB::rollBack();
        throw new Exception('Error during admin registration: ' . $e->getMessage());
    }
}

}
