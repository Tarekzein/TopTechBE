<?php

namespace Modules\Store\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Store\Services\WishlistService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class WishlistController extends Controller
{
    protected $wishlistService;

    public function __construct(WishlistService $wishlistService)
    {
        $this->wishlistService = $wishlistService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $guestToken = $request->cookie('guest_token');

            $wishlist = $this->wishlistService->getWishlist($userId, $guestToken);

            if (!$wishlist) {
                $guestToken = $this->wishlistService->generateGuestToken();
                $wishlist = $this->wishlistService->getWishlist(null, $guestToken);
            }

            $response = response()->json([
                'status' => 'success',
                'data' => [
                    'wishlist' => $wishlist,
                ],
            ]);

            if (!$userId && $guestToken) {
                $response->cookie('guest_token', $guestToken, 60 * 24 * 30); // 30 days
            }

            return $response;
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function addItem(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product_id' => 'required|integer|exists:products,id',
            ]);

            $userId = auth()->id();
            $guestToken = $request->cookie('guest_token');

            $wishlist = $this->wishlistService->getWishlist($userId, $guestToken);
            if (!$wishlist) {
                $guestToken = $this->wishlistService->generateGuestToken();
                $wishlist = $this->wishlistService->getWishlist(null, $guestToken);
            }

            $item = $this->wishlistService->addItem($wishlist, $request->product_id);

            $response = response()->json([
                'status' => 'success',
                'message' => 'Product added to wishlist',
                'data' => [
                    'item' => $item,
                ],
            ]);

            if (!$userId && $guestToken) {
                $response->cookie('guest_token', $guestToken, 60 * 24 * 30); // 30 days
            }

            return $response;
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function removeItem(Request $request, $productId): JsonResponse
    {
        try {
            $userId = auth()->id();
            $guestToken = $request->cookie('guest_token');

            $wishlist = $this->wishlistService->getWishlist($userId, $guestToken);
            if (!$wishlist) {
                throw new Exception('Wishlist not found', 404);
            }

            $this->wishlistService->removeItem($wishlist, $productId);

            return response()->json([
                'status' => 'success',
                'message' => 'Product removed from wishlist',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function clear(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $guestToken = $request->cookie('guest_token');

            $wishlist = $this->wishlistService->getWishlist($userId, $guestToken);
            if (!$wishlist) {
                throw new Exception('Wishlist not found', 404);
            }

            $this->wishlistService->clearWishlist($wishlist);

            return response()->json([
                'status' => 'success',
                'message' => 'Wishlist cleared successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    public function merge(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $guestToken = $request->cookie('guest_token');

            if (!$userId) {
                throw new Exception('User must be authenticated to merge wishlists', 401);
            }

            $userWishlist = $this->wishlistService->getWishlist($userId);
            $guestWishlist = $this->wishlistService->getWishlist(null, $guestToken);

            if (!$guestWishlist) {
                throw new Exception('Guest wishlist not found', 404);
            }

            $mergedWishlist = $this->wishlistService->mergeWishlists($userWishlist, $guestWishlist);

            return response()->json([
                'status' => 'success',
                'message' => 'Wishlists merged successfully',
                'data' => [
                    'wishlist' => $mergedWishlist,
                ],
            ])->cookie('guest_token', null, -1); // Remove guest token cookie
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }
} 