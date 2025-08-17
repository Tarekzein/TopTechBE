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
        
        // Some payment gateways are very strict about currency codes
        // Try to normalize the currency if needed
        $currency = $this->normalizeCurrency($currency);
        
        if (empty($merchantReferenceId)) {
            throw new \InvalidArgumentException('Merchant reference ID is required');
        }
        
        // Clean and validate merchant reference ID
        $merchantReferenceId = $this->cleanMerchantReferenceId($merchantReferenceId);
        
        if (empty($callbackUrl) || !filter_var($callbackUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Valid callback URL is required');
        }
        
        // Add credentials check
        if (empty($apiPassword) || empty($merchantPublicKey)) {
            throw new \Exception('Geidea API credentials (apiPassword or merchantPublicKey) are not set. Please check your .env and config/services.php.');
        }

        // Try different amount formats - some payment gateways are very specific
        $amountFormats = $this->getAmountFormats($amount, $currency);
        $apiEndpoints = $this->getApiEndpoints();
        
        foreach ($amountFormats as $formatName => $formattedAmount) {
            foreach ($apiEndpoints as $endpointName => $endpointUrl) {
                try {
                    Log::info("Trying amount format: {$formatName} with endpoint: {$endpointName}", [
                        'amount' => $formattedAmount,
                        'formatName' => $formatName,
                        'endpoint' => $endpointName,
                        'url' => $endpointUrl
                    ]);
                    
                    $result = $this->attemptCreateSession(
                        $formattedAmount,
                        $currency,
                        $merchantReferenceId,
                        $callbackUrl,
                        $apiPassword,
                        $merchantPublicKey,
                        $otherParams,
                        $endpointUrl,
                        $endpointName
                    );
                    
                    Log::info("Successfully created session with format: {$formatName} and endpoint: {$endpointName}", [
                        'amount' => $formattedAmount,
                        'formatName' => $formatName,
                        'endpoint' => $endpointName
                    ]);
                    
                    return $result;
                    
                } catch (\Exception $e) {
                    Log::warning("Failed to create session with format: {$formatName} and endpoint: {$endpointName}", [
                        'amount' => $formattedAmount,
                        'formatName' => $formatName,
                        'endpoint' => $endpointName,
                        'url' => $endpointUrl,
                        'error' => $e->getMessage()
                    ]);
                    
                    // If this is the last combination to try, throw the exception
                    if ($formatName === array_key_last($amountFormats) && $endpointName === array_key_last($apiEndpoints)) {
                        throw $e;
                    }
                    
                    // Continue to next combination
                    continue;
                }
            }
        }
        
        throw new \Exception('All amount formats and API endpoints failed for Geidea API');
    }
    
    /**
     * Get different amount formats to try with the payment gateway
     */
    private function getAmountFormats($amount, $currency)
    {
        $formats = [];
        
        switch (strtoupper($currency)) {
            case 'EGP':
                // For EGP, try multiple formats
                $formats = [
                    'piastres' => (int) round($amount * 100),
                    'decimal_2' => round($amount, 2),
                    'decimal_0' => (int) round($amount),
                    'string_piastres' => (string) round($amount * 100),
                    'string_decimal' => (string) round($amount, 2)
                ];
                break;
                
            case 'USD':
                // For USD, try multiple formats
                $formats = [
                    'cents' => (int) round($amount * 100),
                    'decimal_2' => round($amount, 2),
                    'decimal_0' => (int) round($amount),
                    'string_cents' => (string) round($amount * 100),
                    'string_decimal' => (string) round($amount, 2)
                ];
                break;
                
            default:
                // For other currencies
                $formats = [
                    'smallest_unit' => (int) round($amount * 100),
                    'decimal_2' => round($amount, 2),
                    'decimal_0' => (int) round($amount),
                    'string_smallest' => (string) round($amount * 100),
                    'string_decimal' => (string) round($amount, 2)
                ];
                break;
        }
        
        return $formats;
    }
    
    /**
     * Get different API endpoint options to try
     */
    private function getApiEndpoints()
    {
        return [
            'v2_direct_session' => 'https://api.merchant.geidea.net/payment-intent/api/v2/direct/session',
            'v2_session' => 'https://api.merchant.geidea.net/payment-intent/api/v2/session',
            'v1_session' => 'https://api.merchant.geidea.net/payment-intent/api/v1/session',
            'legacy_session' => 'https://api.merchant.geidea.net/payment-intent/session'
        ];
    }
    
    /**
     * Attempt to create a session with a specific amount format
     */
    private function attemptCreateSession($amount, $currency, $merchantReferenceId, $callbackUrl, $apiPassword, $merchantPublicKey, $otherParams = [], $url = null, $endpointName = null)
    {
        // Try different timestamp formats - some payment gateways are very specific
        $timestampFormats = [
            'iso8601' => now()->toISOString(),
            'unix_timestamp' => (string) now()->timestamp,
            'mysql_format' => now()->format('Y-m-d H:i:s'),
            'custom_format' => now()->format('Y/m/d H:i:s')
        ];
        
        foreach ($timestampFormats as $formatName => $timestamp) {
            try {
                Log::info("Trying timestamp format: {$formatName}", [
                    'timestamp' => $timestamp,
                    'formatName' => $formatName
                ]);
                
                $signature = $this->generateSignature($merchantPublicKey, $amount, $currency, $merchantReferenceId, $apiPassword, $timestamp);

                $data = array_merge([
                    'amount' => $amount,
                    'currency' => $currency,
                    'merchantReferenceId' => $merchantReferenceId,
                    'timestamp' => $timestamp,
                    'signature' => $signature,
                    'callbackUrl' => $callbackUrl,
                ], $otherParams);

                // Clean the payload to only include essential Geidea fields
                // Remove any extra fields that might be causing validation issues
                $cleanData = [
                    'amount' => $amount,
                    'currency' => $currency,
                    'merchantReferenceId' => $merchantReferenceId,
                    'timestamp' => $timestamp,
                    'signature' => $signature,
                    'callbackUrl' => $callbackUrl,
                ];
                
                // Add additional commonly required fields for payment gateways
                // These might be required by Geidea even if not documented
                $cleanData['merchantId'] = $merchantPublicKey;
                $cleanData['apiKey'] = $apiPassword;
                $cleanData['orderId'] = $merchantReferenceId;
                $cleanData['orderAmount'] = $amount;
                $cleanData['orderCurrency'] = $currency;

                Log::info('Geidea API request data:', [
                    'amount' => $amount,
                    'amountType' => gettype($amount),
                    'currency' => $currency,
                    'merchantReferenceId' => $merchantReferenceId,
                    'timestamp' => $timestamp,
                    'timestampFormat' => $formatName,
                    'url' => $url ?? 'https://api.merchant.geidea.net/payment-intent/api/v2/direct/session'
                ]);

                // Use the provided URL if available, otherwise use the default
                $finalUrl = $url ?? 'https://api.merchant.geidea.net/payment-intent/api/v2/direct/session';

                // Log the complete request payload for debugging
                Log::info('Complete Geidea API request payload:', [
                    'url' => $finalUrl,
                    'payload' => $cleanData,
                    'headers' => [
                        'Authorization' => 'Basic ' . base64_encode($merchantPublicKey . ':' . $apiPassword),
                        'Content-Type' => 'application/json'
                    ]
                ]);

                try {
                    $response = Http::withBasicAuth($merchantPublicKey, $apiPassword)
                        ->withHeaders([
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                            'User-Agent' => 'ATech-Payment-Service/1.0'
                        ])
                        ->timeout(30) // Add timeout
                        ->post($finalUrl, $cleanData);

                    Log::info('Geidea API response received:', [
                        'statusCode' => $response->status(),
                        'responseCode' => $response->json('responseCode'),
                        'responseMessage' => $response->json('detailedResponseMessage'),
                        'timestampFormat' => $formatName,
                        'endpoint' => $endpointName
                    ]);

                    if ($response->successful() && $response->json('responseCode') === '000') {
                        Log::info("Successfully created session with timestamp format: {$formatName} and endpoint: {$endpointName}");
                        return $response->json('session.id');
                    } else {
                        $errorMessage = $response->json('detailedResponseMessage') ?? 'Geidea session creation failed';
                        $responseCode = $response->json('responseCode');
                        $detailedResponseCode = $response->json('detailedResponseCode');
                        $language = $response->json('language');
                        
                        Log::warning("Failed with timestamp format: {$formatName} and endpoint: {$endpointName}", [
                            'responseCode' => $responseCode,
                            'detailedResponseCode' => $detailedResponseCode,
                            'errorMessage' => $errorMessage,
                            'language' => $language,
                            'fullResponse' => $response->json(),
                            'statusCode' => $response->status(),
                            'requestPayload' => $cleanData,
                            'endpoint' => $endpointName
                        ]);
                        
                        // If this is the last timestamp format to try, throw the exception
                        if ($formatName === array_key_last($timestampFormats)) {
                            throw new \Exception("Geidea API Error (Code: {$responseCode}, Detailed: {$detailedResponseCode}): {$errorMessage}");
                        }
                        
                        // Continue to next timestamp format
                        continue;
                    }
                } catch (\Exception $e) {
                    if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
                        Log::error('Geidea API connection error:', [
                            'error' => $e->getMessage(),
                            'url' => $finalUrl
                        ]);
                        throw new \Exception('Unable to connect to Geidea payment service. Please try again later.');
                    }
                    
                    // If this is the last timestamp format to try, throw the exception
                    if ($formatName === array_key_last($timestampFormats)) {
                        throw $e;
                    }
                    
                    // Continue to next timestamp format
                    continue;
                }
                
            } catch (\Exception $e) {
                // If this is the last timestamp format to try, throw the exception
                if ($formatName === array_key_last($timestampFormats)) {
                    throw $e;
                }
                
                // Continue to next timestamp format
                continue;
            }
        }
        
        throw new \Exception('All timestamp formats failed for Geidea API');
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
        
        // Handle different currencies - some payment gateways have specific requirements
        switch (strtoupper($currency)) {
            case 'EGP':
                // For EGP, try both formats: piastres and decimal
                $amountInPiastres = (int) round($amount * 100);
                $amountInDecimal = round($amount, 2);
                
                Log::info('EGP amount conversion options:', [
                    'originalAmount' => $amount,
                    'amountInPiastres' => $amountInPiastres,
                    'amountInDecimal' => $amountInDecimal
                ]);
                
                // Try piastres first (most common for EGP)
                return $amountInPiastres;
                
            case 'USD':
                // For USD, convert to cents
                $amountInCents = (int) round($amount * 100);
                Log::info('USD amount conversion:', [
                    'originalAmount' => $amount,
                    'amountInCents' => $amountInCents
                ]);
                return $amountInCents;
                
            default:
                // For other currencies, try both approaches
                $amountInSmallestUnit = (int) round($amount * 100);
                $amountInDecimal = round($amount, 2);
                
                Log::info('Default currency amount conversion:', [
                    'currency' => $currency,
                    'originalAmount' => $amount,
                    'amountInSmallestUnit' => $amountInSmallestUnit,
                    'amountInDecimal' => $amountInDecimal
                ]);
                
                return $amountInSmallestUnit;
        }
    }

    /**
     * Clean and validate merchant reference ID.
     * Geidea has a limit on merchantReferenceId length (e.g., 50 characters).
     * If the ID is too long, it will be truncated.
     */
    private function cleanMerchantReferenceId($merchantReferenceId)
    {
        $maxLength = 50; // Geidea's maximum allowed length for merchantReferenceId
        if (strlen($merchantReferenceId) > $maxLength) {
            Log::warning("Merchant reference ID is too long. Truncating to {$maxLength} characters.", [
                'originalLength' => strlen($merchantReferenceId),
                'merchantReferenceId' => $merchantReferenceId
            ]);
            return substr($merchantReferenceId, 0, $maxLength);
        }
        return $merchantReferenceId;
    }

    /**
     * Normalize currency code to a standard format if needed.
     * Geidea expects uppercase 3-letter codes.
     */
    private function normalizeCurrency($currency)
    {
        return strtoupper($currency);
    }
} 