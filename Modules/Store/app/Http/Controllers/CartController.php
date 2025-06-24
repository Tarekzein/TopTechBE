<?php

namespace Modules\Store\Http\Controllers;

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
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    protected function getUserId(): ?int
    {
        return Auth::check() ? Auth::id() : null;
    }

    protected function getGuestToken(Request $request): ?string
    {
        return $request->header('X-Guest-Token') ?? $request->input('guest_token');
    }

    protected function loadCartRelations($cart)
    {
        $cart->load(['items.product', 'items.productVariation']);

        // Apply formatted attributes to each variation
        foreach ($cart->items as $item) {
            if ($item->productVariation) {
                $item->productVariation->attributes = $item->productVariation->formatted_attributes;
            }
        }

        return $cart;
    }

    public function getCart(Request $request)
    {
        try {
            $userId = $this->getUserId();
            $guestToken = $this->getGuestToken($request);

            $cart = $this->cartService->getOrCreateCart($userId, $guestToken);
            $this->loadCartRelations($cart);

            return response()->json(['status' => 'success', 'data' => $cart]);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function addItem(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer',
                'quantity' => 'required|integer',
                'product_variation_id' => 'nullable|integer',
            ]);

            $userId = $request->user_id ?? $this->getUserId();
            $guestToken = $this->getGuestToken($request) ?? Str::uuid()->toString();

            Log::info('Adding item to cart', array_merge($validated, compact('userId', 'guestToken')));

            $cart = $this->cartService->getOrCreateCart($userId, $guestToken);
            $this->cartService->addItem($cart, $validated['product_id'], $validated['quantity'], $validated['product_variation_id'] ?? null);
            $this->loadCartRelations($cart);

            return response()->json([
                'status' => 'success',
                'message' => 'Item added to cart',
                'data' => $cart,
                'guest_token' => $guestToken,
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function updateItem(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer',
                'quantity' => 'required|integer|min:1',
                'product_variation_id' => 'nullable|integer',
            ]);

            $userId = $request->user_id ?? $this->getUserId();
            $guestToken = $this->getGuestToken($request);

            Log::info('Updating cart item', array_merge($validated, compact('userId', 'guestToken')));

            $cart = $this->cartService->getOrCreateCart($userId, $guestToken);
            $this->cartService->updateItem($cart, $validated['product_id'], $validated['quantity'], $validated['product_variation_id'] ?? null);
            $this->loadCartRelations($cart);

            return response()->json([
                'status' => 'success',
                'message' => 'Cart item updated',
                'data' => $cart,
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function removeItem(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer',
            ]);

            $userId = $request->user_id ?? $this->getUserId();
            $guestToken = $this->getGuestToken($request);

            $cart = $this->cartService->getOrCreateCart($userId, $guestToken);
            $this->cartService->removeItem($cart, $validated['product_id']);
            $this->loadCartRelations($cart);

            return response()->json([
                'status' => 'success',
                'message' => 'Item removed from cart',
                'data' => $cart,
            ]);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function clearCart(Request $request)
    {
        try {
            $userId = $request->user()->id ?? $this->getUserId();
            $guestToken = $this->getGuestToken($request);

            $cart = $this->cartService->getOrCreateCart($userId, $guestToken);
            $this->cartService->clearCart($cart);

            return response()->json([
                'status' => 'success',
                'message' => 'Cart cleared',
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function mergeOnLogin(Request $request)
    {
        try {
            $userId = $this->getUserId();
            $guestToken = $this->getGuestToken($request);

            if (!$userId || !$guestToken) {
                return response()->json(['status' => 'error', 'message' => 'User or guest token missing'], 400);
            }

            $userCart = $this->cartService->getOrCreateCart($userId, null);
            $guestCart = $this->cartService->getOrCreateCart(null, $guestToken);

            $cart = ($guestCart && $guestCart->items->count())
                ? $this->cartService->mergeCarts($userCart, $guestCart)
                : $userCart;

            $this->loadCartRelations($cart);

            return response()->json([
                'status' => 'success',
                'message' => 'Cart merged',
                'data' => $cart,
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e);
        }
    }

    private function validationErrorResponse(ValidationException $e)
    {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'errors' => $e->errors(),
        ], 422);
    }

    private function errorResponse(Exception $e)
    {
        Log::error($e);
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}
