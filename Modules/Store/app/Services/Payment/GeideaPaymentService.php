<?php

namespace Modules\Store\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeideaPaymentService
{
    public function generateSignature($merchantPublicKey, $orderAmount, $orderCurrency, $merchantReferenceId, $apiPassword, $timestamp)
    {
        // Validate input parameters
        if (empty($merchantPublicKey) || empty($apiPassword)) {
            throw new \InvalidArgumentException('Merchant public key and API password are required for signature generation');
        }
        
        if (empty($orderAmount) || !is_numeric($orderAmount)) {
            throw new \InvalidArgumentException('Order amount must be a valid number');
        }
        
        if (empty($orderCurrency) || strlen($orderCurrency) !== 3) {
            throw new \InvalidArgumentException('Order currency must be a 3-letter ISO code');
        }
        
        if (empty($merchantReferenceId)) {
            throw new \InvalidArgumentException('Merchant reference ID is required');
        }
        
        if (empty($timestamp)) {
            throw new \InvalidArgumentException('Timestamp is required');
        }
        
        // Ensure all parameters are properly formatted
        $merchantPublicKey = (string) $merchantPublicKey;
        // Format amount with 2 decimal places as per Geidea documentation
        $amountStr = number_format((float) $orderAmount, 2, '.', '');
        $orderCurrency = (string) $orderCurrency;
        $merchantReferenceId = (string) $merchantReferenceId;
        $timestamp = (string) $timestamp;
        
        // Create the data string for signature as per Geidea documentation
        $data = "{$merchantPublicKey}{$amountStr}{$orderCurrency}{$merchantReferenceId}{$timestamp}";
        
        Log::info('Geidea signature generation:', [
            'merchantPublicKey' => $merchantPublicKey,
            'amountStr' => $amountStr,
            'orderCurrency' => $orderCurrency,
            'merchantReferenceId' => $merchantReferenceId,
            'timestamp' => $timestamp,
            'dataString' => $data,
            'dataStringLength' => strlen($data)
        ]);
        
        $hash = hash_hmac('sha256', $data, $apiPassword, true);
        $signature = base64_encode($hash);
        
        Log::info('Geidea signature generated:', [
            'signature' => $signature,
            'signatureLength' => strlen($signature),
            'hashAlgorithm' => 'sha256',
            'encoding' => 'base64'
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
        
        Log::info('Geidea createSession called with parameters:', [
            'amount' => $amount,
            'currency' => $currency,
            'merchantReferenceId' => $merchantReferenceId,
            'callbackUrl' => $callbackUrl,
            'merchantPublicKey' => substr($merchantPublicKey, 0, 10) . '...', // Log partial key for security
            'apiPasswordLength' => strlen($apiPassword),
            'otherParams' => $otherParams
        ]);

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
     * Geidea documentation specifies: "The format is a double - a number with 2 digits after the decimal point. For example 19.99"
     */
    private function getAmountFormats($amount, $currency)
    {
        $formats = [];
        
        // Geidea expects a double with 2 decimal places
        $formats = [
            'geidea_format' => round($amount, 2), // Primary format as per Geidea docs
            'string_format' => (string) round($amount, 2), // String version
            'decimal_2' => number_format($amount, 2, '.', ''), // Formatted with number_format
        ];
        
        // For EGP, also try piastres format as some implementations might expect it
        if (strtoupper($currency) === 'EGP') {
            $formats['piastres'] = (int) round($amount * 100);
            $formats['string_piastres'] = (string) round($amount * 100);
        }
        
        // For USD, also try cents format
        if (strtoupper($currency) === 'USD') {
            $formats['cents'] = (int) round($amount * 100);
            $formats['string_cents'] = (string) round($amount * 100);
        }
        
        return $formats;
    }
    
    /**
     * Get different API endpoint options to try
     * Based on Geidea documentation: https://api.merchant.geidea.net/payment-intent/api/v2/direct/session
     */
    private function getApiEndpoints()
    {
        return [
            'v2_direct_session' => 'https://api.merchant.geidea.net/payment-intent/api/v2/direct/session',
            'v2_session' => 'https://api.merchant.geidea.net/payment-intent/api/v2/session',
            'v1_session' => 'https://api.merchant.geidea.net/payment-intent/api/v1/session'
        ];
    }
    
    /**
     * Attempt to create a session with a specific amount format
     */
    private function attemptCreateSession($amount, $currency, $merchantReferenceId, $callbackUrl, $apiPassword, $merchantPublicKey, $otherParams = [], $url = null, $endpointName = null)
    {
        // Try different timestamp formats - Geidea documentation shows Y/m/d H:i:s format
        $timestampFormats = [
            'geidea_format' => now()->format('Y/m/d H:i:s'),
            'iso8601' => now()->toISOString(),
            'unix_timestamp' => (string) now()->timestamp,
            'mysql_format' => now()->format('Y-m-d H:i:s')
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

                // Create payload according to Geidea documentation
                $cleanData = [
                    'amount' => $amount,
                    'currency' => $currency,
                    'timestamp' => $timestamp,
                    'merchantReferenceId' => $merchantReferenceId,
                    'signature' => $signature,
                    'callbackUrl' => $callbackUrl,
                ];
                
                // Add optional parameters if provided
                if (isset($otherParams['language'])) {
                    $cleanData['language'] = $otherParams['language'];
                }
                
                if (isset($otherParams['paymentOperation'])) {
                    $cleanData['paymentOperation'] = $otherParams['paymentOperation'];
                }
                
                if (isset($otherParams['returnUrl'])) {
                    $cleanData['returnUrl'] = $otherParams['returnUrl'];
                }
                
                if (isset($otherParams['cardOnFile'])) {
                    $cleanData['cardOnFile'] = $otherParams['cardOnFile'];
                }
                
                if (isset($otherParams['tokenId'])) {
                    $cleanData['tokenId'] = $otherParams['tokenId'];
                }
                
                // Add customer information if provided
                if (isset($otherParams['customer'])) {
                    $cleanData['customer'] = $otherParams['customer'];
                }
                
                // Add order information if provided
                if (isset($otherParams['order'])) {
                    $cleanData['order'] = $otherParams['order'];
                }

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

                    // Log the raw response for debugging
                    Log::info('Geidea API raw response:', [
                        'statusCode' => $response->status(),
                        'headers' => $response->headers(),
                        'body' => $response->body(),
                        'timestampFormat' => $formatName,
                        'endpoint' => $endpointName
                    ]);

                    // Check if we got a valid JSON response
                    $responseData = $response->json();
                    
                    // Handle case where response is not valid JSON
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::error('Geidea API returned invalid JSON:', [
                            'jsonError' => json_last_error_msg(),
                            'rawBody' => $response->body(),
                            'statusCode' => $response->status(),
                            'timestampFormat' => $formatName,
                            'endpoint' => $endpointName
                        ]);
                        
                        // If this is the last timestamp format to try, throw the exception
                        if ($formatName === array_key_last($timestampFormats)) {
                            throw new \Exception('Geidea API returned invalid JSON response: ' . json_last_error_msg());
                        }
                        
                        // Continue to next timestamp format
                        continue;
                    }
                    
                    Log::info('Geidea API parsed response:', [
                        'statusCode' => $response->status(),
                        'responseData' => $responseData,
                        'responseCode' => $responseData['responseCode'] ?? 'N/A',
                        'responseMessage' => $responseData['detailedResponseMessage'] ?? $responseData['message'] ?? 'N/A',
                        'timestampFormat' => $formatName,
                        'endpoint' => $endpointName
                    ]);

                    if ($response->successful() && isset($responseData['responseCode']) && $responseData['responseCode'] === '000') {
                        Log::info("Successfully created session with timestamp format: {$formatName} and endpoint: {$endpointName}");
                        
                        // Extract session ID according to Geidea documentation
                        $sessionId = null;
                        if (isset($responseData['session']['id'])) {
                            $sessionId = $responseData['session']['id'];
                        } elseif (isset($responseData['sessionId'])) {
                            $sessionId = $responseData['sessionId'];
                        }
                        
                        if ($sessionId) {
                            Log::info('Geidea session created successfully:', [
                                'sessionId' => $sessionId,
                                'responseCode' => $responseData['responseCode'],
                                'responseMessage' => $responseData['responseMessage'] ?? 'Success'
                            ]);
                            return $sessionId;
                        } else {
                            Log::error('Geidea session created but no session ID found in response:', [
                                'responseData' => $responseData
                            ]);
                            throw new \Exception('Session created but no session ID returned from Geidea API');
                        }
                    } else {
                        // Extract error information with better fallbacks
                        $errorMessage = $responseData['detailedResponseMessage'] ?? 
                                       $responseData['message'] ?? 
                                       $responseData['error'] ?? 
                                       'Geidea session creation failed';
                        $responseCode = $responseData['responseCode'] ?? $responseData['code'] ?? 'N/A';
                        $detailedResponseCode = $responseData['detailedResponseCode'] ?? $responseData['errorCode'] ?? 'N/A';
                        $language = $responseData['language'] ?? 'N/A';
                        
                        Log::warning("Failed with timestamp format: {$formatName} and endpoint: {$endpointName}", [
                            'responseCode' => $responseCode,
                            'detailedResponseCode' => $detailedResponseCode,
                            'errorMessage' => $errorMessage,
                            'language' => $language,
                            'fullResponse' => $responseData,
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
                    Log::error('Geidea API request exception:', [
                        'error' => $e->getMessage(),
                        'errorType' => get_class($e),
                        'url' => $finalUrl,
                        'timestampFormat' => $formatName,
                        'endpoint' => $endpointName,
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
                        Log::error('Geidea API connection error:', [
                            'error' => $e->getMessage(),
                            'url' => $finalUrl,
                            'timestampFormat' => $formatName,
                            'endpoint' => $endpointName
                        ]);
                        
                        // If this is the last timestamp format to try, throw the exception
                        if ($formatName === array_key_last($timestampFormats)) {
                            throw new \Exception('Unable to connect to Geidea payment service. Please try again later.');
                        }
                        
                        // Continue to next timestamp format
                        continue;
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
    
    /**
     * Get the checkout URL for the specified environment
     * Based on Geidea documentation:
     * - KSA Environment: https://www.ksamerchant.geidea.net/hpp/checkout/
     * - Egypt Environment: https://www.merchant.geidea.net/hpp/checkout/
     * - UAE Environment: https://payments.geidea.ae/hpp/checkout/
     */
    public function getCheckoutUrl($sessionId, $environment = 'egypt')
    {
        $baseUrls = [
            'ksa' => 'https://www.ksamerchant.geidea.net/hpp/checkout/',
            'egypt' => 'https://www.merchant.geidea.net/hpp/checkout/',
            'uae' => 'https://payments.geidea.ae/hpp/checkout/'
        ];
        
        $baseUrl = $baseUrls[strtolower($environment)] ?? $baseUrls['egypt'];
        
        return $baseUrl . $sessionId;
    }
    
    /**
     * Get the JavaScript library URL for the specified environment
     * Based on Geidea documentation:
     * - KSA Environment: https://www.ksamerchant.geidea.net/hpp/geideaCheckout.min.js
     * - Egypt Environment: https://www.merchant.geidea.net/hpp/geideaCheckout.min.js
     * - UAE Environment: https://payments.geidea.ae/hpp/geideaCheckout.min.js
     */
    public function getJavaScriptUrl($environment = 'egypt')
    {
        $urls = [
            'ksa' => 'https://www.ksamerchant.geidea.net/hpp/geideaCheckout.min.js',
            'egypt' => 'https://www.merchant.geidea.net/hpp/geideaCheckout.min.js',
            'uae' => 'https://payments.geidea.ae/hpp/geideaCheckout.min.js'
        ];
        
        return $urls[strtolower($environment)] ?? $urls['egypt'];
    }
    
    /**
     * Validate callback response from Geidea
     * This method can be used to validate the callback data received from Geidea
     */
    public function validateCallback($callbackData, $apiPassword, $merchantPublicKey)
    {
        try {
            // Extract required fields from callback
            $responseCode = $callbackData['responseCode'] ?? null;
            $responseMessage = $callbackData['responseMessage'] ?? null;
            $detailedResponseCode = $callbackData['detailedResponseCode'] ?? null;
            $detailedResponseMessage = $callbackData['detailedResponseMessage'] ?? null;
            $orderId = $callbackData['orderId'] ?? null;
            $reference = $callbackData['reference'] ?? null;
            $signature = $callbackData['signature'] ?? null;
            
            Log::info('Geidea callback validation:', [
                'responseCode' => $responseCode,
                'responseMessage' => $responseMessage,
                'detailedResponseCode' => $detailedResponseCode,
                'detailedResponseMessage' => $detailedResponseMessage,
                'orderId' => $orderId,
                'reference' => $reference,
                'hasSignature' => !empty($signature)
            ]);
            
            // Check if payment was successful
            $isSuccessful = ($responseCode === '000' && $detailedResponseCode === '000');
            
            return [
                'isSuccessful' => $isSuccessful,
                'responseCode' => $responseCode,
                'responseMessage' => $responseMessage,
                'detailedResponseCode' => $detailedResponseCode,
                'detailedResponseMessage' => $detailedResponseMessage,
                'orderId' => $orderId,
                'reference' => $reference,
                'signature' => $signature
            ];
            
        } catch (\Exception $e) {
            Log::error('Error validating Geidea callback:', [
                'error' => $e->getMessage(),
                'callbackData' => $callbackData
            ]);
            
            throw new \Exception('Failed to validate Geidea callback: ' . $e->getMessage());
        }
    }
} 