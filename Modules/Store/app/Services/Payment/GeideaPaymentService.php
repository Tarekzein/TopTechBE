<?php

namespace Modules\Store\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeideaPaymentService
{
    public function generateSignature($merchantPublicKey, $orderAmount, $orderCurrency, $merchantReferenceId, $apiPassword, $timestamp)
    {
        // Ensure all parameters are properly formatted
        $merchantPublicKey = (string) $merchantPublicKey;
        $amountStr = (string) $orderAmount;
        $orderCurrency = (string) $orderCurrency;
        $merchantReferenceId = (string) $merchantReferenceId;
        $timestamp = (string) $timestamp;
        
        // Create the data string for signature
        $data = "{$merchantPublicKey}{$amountStr}{$orderCurrency}{$merchantReferenceId}{$timestamp}";
        
        Log::info('Geidea signature generation:', [
            'merchantPublicKey' => $merchantPublicKey,
            'amountStr' => $amountStr,
            'orderCurrency' => $orderCurrency,
            'merchantReferenceId' => $merchantReferenceId,
            'timestamp' => $timestamp,
            'dataString' => $data
        ]);
        
        $hash = hash_hmac('sha256', $data, $apiPassword, true);
        $signature = base64_encode($hash);
        
        Log::info('Geidea signature generated:', [
            'signature' => $signature,
            'signatureLength' => strlen($signature)
        ]);
        
        return $signature;
    }

    public function createSession($amount, $currency, $merchantReferenceId, $callbackUrl, $apiPassword, $merchantPublicKey, $otherParams = [])
    {
        // Validate input parameters
        if (empty($amount) || $amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than 0');
        }
        
        if (empty($currency) || strlen($currency) !== 3) {
            throw new \InvalidArgumentException('Currency must be a 3-letter ISO code (e.g., USD, EGP)');
        }
        
        // Validate currency format (must be uppercase letters)
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \InvalidArgumentException('Currency must be a valid 3-letter uppercase ISO code');
        }
        
        if (empty($merchantReferenceId)) {
            throw new \InvalidArgumentException('Merchant reference ID is required');
        }
        
        // Validate merchant reference ID format (alphanumeric and hyphens only, max 50 chars)
        if (!preg_match('/^[a-zA-Z0-9\-_]{1,50}$/', $merchantReferenceId)) {
            throw new \InvalidArgumentException('Merchant reference ID must be 1-50 characters long and contain only letters, numbers, hyphens, and underscores');
        }
        
        if (empty($callbackUrl) || !filter_var($callbackUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Valid callback URL is required');
        }
        
        // Add credentials check
        if (empty($apiPassword) || empty($merchantPublicKey)) {
            throw new \Exception('Geidea API credentials (apiPassword or merchantPublicKey) are not set. Please check your .env and config/services.php.');
        }

        // Convert amount to smallest currency unit (cents for USD, piastres for EGP, etc.)
        $amountInSmallestUnit = $this->convertToSmallestCurrencyUnit($amount, $currency);
        
        $timestamp = now()->toISOString(); // Use ISO 8601 format
        $signature = $this->generateSignature($merchantPublicKey, $amountInSmallestUnit, $currency, $merchantReferenceId, $apiPassword, $timestamp);

        $data = array_merge([
            'amount' => $amountInSmallestUnit,
            'currency' => $currency,
            'merchantReferenceId' => $merchantReferenceId,
            'timestamp' => $timestamp,
            'signature' => $signature,
            'callbackUrl' => $callbackUrl,
        ], $otherParams);

        Log::info('Geidea API request data:', [
            'amount' => $amount,
            'amountInSmallestUnit' => $amountInSmallestUnit,
            'currency' => $currency,
            'merchantReferenceId' => $merchantReferenceId,
            'timestamp' => $timestamp,
            'url' => 'https://api.merchant.geidea.net/payment-intent/api/v2/direct/session'
        ]);

        $url = 'https://api.merchant.geidea.net/payment-intent/api/v2/direct/session';

        try {
            $response = Http::withBasicAuth($merchantPublicKey, $apiPassword)
                ->timeout(30) // Add timeout
                ->post($url, $data);

            Log::info('Geidea API response received:', [
                'statusCode' => $response->status(),
                'responseCode' => $response->json('responseCode'),
                'responseMessage' => $response->json('detailedResponseMessage')
            ]);

            if ($response->successful() && $response->json('responseCode') === '000') {
                return $response->json('session.id');
            } else {
                $errorMessage = $response->json('detailedResponseMessage') ?? 'Geidea session creation failed';
                $responseCode = $response->json('responseCode');
                
                Log::error('Geidea API error response:', [
                    'responseCode' => $responseCode,
                    'errorMessage' => $errorMessage,
                    'fullResponse' => $response->json(),
                    'statusCode' => $response->status()
                ]);
                
                throw new \Exception("Geidea API Error (Code: {$responseCode}): {$errorMessage}");
            }
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
                Log::error('Geidea API connection error:', [
                    'error' => $e->getMessage(),
                    'url' => $url
                ]);
                throw new \Exception('Unable to connect to Geidea payment service. Please try again later.');
            }
            
            // Re-throw the original exception if it's not a connection issue
            throw $e;
        }
    }

    /**
     * Convert amount to smallest currency unit
     * USD -> cents (multiply by 100)
     * EGP -> piastres (multiply by 100)
     * Other currencies may vary
     */
    private function convertToSmallestCurrencyUnit($amount, $currency)
    {
        // Ensure amount is a valid number
        if (!is_numeric($amount)) {
            throw new \InvalidArgumentException('Amount must be a valid number');
        }
        
        $amount = (float) $amount;
        
        // Validate amount range
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than 0');
        }
        
        if ($amount > 999999999) {
            throw new \InvalidArgumentException('Amount is too large');
        }
        
        // Most currencies use 100 as the smallest unit
        $multiplier = 100;
        
        // Convert to integer to avoid floating point precision issues
        $amountInSmallestUnit = (int) round($amount * $multiplier);
        
        // Ensure the converted amount is positive
        if ($amountInSmallestUnit <= 0) {
            throw new \InvalidArgumentException('Converted amount must be greater than 0');
        }
        
        Log::info('Amount conversion:', [
            'originalAmount' => $amount,
            'currency' => $currency,
            'multiplier' => $multiplier,
            'convertedAmount' => $amountInSmallestUnit
        ]);
        
        return $amountInSmallestUnit;
    }
} 