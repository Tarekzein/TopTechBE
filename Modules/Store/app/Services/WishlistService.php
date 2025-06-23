<?php

namespace Modules\Store\Services;

use Modules\Store\Repositories\WishlistRepository;
use Modules\Store\Repositories\ProductRepository;
use Illuminate\Support\Str;
use Exception;

class WishlistService
{
    protected $wishlistRepository;
    protected $productRepository;

    public function __construct(
        WishlistRepository $wishlistRepository,
        ProductRepository $productRepository
    ) {
        $this->wishlistRepository = $wishlistRepository;
        $this->productRepository = $productRepository;
    }

    public function getWishlist($userId = null, $guestToken = null)
    {
        try {
            $wishlist = $this->wishlistRepository->getWishlist($userId, $guestToken);
            if (!$wishlist && ($userId || $guestToken)) {
                $wishlist = $this->wishlistRepository->createWishlist($userId, $guestToken);
            }
            return $wishlist;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function addItem($wishlist, $productId)
    {
        try {
            // Validate product exists
            $product = $this->productRepository->findById($productId);
            if (!$product) {
                throw new Exception('Product not found', 404);
            }

            return $this->wishlistRepository->addItem($wishlist, $productId);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function removeItem($wishlist, $productId)
    {
        try {
            return $this->wishlistRepository->removeItem($wishlist, $productId);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function clearWishlist($wishlist)
    {
        try {
            return $this->wishlistRepository->clearWishlist($wishlist);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function mergeWishlists($userWishlist, $guestWishlist)
    {
        try {
            if (!$userWishlist || !$guestWishlist) {
                throw new Exception('Both wishlists must exist to merge', 400);
            }

            return $this->wishlistRepository->mergeWishlists($userWishlist, $guestWishlist);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function generateGuestToken()
    {
        return Str::random(32);
    }
}
