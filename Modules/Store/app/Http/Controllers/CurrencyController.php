<?php

namespace Modules\Store\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
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
     *
     * @return JsonResponse
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
} 