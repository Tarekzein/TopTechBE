<?php

namespace Modules\Store\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Store\Services\CartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Exception;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    protected function getUserId()
    {
        return Auth::check() ? Auth::id() : null;
    }

    public function getCart(Request $request)
    {
        try {
            $userId = $this->getUserId();
            $guestToken = $request->header('X-Guest-Token') ?? $request->query('guest_token');
            $cart = $this->cartService->getOrCreateCart($userId, $guestToken);
            return response()->json(['status' => 'success', 'data' => $cart->load('items.product')]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function addItem(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer',
                'quantity' => 'required|integer|min:1',
            ]);
            $userId = $request->user()->id ?? $this->getUserId();
            $guestToken = $request->header('X-Guest-Token') ?? $request->input('guest_token');
            Log::info('Adding item to cart', [
                'user_id' => $userId,
                'guest_token' => $guestToken,
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
            ]);
            if (!$userId && !$guestToken) {
                $guestToken = Str::uuid()->toString();
            }
            $cart = $this->cartService->getOrCreateCart($userId, $guestToken);
            $item = $this->cartService->addItem($cart, $validated['product_id'], $validated['quantity']);
            return response()->json([
                'status' => 'success',
                'message' => 'Item added to cart',
                'data' => $cart->fresh('items.product'),
                'guest_token' => $guestToken,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function updateItem(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer',
                'quantity' => 'required|integer|min:1',
            ]);
            Log::info('Updating cart item', [
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'headers' => $request->headers->all(),
            ]);
            $userId = $request->user_id ?? $this->getUserId();
            $guestToken = $request->header('X-Guest-Token') ?? $request->input('guest_token');
            Log::info('Updating cart item', [
                'user_id' => $userId,
                'guest_token' => $guestToken,
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
            ]);
            $cart = $this->cartService->getOrCreateCart($userId, $guestToken);
            $item = $this->cartService->updateItem($cart, $validated['product_id'], $validated['quantity']);
            return response()->json([
                'status' => 'success',
                'message' => 'Cart item updated',
                'data' => $cart->fresh('items.product'),
            ]);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function removeItem(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer',
            ]);
            $userId = $request->user_id ?? $this->getUserId();
            $guestToken = $request->header('X-Guest-Token') ?? $request->input('guest_token');
            $cart = $this->cartService->getOrCreateCart($userId, $guestToken);
            $this->cartService->removeItem($cart, $validated['product_id']);
            return response()->json([
                'status' => 'success',
                'message' => 'Item removed from cart',
                'data' => $cart->fresh('items.product'),
            ]);
        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function clearCart(Request $request)
    {
        try {
            $userId = $request->user()->id ?? $this->getUserId();
            $guestToken = $request->header('X-Guest-Token') ?? $request->input('guest_token');
            $cart = $this->cartService->getOrCreateCart($userId, $guestToken);
            $this->cartService->clearCart($cart);
            return response()->json([
                'status' => 'success',
                'message' => 'Cart cleared',
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // Call this endpoint after login to merge guest cart with user cart
    public function mergeOnLogin(Request $request)
    {
        try {
            $userId = $this->getUserId();
            $guestToken = $request->header('X-Guest-Token') ?? $request->input('guest_token');
            if (!$userId || !$guestToken) {
                return response()->json(['status' => 'error', 'message' => 'User or guest token missing'], 400);
            }
            $userCart = $this->cartService->getOrCreateCart($userId, null);
            $guestCart = $this->cartService->getOrCreateCart(null, $guestToken);
            if ($guestCart && $guestCart->items->count()) {
                $cart = $this->cartService->mergeCarts($userCart, $guestCart);
            } else {
                $cart = $userCart;
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Cart merged',
                'data' => $cart->load('items.product'),
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
