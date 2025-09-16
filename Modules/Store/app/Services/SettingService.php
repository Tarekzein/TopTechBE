<?php

namespace Modules\Store\App\Services;

use Modules\Store\Interfaces\SettingRepositoryInterface;
use Modules\Store\Interfaces\SettingServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\Store\App\Models\Setting;

class SettingService implements SettingServiceInterface
{
    /**
     * @var SettingRepositoryInterface
     */
    protected $settingRepository;

    /**
     * Available setting types
     */
    const TYPES = [
        'text' => 'Text',
        'textarea' => 'Text Area',
        'number' => 'Number',
        'boolean' => 'Boolean',
        'select' => 'Select',
        'radio' => 'Radio',
        'checkbox' => 'Checkbox',
        'file' => 'File',
        'image' => 'Image',
        'color' => 'Color',
        'date' => 'Date',
        'datetime' => 'Date Time',
        'time' => 'Time',
        'json' => 'JSON',
    ];

    /**
     * Available setting groups
     */
    const GROUPS = [
        'general' => 'General',
        'store' => 'Store',
        'currency' => 'Currency',
        'payment' => 'Payment',
        'shipping' => 'Shipping',
        'tax' => 'Tax',
        'email' => 'Email',
        'social' => 'Social Media',
        'seo' => 'SEO',
        'security' => 'Security',
        'maintenance' => 'Maintenance',
    ];

    /**
     * SettingService constructor.
     *
     * @param SettingRepositoryInterface $settingRepository
     */
    public function __construct(SettingRepositoryInterface $settingRepository)
    {
        $this->settingRepository = $settingRepository;
    }

    /**
     * Get all settings
     *
     * @param bool $withValues
     * @param string|null $locale
     * @return Collection
     */
    public function getAllSettings(bool $withValues = false, ?string $locale = null): Collection
    {
        if ($withValues) {
            return $this->settingRepository->getAllWithValues($locale);
        }
        return $this->settingRepository->all();
    }

    /**
     * Get all public settings
     *
     * @param bool $withValues
     * @param string|null $locale
     * @return Collection
     */
    public function getPublicSettings(bool $withValues = false, ?string $locale = null): Collection
    {
        $settings = $this->settingRepository->getPublic();

        if ($withValues) {
            return $settings->map(function ($setting) use ($locale) {
                $setting->value = $setting->getValue($locale);
                return $setting;
            });
        }

        return $settings;
    }

    /**
     * Get settings by group
     *
     * @param string $group
     * @param bool $withValues
     * @param string|null $locale
     * @return Collection
     */
    public function getSettingsByGroup(string $group, bool $withValues = false, ?string $locale = null): Collection
    {
        $settings = $this->settingRepository->getByGroup($group);

        if ($withValues) {
            return $settings->map(function ($setting) use ($locale) {
                $setting->value = $setting->getValue($locale);
                return $setting;
            });
        }

        return $settings;
    }

    /**
     * Get a setting value
     *
     * @param string $key
     * @param string|null $locale
     * @return mixed
     */
    public function getSettingValue(string $key, ?string $locale = null)
    {
        return $this->settingRepository->getValue($key, $locale);
    }

    /**
     * Set a setting value
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $locale
     * @return bool
     */
    public function setSettingValue(string $key, $value, ?string $locale = null): bool
    {
        $setting = $this->settingRepository->findByKey($key);
        if (!$setting) {
            return false;
        }

        // Validate the value
        if (!$this->validateSettingValue($key, $value)) {
            return false;
        }

        return $this->settingRepository->setValue($key, $value, $locale);
    }

    /**
     * Delete a setting value
     *
     * @param string $key
     * @param string|null $locale
     * @return bool
     */
    public function deleteSettingValue(string $key, ?string $locale = null): bool
    {
        return $this->settingRepository->deleteValue($key, $locale);
    }

