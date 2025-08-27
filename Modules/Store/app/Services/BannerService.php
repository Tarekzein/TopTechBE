<?php

namespace Modules\Store\Services;

use Modules\Store\Models\Banner;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class BannerService
{
    /**
     * Get banners by position with caching
     */
    public function getBannersByPosition(string $position, ?string $userRole = null, int $limit = 10): Collection
    {
        $cacheKey = "banners_{$position}_{$userRole}_{$limit}";
        
        return Cache::remember($cacheKey, 300, function () use ($position, $userRole, $limit) {
            return Banner::active()
                ->byPosition($position)
                ->currentlyValid()
                ->forUserRole($userRole)
                ->orderBy('sort_order')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get all banners with pagination
     */
    public function getAllBanners(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Banner::query();

        // Apply filters
        if (isset($filters['position'])) {
            $query->byPosition($filters['position']);
        }

        if (isset($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('title', 'like', "%{$filters['search']}%")
                  ->orWhere('subtitle', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('sort_order')->paginate($perPage);
    }

    /**
     * Create a new banner
     */
    public function createBanner(array $data): Banner
    {
        $banner = Banner::create($data);
        
        // Clear cache
        $this->clearBannerCache();
        
        return $banner;
    }

    /**
     * Update a banner
     */
    public function updateBanner(Banner $banner, array $data): Banner
    {
        $banner->update($data);
        
        // Clear cache
        $this->clearBannerCache();
        
        return $banner;
    }

    /**
     * Delete a banner
     */
    public function deleteBanner(Banner $banner): bool
    {
        $deleted = $banner->delete();
        
        if ($deleted) {
            // Clear cache
            $this->clearBannerCache();
        }
        
        return $deleted;
    }

    /**
     * Toggle banner active status
     */
    public function toggleBannerStatus(Banner $banner): Banner
    {
        $banner->update(['is_active' => !$banner->is_active]);
        
        // Clear cache
        $this->clearBannerCache();
        
        return $banner;
    }

    /**
     * Update banner sort order
     */
    public function updateSortOrder(array $bannerIds): bool
    {
        foreach ($bannerIds as $index => $bannerId) {
            Banner::where('id', $bannerId)->update(['sort_order' => $index + 1]);
        }
        
        // Clear cache
        $this->clearBannerCache();
        
        return true;
    }

    /**
     * Get banner statistics
     */
    public function getBannerStatistics(): array
    {
        $totalBanners = Banner::count();
        $activeBanners = Banner::active()->count();
        $inactiveBanners = $totalBanners - $activeBanners;
        
        $totalImpressions = Banner::sum('impressions');
        $totalClicks = Banner::sum('clicks');
        
        $avgClickThroughRate = $totalImpressions > 0 
            ? round(($totalClicks / $totalImpressions) * 100, 2) 
            : 0;

        return [
            'total_banners' => $totalBanners,
            'active_banners' => $activeBanners,
            'inactive_banners' => $inactiveBanners,
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'avg_click_through_rate' => $avgClickThroughRate,
        ];
    }

    /**
     * Get banner performance by position
     */
    public function getBannerPerformanceByPosition(): Collection
    {
        return Banner::selectRaw('
                position,
                COUNT(*) as total_banners,
                SUM(impressions) as total_impressions,
                SUM(clicks) as total_clicks,
                CASE 
                    WHEN SUM(impressions) > 0 
                    THEN ROUND((SUM(clicks) / SUM(impressions)) * 100, 2)
                    ELSE 0 
                END as click_through_rate
            ')
            ->groupBy('position')
            ->get();
    }

    /**
     * Get top performing banners
     */
    public function getTopPerformingBanners(int $limit = 10): Collection
    {
        return Banner::selectRaw('
                *,
                CASE 
                    WHEN impressions > 0 
                    THEN ROUND((clicks / impressions) * 100, 2)
                    ELSE 0 
                END as click_through_rate
            ')
            ->orderBy('click_through_rate', 'desc')
            ->orderBy('clicks', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Clear banner cache
     */
    private function clearBannerCache(): void
    {
        $positions = ['top', 'hero', 'sidebar', 'bottom', 'popup'];
        $userRoles = ['admin', 'vendor', 'customer', null];
        
        foreach ($positions as $position) {
            foreach ($userRoles as $role) {
                $roleKey = $role ?? 'guest';
                Cache::forget("banners_{$position}_{$roleKey}_10");
                Cache::forget("banners_{$position}_{$roleKey}_5");
            }
        }
    }

    /**
     * Get banner types
     */
    public function getBannerTypes(): array
    {
        return [
            'gradient' => 'Gradient Banner',
            'image' => 'Image Banner',
            'hero' => 'Hero Banner',
            'sidebar' => 'Sidebar Banner',
            'popup' => 'Popup Banner',
        ];
    }

    /**
     * Get banner positions
     */
    public function getBannerPositions(): array
    {
        return [
            'top' => 'Top of Page',
            'hero' => 'Hero Section',
            'sidebar' => 'Sidebar',
            'bottom' => 'Bottom of Page',
            'popup' => 'Popup/Modal',
        ];
    }

    /**
     * Validate banner data
     */
    public function validateBannerData(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Banner name is required';
        }

        if (empty($data['type'])) {
            $errors['type'] = 'Banner type is required';
        }

        if (empty($data['position'])) {
            $errors['position'] = 'Banner position is required';
        }

        // Validate date range
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            if (strtotime($data['start_date']) >= strtotime($data['end_date'])) {
                $errors['date_range'] = 'End date must be after start date';
            }
        }

        // Validate gradient colors
        if ($data['type'] === 'gradient') {
            if (empty($data['background_start_color']) || empty($data['background_end_color'])) {
                $errors['colors'] = 'Both start and end colors are required for gradient banners';
            }
        }

        return $errors;
    }
}
