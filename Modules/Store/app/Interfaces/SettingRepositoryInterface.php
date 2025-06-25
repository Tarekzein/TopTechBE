<?php

namespace  Modules\Store\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Modules\Store\App\Models\Setting;

interface SettingRepositoryInterface
{
    /**
     * Get all settings
     *
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Get all public settings
     *
     * @return Collection
     */
    public function getPublic(): Collection;

    /**
     * Get settings by group
     *
     * @param string $group
     * @return Collection
     */
    public function getByGroup(string $group): Collection;

    /**
     * Find a setting by its key
     *
     * @param string $key
     * @return Setting|null
     */
    public function findByKey(string $key): ?Setting;

    /**
     * Get a setting value
     *
     * @param string $key
     * @param string|null $locale
     * @return mixed
     */
    public function getValue(string $key, ?string $locale = null);

    /**
     * Set a setting value
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $locale
     * @return bool
     */
    public function setValue(string $key, $value, ?string $locale = null): bool;

    /**
     * Delete a setting value
     *
     * @param string $key
     * @param string|null $locale
     * @return bool
     */
    public function deleteValue(string $key, ?string $locale = null): bool;

    /**
     * Get all settings with their values
     *
     * @param string|null $locale
     * @return Collection
     */
    public function getAllWithValues(?string $locale = null): Collection;

    /**
     * Get all settings grouped by their group key.
     *
     * @param string|null $locale
     * @return \Illuminate\Support\Collection
     */
    public function getAllGroupedByGroup(?string $locale = null): \Illuminate\Support\Collection;

    /**
     * Check if a setting exists by key
     *
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool;
}
