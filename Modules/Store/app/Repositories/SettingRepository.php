<?php

namespace Modules\Store\App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Store\App\Models\Setting;
use Modules\Store\Interfaces\SettingRepositoryInterface;

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
        $settings = $this->model->where('group', $group)->get();
        
        Log::info('Getting settings by group:', [
            'group' => $group,
            'count' => $settings->count(),
            'settings' => $settings->toArray()
        ]);
        
        return $settings;
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

    /**
     * Check if a setting exists by key
     *
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool
    {
        $exists = $this->model->where('key', $key)->exists();
        
        Log::info('Checking if setting exists:', [
            'key' => $key,
            'exists' => $exists
        ]);
        
        return $exists;
    }

    /**
     * Update or create a setting
     *
     * @param string $key
     * @param mixed $value
     * @param string $name
     * @param string $description
     * @param string|null $group
     * @param bool $isPublic
     * @param string $type
     * @return Setting
     */
    public function updateOrCreate(
        string $key, 
        $value, 
        string $name, 
        string $description, 
        ?string $group = null, 
        bool $isPublic = false,
        string $type = 'string'
    ): Setting {
        Log::info('Updating or creating setting:', [
            'key' => $key,
            'value' => $value,
            'name' => $name,
            'description' => $description,
            'group' => $group,
            'type' => $type
        ]);

        try {
            $setting = $this->model->updateOrCreate(
                ['key' => $key],
                [
                    'name' => $name,
                    'description' => $description,
                    'group' => $group,
                    'is_public' => $isPublic,
                    'type' => $type,
                    'is_required' => false,
                    'validation_rules' => [],
                    'options' => [],
                    'display_order' => 0,
                ]
            );

            // Convert value to string based on type
            $stringValue = match ($type) {
                'boolean' => $value ? '1' : '0',
                'integer', 'float' => (string) ($value ?? '0'),
                'array', 'json' => json_encode($value ?? []),
                default => (string) ($value ?? ''),
            };

            $setting->setValue($stringValue);
            $this->clearCache($key);

            Log::info('Setting updated/created successfully:', [
                'setting' => $setting->toArray(),
                'value' => $stringValue
            ]);

            return $setting;
        } catch (\Exception $e) {
            Log::error('Failed to update/create setting:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Create a new setting
     *
     * @param string $key
     * @param mixed $value
     * @param string $name
     * @param string $description
     * @param string|null $group
     * @param bool $isPublic
     * @param string $type
     * @return Setting
     */
    public function create(
        string $key, 
        $value, 
        string $name, 
        string $description, 
        ?string $group = null, 
        bool $isPublic = false,
        string $type = 'string'
    ): Setting {
        Log::info('Creating setting:', [
            'key' => $key,
            'value' => $value,
            'name' => $name,
            'description' => $description,
            'group' => $group,
            'type' => $type
        ]);

        try {
            $setting = $this->model->create([
                'key' => $key,
                'name' => $name,
                'description' => $description,
                'group' => $group,
                'is_public' => $isPublic,
                'type' => $type,
                'is_required' => false,
                'validation_rules' => [],
                'options' => [],
                'display_order' => 0,
            ]);

            // Convert value to string based on type
            $stringValue = match ($type) {
                'boolean' => $value ? '1' : '0',
                'integer', 'float' => (string) ($value ?? '0'),
                'array', 'json' => json_encode($value ?? []),
                default => (string) ($value ?? ''),
            };

            $setting->setValue($stringValue);
            $this->clearCache($key);

            Log::info('Setting created successfully:', [
                'setting' => $setting->toArray(),
                'value' => $stringValue
            ]);

            return $setting;
        } catch (\Exception $e) {
            Log::error('Failed to create setting:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