    /**
     * Create a new setting
     *
     * @param array $data
     * @return Setting|null
     */
    public function createSetting(array $data): ?Setting
    {
        try {
            // Validate required fields
            $validator = Validator::make($data, [
                'key' => 'required|string|unique:settings,key',
                'name' => 'required|string',
                'type' => 'required|string|in:' . implode(',', array_keys(self::TYPES)),
                'group' => 'required|string|in:' . implode(',', array_keys(self::GROUPS)),
                'is_public' => 'boolean',
                'is_required' => 'boolean',
                'validation_rules' => 'nullable|array',
                'options' => 'nullable|array',
                'display_order' => 'integer',
            ]);

            if ($validator->fails()) {
                Log::error('Setting validation failed', [
                    'data' => $data,
                    'errors' => $validator->errors()->toArray()
                ]);
                return null;
            }

            // Create setting
            $setting = new Setting($data);
            $setting->save();

            return $setting;

        } catch (\Exception $e) {
            Log::error('Error creating setting', [
                'data' => $data,
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Update a setting
     *
     * @param string $key
     * @param array $data
     * @return bool
     */
    public function updateSetting(string $key, array $data): bool
    {
        try {
            $setting = $this->settingRepository->findByKey($key);
            if (!$setting) {
                return false;
            }

            // Don't allow changing the key
            unset($data['key']);

            // Validate data
            $validator = Validator::make($data, [
                'name' => 'string',
                'type' => 'string|in:' . implode(',', array_keys(self::TYPES)),
                'group' => 'string|in:' . implode(',', array_keys(self::GROUPS)),
                'is_public' => 'boolean',
                'is_required' => 'boolean',
                'validation_rules' => 'nullable|array',
                'options' => 'nullable|array',
                'display_order' => 'integer',
            ]);

            if ($validator->fails()) {
                Log::error('Setting update validation failed', [
                    'key' => $key,
                    'data' => $data,
                    'errors' => $validator->errors()->toArray()
                ]);
                return false;
            }

            return $setting->update($data);

        } catch (\Exception $e) {
            Log::error('Error updating setting', [
                'key' => $key,
                'data' => $data,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete a setting
     *
     * @param string $key
     * @return bool
     */
    public function deleteSetting(string $key): bool
    {
        try {
            $setting = $this->settingRepository->findByKey($key);
            if (!$setting) {
                return false;
            }

            // Don't allow deleting required settings
            if ($setting->is_required) {
                return false;
            }

            return $setting->delete();

        } catch (\Exception $e) {
            Log::error('Error deleting setting', [
                'key' => $key,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Validate a setting value
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function validateSettingValue(string $key, $value): bool
    {
        try {
            $setting = $this->settingRepository->findByKey($key);
            if (!$setting) {
                return false;
            }

            // If setting is required and value is empty
            if ($setting->is_required && empty($value)) {
                return false;
            }

            // If no validation rules, return true
            if (empty($setting->validation_rules)) {
                return true;
            }

            // Validate value against rules
            $validator = Validator::make(['value' => $value], [
                'value' => $setting->validation_rules
            ]);

            return !$validator->fails();

        } catch (\Exception $e) {
            Log::error('Error validating setting value', [
                'key' => $key,
                'value' => $value,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get all available setting groups
     *
     * @return array
     */
    public function getSettingGroups(): array
    {
        return self::GROUPS;
    }

    /**
     * Get all available setting types
     *
     * @return array
     */
    public function getSettingTypes(): array
    {
        return self::TYPES;
    }
    public function bulkUpdate(array $settings): array
{
    $errors = [];
    $updated = [];

    Log::info('=== SERVICE BULK UPDATE START ===');
    Log::info('SettingService->bulkUpdate called with:', ['settings_count' => count($settings)]);
    Log::info('Repository class:', [get_class($this->settingRepository)]);

    // Check if repository is properly injected
    if (!$this->settingRepository) {
        Log::error('SettingRepository not injected properly');
        throw new \Exception('Repository not available');
    }

    foreach ($settings as $index => $setting) {
        Log::info("=== Processing setting #{$index} ===");
        Log::info('Setting data:', $setting);

        $key = $setting['key'] ?? null;
        $value = $setting['value'] ?? null;

        if (!$key) {
            $error = ['key' => null, 'error' => 'Key is required'];
            Log::warning('Setting missing key:', $error);
            $errors[] = $error;
            continue;
        }

        Log::info("Looking for setting with key: {$key}");

        try {
            // Check if findByKey method exists
            if (!method_exists($this->settingRepository, 'findByKey')) {
                Log::error('findByKey method does not exist on repository');
                throw new \Exception('Repository method findByKey not found');
            }

            $model = $this->settingRepository->findByKey($key);
            Log::info('Repository findByKey result:', [
                'found' => $model ? 'yes' : 'no',
                'model_class' => $model ? get_class($model) : null,
                'model_data' => $model ? $model->toArray() : null
            ]);

            if (!$model) {
                $error = ['key' => $key, 'error' => 'Setting not found'];
                Log::warning('Setting not found:', $error);
                $errors[] = $error;
                continue;
            }

            Log::info("Attempting to update setting {$key} with value:", ['value' => $value, 'type' => gettype($value)]);

            // Check if setValue method exists
            if (!method_exists($this->settingRepository, 'setValue')) {
                Log::error('setValue method does not exist on repository');
                throw new \Exception('Repository method setValue not found');
            }

            $success = $this->settingRepository->setValue($key, $value);
            Log::info("setValue returned:", ['success' => $success, 'type' => gettype($success)]);

            if ($success) {
                $updatedItem = [
                    'key' => $key,
                    'value' => $value,
                    'updated_at' => now()
                ];
                $updated[] = $updatedItem;
                Log::info('Setting updated successfully:', $updatedItem);
            } else {
                $error = ['key' => $key, 'error' => 'setValue returned false - update failed'];
                Log::error('setValue returned false:', $error);
                $errors[] = $error;
            }

        } catch (\Throwable $e) {
            Log::error("=== Exception updating setting {$key} ===");
            Log::error('Exception class: ' . get_class($e));
            Log::error('Exception message: ' . $e->getMessage());
            Log::error('Exception file: ' . $e->getFile() . ':' . $e->getLine());
            Log::error('Exception trace: ' . $e->getTraceAsString());

            $error = ['key' => $key, 'error' => $e->getMessage()];
            $errors[] = $error;
        }
    }

    $result = [
        'updated' => $updated,
        'errors' => $errors,
    ];

    Log::info('=== SERVICE BULK UPDATE RESULT ===');
    Log::info('Final result:', [
        'updated_count' => count($updated),
        'errors_count' => count($errors),
        'updated' => $updated,
        'errors' => $errors
    ]);
    Log::info('=== SERVICE BULK UPDATE END ===');

    return $result;
}


    /**
     * Get all settings grouped by their group key.
     *
     * @param bool $withValues
     * @param string|null $locale
     * @return Collection
     */
    public function getAllSettingsGrouped(bool $withValues = true, ?string $locale = null): Collection
    {
        return $this->settingRepository->getAllGroupedByGroup($locale);
    }
}
