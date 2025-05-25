<?php

namespace Modules\Store\App\Services;

use Modules\Store\App\Models\Currency;
use Illuminate\Database\Eloquent\Collection;
use Modules\Store\App\Services\Interfaces\CurrencyServiceInterface;
use Modules\Store\App\Repositories\Interfaces\CurrencyRepositoryInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyService implements CurrencyServiceInterface
{
    /**
     * @var CurrencyRepositoryInterface
     */
    protected $currencyRepository;

    /**
     * CurrencyService constructor.
     *
     * @param CurrencyRepositoryInterface $currencyRepository
     */
    public function __construct(CurrencyRepositoryInterface $currencyRepository)
    {
        $this->currencyRepository = $currencyRepository;
    }

    /**
     * Get all active currencies
     *
     * @return Collection
     */
    public function getActiveCurrencies(): Collection
    {
        return $this->currencyRepository->getActive();
    }

    /**
     * Get the default currency
     *
     * @return Currency|null
     */
    public function getDefaultCurrency(): ?Currency
    {
        return $this->currencyRepository->getDefault();
    }

    /**
     * Set a currency as default
     *
     * @param string $code
     * @return bool
     */
    public function setDefaultCurrency(string $code): bool
    {
        $currency = $this->currencyRepository->findByCode($code);
        if (!$currency) {
            return false;
        }

        return $this->currencyRepository->setDefault($currency);
    }

    /**
     * Convert amount from one currency to another
     *
     * @param float $amount
     * @param string $fromCode
     * @param string $toCode
     * @return float|null
     */
    public function convertAmount(float $amount, string $fromCode, string $toCode): ?float
    {
        if ($fromCode === $toCode) {
            return $amount;
        }

        $fromCurrency = $this->currencyRepository->findByCode($fromCode);
        $toCurrency = $this->currencyRepository->findByCode($toCode);

        if (!$fromCurrency || !$toCurrency) {
            return null;
        }

        // Convert to default currency first, then to target currency
        $defaultCurrency = $this->getDefaultCurrency();
        if (!$defaultCurrency) {
            return null;
        }

        if ($fromCurrency->code === $defaultCurrency->code) {
            return $amount * $toCurrency->exchange_rate;
        }

        if ($toCurrency->code === $defaultCurrency->code) {
            return $amount / $fromCurrency->exchange_rate;
        }

        // Convert through default currency
        $amountInDefault = $amount / $fromCurrency->exchange_rate;
        return $amountInDefault * $toCurrency->exchange_rate;
    }

    /**
     * Format amount in a specific currency
     *
     * @param float $amount
     * @param string $code
     * @return string|null
     */
    public function formatAmount(float $amount, string $code): ?string
    {
        $currency = $this->currencyRepository->findByCode($code);
        if (!$currency) {
            return null;
        }

        return $currency->format($amount);
    }

    /**
     * Update exchange rates from an external service
     *
     * @return bool
     */
    public function updateExchangeRates(): bool
    {
        try {
            // Get default currency
            $defaultCurrency = $this->getDefaultCurrency();
            if (!$defaultCurrency) {
                return false;
            }

            // Get active currencies
            $currencies = $this->getActiveCurrencies();
            if ($currencies->isEmpty()) {
                return false;
            }

            // Fetch rates from external API (example using exchangerate-api.com)
            $response = Http::get('https://api.exchangerate-api.com/v4/latest/' . $defaultCurrency->code);
            
            if (!$response->successful()) {
                Log::error('Failed to fetch exchange rates', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }

            $data = $response->json();
            if (!isset($data['rates'])) {
                return false;
            }

            // Update rates in database
            return $this->currencyRepository->updateExchangeRates($data['rates']);

        } catch (\Exception $e) {
            Log::error('Error updating exchange rates', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Create a new currency
     *
     * @param array $data
     * @return Currency|null
     */
    public function createCurrency(array $data): ?Currency
    {
        try {
            // Validate currency code format
            if (!preg_match('/^[A-Z]{3}$/', $data['code'])) {
                return null;
            }

            // Check if currency already exists
            if ($this->currencyRepository->findByCode($data['code'])) {
                return null;
            }

            // Create currency
            $currency = new Currency($data);
            
            // If this is the first currency, set it as default
            if ($this->getActiveCurrencies()->isEmpty()) {
                $currency->is_default = true;
            }

            $currency->save();
            return $currency;

        } catch (\Exception $e) {
            Log::error('Error creating currency', [
                'data' => $data,
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Update a currency
     *
     * @param string $code
     * @param array $data
     * @return bool
     */
    public function updateCurrency(string $code, array $data): bool
    {
        try {
            $currency = $this->currencyRepository->findByCode($code);
            if (!$currency) {
                return false;
            }

            // Don't allow changing the code
            unset($data['code']);

            // If setting as default, handle that separately
            if (isset($data['is_default']) && $data['is_default']) {
                $this->setDefaultCurrency($code);
                unset($data['is_default']);
            }

            return $currency->update($data);

        } catch (\Exception $e) {
            Log::error('Error updating currency', [
                'code' => $code,
                'data' => $data,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete a currency
     *
     * @param string $code
     * @return bool
     */
    public function deleteCurrency(string $code): bool
    {
        try {
            $currency = $this->currencyRepository->findByCode($code);
            if (!$currency) {
                return false;
            }

            // Don't allow deleting the default currency
            if ($currency->is_default) {
                return false;
            }

            return $currency->delete();

        } catch (\Exception $e) {
            Log::error('Error deleting currency', [
                'code' => $code,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }
} 