<?php

namespace Modules\Store\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Store\App\Models\Currency;
use Modules\Store\Interfaces\CurrencyServiceInterface;
use Exception;

class CurrencyController extends Controller
{
    protected CurrencyServiceInterface $currencyService;

    public function __construct(CurrencyServiceInterface $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Get all active currencies.
     */
    public function index(): JsonResponse
    {
        try {
            $currencies = $this->currencyService->getActiveCurrencies();
            return response()->json([
                'status' => 'success',
                'data' => $currencies
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch currencies',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new currency.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:10|unique:currencies,code',
            'name' => 'required|string|max:100',
            'symbol' => 'required|string|max:10',
            'position' => 'required|in:before,after',
            'decimal_places' => 'required|integer|min:0|max:6',
            'decimal_separator' => 'required|string|max:2',
            'thousands_separator' => 'required|string|max:2',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'exchange_rate' => 'required|numeric|min:0',
        ]);

        try {
            $currency = Currency::create($data);
            return response()->json([
                'status' => 'success',
                'data' => $currency
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create currency',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a specific currency.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $currency = Currency::findOrFail($id);
            return response()->json([
                'status' => 'success',
                'data' => $currency
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Currency not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update a specific currency.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'code' => 'string|max:10|unique:currencies,code,' . $id,
            'name' => 'string|max:100',
            'symbol' => 'string|max:10',
            'position' => 'in:before,after',
            'decimal_places' => 'integer|min:0|max:6',
            'decimal_separator' => 'string|max:2',
            'thousands_separator' => 'string|max:2',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'exchange_rate' => 'numeric|min:0',
        ]);

        try {
            $currency = Currency::findOrFail($id);
            $currency->update($data);
            return response()->json([
                'status' => 'success',
                'data' => $currency
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update currency',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a currency.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $currency = Currency::findOrFail($id);
            $currency->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Currency deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete currency',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle active status.
     */
    public function toggleActive(int $id): JsonResponse
    {
        try {
            $currency = Currency::findOrFail($id);
            $currency->is_active = !$currency->is_active;
            $currency->save();

            return response()->json([
                'status' => 'success',
                'data' => $currency
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle active status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set a currency as default.
     */
    public function setDefault(int $id): JsonResponse
    {
        try {
            Currency::where('is_default', true)->update(['is_default' => false]);

            $currency = Currency::findOrFail($id);
            $currency->is_default = true;
            $currency->save();

            return response()->json([
                'status' => 'success',
                'data' => $currency
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to set default currency',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
