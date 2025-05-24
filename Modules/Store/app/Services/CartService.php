<?php

namespace Modules\Store\Services;

use Modules\Store\Repositories\CartRepository;
use Modules\Store\Entities\Product;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Exception;

class CartService
{
    protected $cartRepository;

    public function __construct(CartRepository $cartRepository)
    {
        $this->cartRepository = $cartRepository;
    }

    public function getOrCreateCart($userId = null, $guestToken = null)
    {
        $cart = $this->cartRepository->getCart($userId, $guestToken);
        if (!$cart) {
            $cart = $this->cartRepository->createCart($userId, $guestToken);
        }
        return $cart;
    }

    public function addItem($cart, $productId, $quantity = 1)
    {
        $product = Product::find($productId);
        if (!$product || !$product->is_active) {
            throw ValidationException::withMessages(['product_id' => 'Product not found or inactive.']);
        }
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be at least 1.']);
        }
        if ($product->stock < $quantity) {
            throw ValidationException::withMessages(['quantity' => 'Not enough stock.']);
        }
        return $this->cartRepository->addItem($cart, $productId, $quantity);
    }

    public function updateItem($cart, $productId, $quantity)
    {
        $product = Product::find($productId);
        if (!$product || !$product->is_active) {
            throw ValidationException::withMessages(['product_id' => 'Product not found or inactive.']);
        }
        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be at least 1.']);
        }
        if ($product->stock < $quantity) {
            throw ValidationException::withMessages(['quantity' => 'Not enough stock.']);
        }
        return $this->cartRepository->updateItem($cart, $productId, $quantity);
    }

    public function removeItem($cart, $productId)
    {
        return $this->cartRepository->removeItem($cart, $productId);
    }

    public function clearCart($cart)
    {
        return $this->cartRepository->clearCart($cart);
    }

    public function mergeCarts($userCart, $guestCart)
    {
        return $this->cartRepository->mergeCarts($userCart, $guestCart);
    }
} 