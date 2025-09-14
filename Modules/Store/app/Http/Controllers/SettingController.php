<?php

namespace Modules\Store\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Store\App\Services\SettingService;
use Modules\Store\Interfaces\SettingServiceInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Modules\Store\App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Exception;

class SettingController extends Controller
{
   protected $settingService;

    public function __construct(SettingServiceInterface $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Get all settings
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $locale = $request->input('locale');
            $settings = $this->settingService->getAllSettings(true, $locale);

            return response()->json([
                'status' => 'success',
                'data'   => $settings,
            ]);
        } catch (Exception $e) {
            return $this->logErrorResponse($e, 'Failed to fetch settings', 500);
        }
    }

    /**
     * Get available setting groups with their settings
     */
    public function getGroups(Request $request): JsonResponse
    {
        try {
            $locale = $request->input('locale');
            $groupedSettings = $this->settingService->getAllSettingsGrouped(true, $locale);

            return response()->json([
                'status' => 'success',
                'data'   => $groupedSettings,
            ]);
        } catch (Exception $e) {
            return $this->logErrorResponse($e, 'Failed to fetch setting groups', 500);
        }
    }

    /**
     * Get public settings
     */
    public function public(Request $request): JsonResponse
    {
        try {
            $withValues = $request->boolean('with_values', false);
            $locale = $request->input('locale');
            $settings = $this->settingService->getPublicSettings($withValues, $locale);

            return response()->json([
                'status' => 'success',
                'data'   => $settings,
            ]);
        } catch (Exception $e) {
            return $this->logErrorResponse($e, 'Failed to fetch public settings', 500);
        }
    }

    /**
     * Get settings by group
     */
    public function byGroup(Request $request, string $group): JsonResponse
    {
        try {
            $withValues = $request->boolean('with_values', false);
            $locale = $request->input('locale');
            $settings = $this->settingService->getSettingsByGroup($group, $withValues, $locale);

            return response()->json([
                'status' => 'success',
                'data'   => $settings,
            ]);
        } catch (Exception $e) {
            return $this->logErrorResponse($e, 'Failed to fetch settings by group', 500);
        }
    }

    /**
     * Get a setting value
     */
    public function getValue(Request $request, string $key): JsonResponse
    {
        try {
            $locale = $request->input('locale');
            $value = $this->settingService->getSettingValue($key, $locale);

            if ($value === null) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Setting123 not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data'   => $value,
            ]);
        } catch (Exception $e) {
            return $this->logErrorResponse($e, 'Failed to fetch setting value', 500);
        }
    }

    /**
     * Set a setting value
     */
    public function setValue(Request $request, string $key): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'value'  => 'required',
                'locale' => 'nullable|string|max:10',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $success = $this->settingService->setSettingValue(
                $key,
                $request->input('value'),
                $request->input('locale')
            );

            if (!$success) {
                return response()->json([
                    'status'  => 'error',
                    'message' => '123 value',
                ], 400);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Setting value updated successfully',
            ]);
        } catch (Exception $e) {
            return $this->logErrorResponse($e, 'Faile5666d to update setting value', 500);
        }
    }

    /**
     * Create a new setting
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'key'              => 'required|string|unique:settings,key',
                'name'             => 'required|string',
                'type'             => 'required|string|in:' . implode(',', array_keys(SettingService::TYPES)),
                'group'            => 'required|string|in:' . implode(',', array_keys(SettingService::GROUPS)),
                'value'            => 'required',
                'is_public'        => 'boolean',
                'is_required'      => 'boolean',
                'validation_rules' => 'nullable|array',
                'options'          => 'nullable|array',
                'display_order'    => 'integer',
                'locale'           => 'nullable|string|max:10',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $setting = $this->settingService->createSetting($request->all());

            if (!$setting) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Failed to create setting',
                ], 400);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Setting created successfully',
                'data'    => $setting,
            ], 201);
        } catch (Exception $e) {
            return $this->logErrorResponse($e, 'Failed to create setting', 500);
        }
    }

    /**
     * Update a setting
     */
    public function update(Request $request, string $key): JsonResponse
{
    try {
        $validator = Validator::make($request->all(), [
            'name'             => 'sometimes|string',
            'type'             => 'sometimes|string',
            'group'            => 'sometimes|string',
            'value'            => 'nullable',
            'is_public'        => 'sometimes|boolean',
            'is_required'      => 'sometimes|boolean',
            'validation_rules' => 'nullable|array',
            'options'          => 'nullable|array',
            'display_order'    => 'sometimes|integer',
            'locale'           => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // ابحث عن الـ setting بالـ key
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Setting0050 not found'.$key,
            ], 404);
        }

        // حدث البيانات
        $data = $request->only([
            'name', 'type', 'group', 'value',
            'is_public', 'is_required', 'validation_rules',
            'options', 'display_order', 'locale'
        ]);

        $setting->update($data);

        return response()->json([
            'status'  => 'success',
            'message' => 'Setting updated successfully',
            'data'    => $setting,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Failed to update setting',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Delete a setting
     */
    public function destroy(string $key): JsonResponse
    {
        try {
            $success = $this->settingService->deleteSetting($key);

            if (!$success) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Failed to delete setting',
                ], 400);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Setting deleted successfully',
            ]);
        } catch (Exception $e) {
            return $this->logErrorResponse($e, 'Failed to delete setting', 500);
        }
    }

    /**
     * Get available setting types
     */
    public function types(): JsonResponse
    {
        try {
            return response()->json([
                'status' => 'success',
                'data'   => $this->settingService->getSettingTypes(),
            ]);
        } catch (Exception $e) {
            return $this->logErrorResponse($e, 'Failed to fetch setting types', 500);
        }
    }

    /**
     * Get available setting groups
     */
    public function groups(): JsonResponse
    {
        try {
            return response()->json([
                'status' => 'success',
                'data'   => $this->settingService->getSettingGroups(),
            ]);
        } catch (Exception $e) {
            return $this->logErrorResponse($e, 'Failed to fetch setting groups', 500);
        }
    }

    /**
     * Show a single setting with values
     */
    public function show(string $key): JsonResponse
    {
        $setting = Setting::with('values')->where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Setting not 152found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $setting,
        ]);
    }

    /**
     * Bulk update multiple settings at once
     */
 public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            Log::info('Bulk update request received:', $request->all());
            
            $validator = Validator::make($request->all(), [
                'settings' => 'required|array',
                'settings.*.key' => 'required|string',
                'settings.*.value' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $result = $this->settingService->bulkUpdate($request->input('settings', []));

            if (!empty($result['errors'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Some settings failed to update',
                    'errors' => $result['errors'],
                    'updated' => $result['updated'],
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Settings updated successfully',
                'data' => $result['updated'],
            ]);

        } catch (Exception $e) {
            return $this->logErrorResponse($e, 'Failed to bulk update settings', 500);
        }
    }


public function test(Request $request): JsonResponse
{
    try {
        Log::info('=== TESTING REPOSITORY METHODS ===');

        // Test 1: Check if service is available
        $serviceAvailable = $this->settingService ? 'yes' : 'no';
        Log::info('Service available:', ['available' => $serviceAvailable]);
        
        if (!$this->settingService) {
            return response()->json([
                'status' => 'error',
                'message' => 'SettingService not injected',
            ], 500);
        }

        Log::info('Service class:', [get_class($this->settingService)]);

        // Test 2: Get all settings
        Log::info('Testing getAllSettings...');
        $allSettings = $this->settingService->getAllSettings(true);
        Log::info('All settings count:', ['count' => $allSettings->count()]);
        
        if ($allSettings->count() === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'No settings found in database',
            ], 404);
        }

        $firstSetting = $allSettings->first();
        Log::info('First setting:', $firstSetting->toArray());

        // Test 3: Try to find this setting by key using service method
        $key = $firstSetting->key;
        Log::info("Testing service findByKey with key: {$key}");
        
        // Use reflection to access protected repository property
        $reflection = new \ReflectionClass($this->settingService);
        $repositoryProperty = $reflection->getProperty('settingRepository');
        $repositoryProperty->setAccessible(true);
        $repository = $repositoryProperty->getValue($this->settingService);
        
        if (!$repository) {
            Log::error('Repository not found in service');
            return response()->json([
                'status' => 'error',
                'message' => 'Repository not accessible',
            ], 500);
        }

        Log::info('Repository class:', [get_class($repository)]);
        
        // Test direct repository findByKey
        Log::info("Testing repository->findByKey with key: {$key}");
        $foundSetting = $repository->findByKey($key);
        Log::info('Repository findByKey result:', $foundSetting ? $foundSetting->toArray() : 'null');

        if (!$foundSetting) {
            return response()->json([
                'status' => 'error',
                'message' => 'Repository findByKey failed for existing key',
                'debug' => [
                    'key_tested' => $key,
                    'repository_class' => get_class($repository)
                ]
            ], 500);
        }

        // Test 4: Try setValue
        Log::info('Testing setValue...');
        $originalValue = $foundSetting->getValue();
        $testValue = 'test_value_' . time();
        
        Log::info('Original value:', ['value' => $originalValue]);
        Log::info('Test value:', ['value' => $testValue]);
        
        $result = $repository->setValue($key, $testValue);
        Log::info('setValue result:', ['result' => $result, 'type' => gettype($result)]);

        if (!$result) {
            return response()->json([
                'status' => 'error',
                'message' => 'setValue returned false',
                'debug' => [
                    'key' => $key,
                    'value' => $testValue,
                    'original_value' => $originalValue
                ]
            ], 500);
        }

        // Test 5: Verify the value was set
        $updatedSetting = $repository->findByKey($key);
        $newValue = $updatedSetting->getValue();
        Log::info('New value after update:', ['value' => $newValue]);

        // Test 6: Test bulk update with one setting
        Log::info('Testing bulk update with single setting...');
        $bulkResult = $this->settingService->bulkUpdate([
            ['key' => $key, 'value' => 'bulk_test_' . time()]
        ]);
        Log::info('Bulk update result:', $bulkResult);

        // Test 7: Restore original value
        $repository->setValue($key, $originalValue);
        Log::info('Restored original value');

        return response()->json([
            'status' => 'success',
            'message' => 'All tests passed',
            'data' => [
                'settings_count' => $allSettings->count(),
                'first_setting_key' => $key,
                'repository_class' => get_class($repository),
                'service_class' => get_class($this->settingService),
                'setValue_works' => $result,
                'bulk_update_result' => $bulkResult
            ]
        ]);

    } catch (Exception $e) {
        Log::error('Test failed:', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Test failed',
            'error' => $e->getMessage(),
            'debug' => [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ], 500);
    }
}

    /**
     * Helper to log errors and return JSON response
     */
    
    protected function logErrorResponse(Exception $e, string $customMessage = 'An error occurred', int $status = 400): JsonResponse
    {
        Log::error($customMessage, [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'status'  => 'error',
            'message' => $customMessage,
            'error'   => $e->getMessage(),
        ], $status);
    }
}
