<?php

namespace Modules\Store\App\Services\Interfaces;

use Modules\Store\App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;

interface SettingServiceInterface
{
    /**
     * Get all settings
     *
     * @param bool $withValues
     * @param string|null $locale
     * @return Collection
     */
    public function getAllSettings(bool $withValues = false, ?string $locale = null): Collection;

    /**
     * Get all public settings
     *
     * @param bool $withValues
     * @param string|null $locale
     * @return Collection
     */
    public function getPublicSettings(bool $withValues = false, ?string $locale = null): Collection;

    /**
     * Get settings by group
     *
     * @param string $group
     * @param bool $withValues
     * @param string|null $locale
     * @return Collection
     */
    public function getSettingsByGroup(string $group, bool $withValues = false, ?string $locale = null): Collection;

    /**
     * Get a setting value
     *
     * @param string $key
     * @param string|null $locale
     * @return mixed
     */
    public function getSettingValue(string $key, ?string $locale = null);

    /**
     * Set a setting value
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $locale
     * @return bool
     */
    public function setSettingValue(string $key, $value, ?string $locale = null): bool;

    /**
     * Delete a setting value
     *
     * @param string $key
     * @param string|null $locale
     * @return bool
     */
    public function deleteSettingValue(string $key, ?string $locale = null): bool;

    /**
     * Create a new setting
     *
     * @param array $data
     * @return Setting|null
     */
    public function createSetting(array $data): ?Setting;

    /**
     * Update a setting
     *
     * @param string $key
     * @param array $data
     * @return bool
     */
    public function updateSetting(string $key, array $data): bool;

    /**
     * Delete a setting
     *
     * @param string $key
     * @return bool
     */
    public function deleteSetting(string $key): bool;

    /**
     * Validate a setting value
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function validateSettingValue(string $key, $value): bool;

    /**
     * Get all available setting groups
     *
     * @return array
     */
    public function getSettingGroups(): array;

    /**
     * Get all available setting types
     *
     * @return array
     */
    public function getSettingTypes(): array;
} 