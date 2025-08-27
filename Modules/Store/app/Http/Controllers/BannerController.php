<?php

namespace Modules\Store\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Store\Services\BannerService;
use Modules\Store\Models\Banner;
use Illuminate\Support\Facades\Auth;

class BannerController extends Controller
{
    protected $bannerService;

    public function __construct(BannerService $bannerService)
    {
        $this->bannerService = $bannerService;
    }

    /**
     * Get banners by position (public endpoint)
     */
    public function getBannersByPosition(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'position' => 'required|string',
                'limit' => 'nullable|integer|min:1|max:50',
            ]);

            $position = $request->position;
            $limit = $request->get('limit', 10);
            $userRole = Auth::user()?->roles->first()?->name;

            $banners = $this->bannerService->getBannersByPosition($position, $userRole, $limit);

            return response()->json([
                'success' => true,
                'data' => $banners
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get banners',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all banners (admin only)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['position', 'type', 'is_active', 'search']);
            $perPage = $request->get('per_page', 20);

            $banners = $this->bannerService->getAllBanners($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => $banners
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get banners',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get banner by ID
     */
    public function show(Banner $banner): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $banner
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get banner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new banner
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|string|in:gradient,image,hero,sidebar,popup',
                'position' => 'required|string|in:top,hero,sidebar,bottom,popup',
                'title' => 'nullable|string|max:255',
                'subtitle' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'button_text' => 'nullable|string|max:255',
                'button_url' => 'nullable|url|max:255',
                'image_url' => 'nullable|url|max:255',
                'background_start_color' => 'nullable|string|max:7',
                'background_end_color' => 'nullable|string|max:7',
                'text_color' => 'nullable|string|max:7',
                'settings' => 'nullable|array',
                'is_active' => 'boolean',
                'sort_order' => 'nullable|integer|min:0',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after:start_date',
                'target_audience' => 'nullable|array',
            ]);

            $data = $request->all();
            
            // Validate banner data
            $errors = $this->bannerService->validateBannerData($data);
            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ], 422);
            }

            $banner = $this->bannerService->createBanner($data);

            return response()->json([
                'success' => true,
                'message' => 'Banner created successfully',
                'data' => $banner
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create banner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a banner
     */
    public function update(Request $request, Banner $banner): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'type' => 'sometimes|required|string|in:gradient,image,hero,sidebar,popup',
                'position' => 'sometimes|required|string|in:top,hero,sidebar,bottom,popup',
                'title' => 'nullable|string|max:255',
                'subtitle' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'button_text' => 'nullable|string|max:255',
                'button_url' => 'nullable|url|max:255',
                'image_url' => 'nullable|url|max:255',
                'background_start_color' => 'nullable|string|max:7',
                'background_end_color' => 'nullable|string|max:7',
                'text_color' => 'nullable|string|max:7',
                'settings' => 'nullable|array',
                'is_active' => 'boolean',
                'sort_order' => 'nullable|integer|min:0',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after:start_date',
                'target_audience' => 'nullable|array',
            ]);

            $data = $request->all();
            
            // Validate banner data
            $errors = $this->bannerService->validateBannerData($data);
            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ], 422);
            }

            $banner = $this->bannerService->updateBanner($banner, $data);

            return response()->json([
                'success' => true,
                'message' => 'Banner updated successfully',
                'data' => $banner
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update banner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a banner
     */
    public function destroy(Banner $banner): JsonResponse
    {
        try {
            $deleted = $this->bannerService->deleteBanner($banner);

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Banner deleted successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete banner'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete banner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle banner status
     */
    public function toggleStatus(Banner $banner): JsonResponse
    {
        try {
            $banner = $this->bannerService->toggleBannerStatus($banner);

            return response()->json([
                'success' => true,
                'message' => 'Banner status updated successfully',
                'data' => $banner
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update banner status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update banner sort order
     */
    public function updateSortOrder(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'banner_ids' => 'required|array',
                'banner_ids.*' => 'integer|exists:banners,id'
            ]);

            $success = $this->bannerService->updateSortOrder($request->banner_ids);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Banner order updated successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update banner order'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update banner order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get banner statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->bannerService->getBannerStatistics();
            $performanceByPosition = $this->bannerService->getBannerPerformanceByPosition();
            $topPerforming = $this->bannerService->getTopPerformingBanners(5);

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $stats,
                    'performance_by_position' => $performanceByPosition,
                    'top_performing' => $topPerforming,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get banner statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get banner types and positions
     */
    public function getTypesAndPositions(): JsonResponse
    {
        try {
            $types = $this->bannerService->getBannerTypes();
            $positions = $this->bannerService->getBannerPositions();

            return response()->json([
                'success' => true,
                'data' => [
                    'types' => $types,
                    'positions' => $positions,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get banner types and positions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Record banner impression
     */
    public function recordImpression(Banner $banner): JsonResponse
    {
        try {
            $banner->incrementImpressions();

            return response()->json([
                'success' => true,
                'message' => 'Impression recorded successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record impression',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Record banner click
     */
    public function recordClick(Banner $banner): JsonResponse
    {
        try {
            $banner->incrementClicks();

            return response()->json([
                'success' => true,
                'message' => 'Click recorded successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record click',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
