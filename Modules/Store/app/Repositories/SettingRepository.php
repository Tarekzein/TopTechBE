<?php

namespace Modules\Store\App\Repositories;

use Modules\Store\App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;
use Modules\Store\App\Repositories\Interfaces\SettingRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class SettingRepository implements SettingRepositoryInterface
{
    /**
     * @var Setting
     */
    protected $model;

    /**
     * Cache key prefix for settings
     */
    const CACHE_KEY_PREFIX = 'store_settings_';

    /**
     * Cache TTL in seconds (1 hour)
     */
    const CACHE_TTL = 3600;

    /**
     * SettingRepository constructor.
     *
     * @param Setting $model
     */
    public function __construct(Setting $model)
    {
        $this->model = $model;
    }

    /**
     * Get all settings
     *
     * @return Collection
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Get all public settings
     *
     * @return Collection
     */
    public function getPublic(): Collection
    {
        return $this->model->public()->get();
    }

    /**
     * Get settings by group
     *
     * @param string $group
     * @return Collection
     */
    public function getByGroup(string $group): Collection
    {
        return $this->model->where('group', $group)->get();
    }

    /**
     * Find a setting by its key
     *
     * @param string $key
     * @return Setting|null
     */
    public function findByKey(string $key): ?Setting
    {
        return $this->model->where('key', $key)->first();
    }

    /**
     * Get a setting value
     *
     * @param string $key
     * @param string|null $locale
     * @return mixed
     */
    public function getValue(string $key, ?string $locale = null)
    {
        $cacheKey = $this->getCacheKey($key, $locale);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $locale) {
            $setting = $this->findByKey($key);
            if (!$setting) {
                return null;
            }

            return $setting->getValue($locale);
        });
    }

    /**
     * Set a setting value
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $locale
     * @return bool
     */
    public function setValue(string $key, $value, ?string $locale = null): bool
    {
        $setting = $this->findByKey($key);
        if (!$setting) {
            return false;
        }

        $result = $setting->setValue($value, $locale);
        
        if ($result) {
            $this->clearCache($key, $locale);
        }

        return $result;
    }

    /**
     * Delete a setting value
     *
     * @param string $key
     * @param string|null $locale
     * @return bool
     */
    public function deleteValue(string $key, ?string $locale = null): bool
    {
        $setting = $this->findByKey($key);
        if (!$setting) {
            return false;
        }

        $result = $setting->values()
            ->when($locale, function ($query) use ($locale) {
                return $query->where('locale', $locale);
            })
            ->delete();

        if ($result) {
            $this->clearCache($key, $locale);
        }

        return $result;
    }

    /**
     * Get all settings with their values
     *
     * @param string|null $locale
     * @return Collection
     */
    public function getAllWithValues(?string $locale = null): Collection
    {
        $cacheKey = $this->getCacheKey('all', $locale);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($locale) {
            $settings = $this->model->with(['values' => function ($query) use ($locale) {
                if ($locale) {
                    $query->where('locale', $locale);
                }
            }])->get();

            return $settings->map(function ($setting) use ($locale) {
                $setting->value = $setting->getValue($locale);
                return $setting;
            });
        });
    }

    /**
     * Get cache key for a setting
     *
     * @param string $key
     * @param string|null $locale
     * @return string
     */
    protected function getCacheKey(string $key, ?string $locale = null): string
    {
        return self::CACHE_KEY_PREFIX . $key . ($locale ? '_' . $locale : '');
    }

    /**
     * Clear cache for a setting
     *
     * @param string $key
     * @param string|null $locale
     * @return void
     */
    protected function clearCache(string $key, ?string $locale = null): void
    {
        // Clear specific setting cache
        Cache::forget($this->getCacheKey($key, $locale));
        
        // Clear all settings cache
        Cache::forget($this->getCacheKey('all', $locale));
    }
} 