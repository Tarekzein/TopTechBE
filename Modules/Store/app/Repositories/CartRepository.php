<?php

namespace Modules\Store\Repositories;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Store\Models\Cart;

class CartRepository
{
    public function getCart($userId = null, $guestToken = null)
    {
        try {
            if ($userId) {
                return Cart::with(['items.product','items.productVariation'])->where('user_id', $userId)->first();
            } elseif ($guestToken) {
                return Cart::with(['items.product','items.productVariation'])->where('guest_token', $guestToken)->first();
            }
            return null;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createCart($userId = null, $guestToken = null)
    {
        try {
            return Cart::create([
                'user_id' => $userId,
                'guest_token' => $guestToken,
            ]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function addItem($cart, $productId, $quantity = 1, $productVariationId=null)
    {
        DB::beginTransaction();
        try {
            $item = $cart->items()->where('product_id', $productId)->first();
            if ($item) {
                $item->quantity += $quantity;
                $item->save();
            } else {
                $item = $cart->items()->create([
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'product_variation_id' => $productVariationId,
                ]);
            }
            DB::commit();
            return $item;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateItem($cart, $productId, $quantity)
    {
        DB::beginTransaction();
        try {
            $item = $cart->items()->where('product_id', $productId)->first();
            if ($item) {
                $item->quantity = $quantity;
                $item->save();
            }
            DB::commit();
            return $item;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function removeItem($cart, $productId)
    {
        DB::beginTransaction();
        try {
            $item = $cart->items()->where('product_id', $productId)->first();
            if ($item) {
                $item->delete();
            }
            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function clearCart($cart)
    {
        DB::beginTransaction();
        try {
            $cart->items()->delete();
            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function mergeCarts($userCart, $guestCart)
    {
        DB::beginTransaction();
        try {
            foreach ($guestCart->items as $guestItem) {
                $userItem = $userCart->items()->where('product_id', $guestItem->product_id)->first();
                if ($userItem) {
                    $userItem->quantity += $guestItem->quantity;
                    $userItem->save();
                } else {
                    $userCart->items()->create([
                        'product_id' => $guestItem->product_id,
                        'quantity' => $guestItem->quantity,
                    ]);
                }
                $guestItem->delete();
            }
            $guestCart->delete();
            DB::commit();
            return $userCart->fresh('items.product');
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
