<?php

namespace Modules\Store\Services\Payment;

use Illuminate\Support\Facades\Http;

class GeideaPaymentService
{
    public function generateSignature($merchantPublicKey, $orderAmount, $orderCurrency, $merchantReferenceId, $apiPassword, $timestamp)
    {
        $amountStr = number_format($orderAmount, 2, '.', '');
        $data = "{$merchantPublicKey}{$amountStr}{$orderCurrency}{$merchantReferenceId}{$timestamp}";
        $hash = hash_hmac('sha256', $data, $apiPassword, true);
        return base64_encode($hash);
    }

    public function createSession($amount, $currency, $merchantReferenceId, $callbackUrl, $apiPassword, $merchantPublicKey, $otherParams = [])
    {
        // Add credentials check
        if (empty($apiPassword) || empty($merchantPublicKey)) {
            throw new \Exception('Geidea API credentials (apiPassword or merchantPublicKey) are not set. Please check your .env and config/services.php.');
        }
        $timestamp = now()->format('Y/m/d H:i:s');
        $signature = $this->generateSignature($merchantPublicKey, $amount, $currency, $merchantReferenceId, $apiPassword, $timestamp);

        $data = array_merge([
            'amount' => $amount,
            'currency' => $currency,
            'merchantReferenceId' => $merchantReferenceId,
            'timestamp' => $timestamp,
            'signature' => $signature,
            'callbackUrl' => $callbackUrl,
        ], $otherParams);

        $url = 'https://api.merchant.geidea.net/payment-intent/api/v2/direct/session';

        $response = Http::withBasicAuth($merchantPublicKey, $apiPassword)
            ->post($url, $data);

        if ($response->successful() && $response->json('responseCode') === '000') {
            return $response->json('session.id');
        } else {
            throw new \Exception($response->json('detailedResponseMessage') ?? 'Geidea session creation failed');
        }
    }
} 