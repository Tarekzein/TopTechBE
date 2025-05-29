<?php

namespace Modules\Store\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Store\App\Services\SettingService;
use Modules\Store\Interfaces\SettingServiceInterface;
use Illuminate\Support\Facades\Validator;
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
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $withValues = $request->boolean('with_values', false);
            $locale = $request->input('locale');
            $settings = $this->settingService->getAllSettings($withValues, $locale);
            
            return response()->json([
                'status' => 'success',
                'data' => $settings
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get public settings
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function public(Request $request): JsonResponse
    {
        try {
            $withValues = $request->boolean('with_values', false);
            $locale = $request->input('locale');
            $settings = $this->settingService->getPublicSettings($withValues, $locale);
            
            return response()->json([
                'status' => 'success',
                'data' => $settings
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch public settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get settings by group
     *
     * @param Request $request
     * @param string $group
     * @return JsonResponse
     */
    public function byGroup(Request $request, string $group): JsonResponse
    {
        try {
            $withValues = $request->boolean('with_values', false);
            $locale = $request->input('locale');
            $settings = $this->settingService->getSettingsByGroup($group, $withValues, $locale);
            
            return response()->json([
                'status' => 'success',
                'data' => $settings
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch settings by group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a setting value
     *
     * @param Request $request
     * @param string $key
     * @return JsonResponse
     */
    public function getValue(Request $request, string $key): JsonResponse
    {
        try {
            $locale = $request->input('locale');
            $value = $this->settingService->getSettingValue($key, $locale);
            
            if ($value === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Setting not found'
                ], 404);
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $value
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch setting value',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set a setting value
     *
     * @param Request $request
     * @param string $key
     * @return JsonResponse
     */
    public function setValue(Request $request, string $key): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'value' => 'required',
                'locale' => 'nullable|string|max:10'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $success = $this->settingService->setSettingValue(
                $key,
                $request->input('value'),
                $request->input('locale')
            );

            if (!$success) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update setting value'
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Setting value updated successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update setting value',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new setting
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'key' => 'required|string|unique:settings,key',
                'name' => 'required|string',
                'type' => 'required|string|in:' . implode(',', array_keys(SettingService::TYPES)),
                'group' => 'required|string|in:' . implode(',', array_keys(SettingService::GROUPS)),
                'value' => 'required',
                'is_public' => 'boolean',
                'is_required' => 'boolean',
                'validation_rules' => 'nullable|array',
                'options' => 'nullable|array',
                'display_order' => 'integer',
                'locale' => 'nullable|string|max:10'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $setting = $this->settingService->createSetting($request->all());

            if (!$setting) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create setting'
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Setting created successfully',
                'data' => $setting
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a setting
     *
     * @param Request $request
     * @param string $key
     * @return JsonResponse
     */
    public function update(Request $request, string $key): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'string',
                'type' => 'string|in:' . implode(',', array_keys(SettingService::TYPES)),
                'group' => 'string|in:' . implode(',', array_keys(SettingService::GROUPS)),
                'value' => 'nullable',
                'is_public' => 'boolean',
                'is_required' => 'boolean',
                'validation_rules' => 'nullable|array',
                'options' => 'nullable|array',
                'display_order' => 'integer',
                'locale' => 'nullable|string|max:10'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $success = $this->settingService->updateSetting($key, $request->all());

            if (!$success) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update setting'
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Setting updated successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a setting
     *
     * @param string $key
     * @return JsonResponse
     */
    public function destroy(string $key): JsonResponse
    {
        try {
            $success = $this->settingService->deleteSetting($key);

            if (!$success) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete setting'
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Setting deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available setting types
     *
     * @return JsonResponse
     */
    public function types(): JsonResponse
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => $this->settingService->getSettingTypes()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch setting types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available setting groups
     *
     * @return JsonResponse
     */
    public function groups(): JsonResponse
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => $this->settingService->getSettingGroups()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch setting groups',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
