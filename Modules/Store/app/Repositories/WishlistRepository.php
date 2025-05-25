<?php

namespace Modules\Store\Repositories;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Store\Models\Wishlist;

class WishlistRepository
{
    public function getWishlist($userId = null, $guestToken = null)
    {
        try {
            if ($userId) {
                return Wishlist::with('items.product')->where('user_id', $userId)->first();
            } elseif ($guestToken) {
                return Wishlist::with('items.product')->where('guest_token', $guestToken)->first();
            }
            return null;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function createWishlist($userId = null, $guestToken = null)
    {
        try {
            return Wishlist::create([
                'user_id' => $userId,
                'guest_token' => $guestToken,
            ]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function addItem($wishlist, $productId)
    {
        DB::beginTransaction();
        try {
            $item = $wishlist->items()->where('product_id', $productId)->first();
            if (!$item) {
                $item = $wishlist->items()->create([
                    'product_id' => $productId,
                ]);
            }
            DB::commit();
            return $item;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function removeItem($wishlist, $productId)
    {
        DB::beginTransaction();
        try {
            $item = $wishlist->items()->where('product_id', $productId)->first();
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

    public function clearWishlist($wishlist)
    {
        DB::beginTransaction();
        try {
            $wishlist->items()->delete();
            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function mergeWishlists($userWishlist, $guestWishlist)
    {
        DB::beginTransaction();
        try {
            foreach ($guestWishlist->items as $guestItem) {
                $userItem = $userWishlist->items()->where('product_id', $guestItem->product_id)->first();
                if (!$userItem) {
                    $userWishlist->items()->create([
                        'product_id' => $guestItem->product_id,
                    ]);
                }
            }
            $guestWishlist->delete();
            DB::commit();
            return $userWishlist->fresh('items.product');
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
