<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Thin wrapper around the Xendit EWallet & QR Code APIs.
 *
 * Xendit amounts are in PHP pesos (not centavos).
 * Auth: HTTP Basic with secret key as username, empty password.
 *
 * Testing keys start with xnd_development_
 * Live keys start with xnd_production_
 */
class XenditService
{
    private const BASE_URL = 'https://api.xendit.co';

    private string $secretKey;
    private string $publicKey;

    public function __construct(?string $secretKey = null, ?string $publicKey = null)
    {
        $this->secretKey = $secretKey ?? (string) config('xendit.secret_key');
        $this->publicKey = $publicKey ?? (string) config('xendit.public_key');
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        if ($this->secretKey === '') {
            throw new RuntimeException('Xendit secret key is not configured. Add XENDIT_SECRET_KEY to your .env file.');
        }

        // Xendit uses HTTP Basic Auth: secret key as username, empty password
        return Http::baseUrl(self::BASE_URL)
            ->withBasicAuth($this->secretKey, '')
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    /**
     * Create an EWallet charge for GCash or Maya.
     *
     * @param  float   $amount           Amount in PHP pesos (e.g. 500.00 for ₱500)
     * @param  string  $channelCode      'GCASH' | 'PAYMAYA'
     * @param  string  $referenceId      Your unique reference ID for this payment
     * @param  array   $channelProperties  URLs: success_redirect_url, failure_redirect_url, cancel_redirect_url
     * @param  array   $metadata         Optional metadata attached to the charge
     *
     * @return array{ id: string, status: string, checkout_url: ?string, reference_id: string, channel_code: string }
     */
    public function createEWalletCharge(
        float $amount,
        string $channelCode,
        string $referenceId,
        array $channelProperties = [],
        array $metadata = []
    ): array {
        $payload = [
            'reference_id'       => $referenceId,
            'currency'           => 'PHP',
            'amount'             => $amount,
            'checkout_method'    => 'ONE_TIME_PAYMENT',
            'channel_code'       => strtoupper($channelCode),
            'channel_properties' => $channelProperties,
        ];

        if (! empty($metadata)) {
            $payload['metadata'] = $metadata;
        }

        $response = $this->client()->post('/ewallets/charges', $payload);

        if ($response->failed()) {
            throw new RuntimeException(
                'Xendit createEWalletCharge failed: ' . $response->body(),
                $response->status()
            );
        }

        $data    = $response->json();
        $actions = $data['actions'] ?? [];

        // Xendit returns the checkout URL in different places depending on the channel
        $checkoutUrl = $data['checkout_url']
            ?? $actions['desktop_web_checkout_url']
            ?? $actions['mobile_web_checkout_url']
            ?? null;

        return [
            'id'           => $data['id'],
            'status'       => $data['status'] ?? 'PENDING',
            'reference_id' => $data['reference_id'] ?? $referenceId,
            'checkout_url' => $checkoutUrl,
            'channel_code' => $data['channel_code'] ?? strtoupper($channelCode),
        ];
    }

    /**
     * Get an EWallet charge by its Xendit charge ID.
     *
     * @return array{ id: string, status: string, reference_id: string, amount: float, channel_code: string }
     */
    public function getEWalletCharge(string $chargeId): array
    {
        $response = $this->client()->get('/ewallets/charges/' . $chargeId);

        if ($response->failed()) {
            throw new RuntimeException(
                'Xendit getEWalletCharge failed: ' . $response->body(),
                $response->status()
            );
        }

        $data = $response->json();

        return [
            'id'           => $data['id'],
            'status'       => $data['status'] ?? 'PENDING',
            'reference_id' => $data['reference_id'] ?? '',
            'amount'       => (float) ($data['amount'] ?? 0),
            'channel_code' => $data['channel_code'] ?? '',
        ];
    }

    /**
     * Create a dynamic QR Ph code that the customer scans to pay.
     *
     * @param  float   $amount
     * @param  string  $referenceId
     *
     * @return array{ id: string, qr_string: string, status: string, reference_id: string, amount: float }
     */
    public function createQRCode(float $amount, string $referenceId): array
    {
        $payload = [
            'reference_id' => $referenceId,
            'type'         => 'DYNAMIC',
            'currency'     => 'PHP',
            'amount'       => $amount,
            'expires_at'   => now()->addHours(2)->toIso8601String(),
        ];

        $response = $this->client()->post('/qr_codes', $payload);

        if ($response->failed()) {
            throw new RuntimeException(
                'Xendit createQRCode failed: ' . $response->body(),
                $response->status()
            );
        }

        $data = $response->json();

        return [
            'id'           => $data['id'],
            'qr_string'    => $data['qr_string'] ?? '',
            'status'       => $data['status'] ?? 'ACTIVE',
            'reference_id' => $data['reference_id'] ?? $referenceId,
            'amount'       => (float) ($data['amount'] ?? $amount),
        ];
    }

    /**
     * Get a QR code by its Xendit ID.
     *
     * @return array{ id: string, status: string, qr_string: string, amount: float }
     */
    public function getQRCode(string $qrCodeId): array
    {
        $response = $this->client()->get('/qr_codes/' . $qrCodeId);

        if ($response->failed()) {
            throw new RuntimeException(
                'Xendit getQRCode failed: ' . $response->body(),
                $response->status()
            );
        }

        $data = $response->json();

        return [
            'id'        => $data['id'],
            'status'    => $data['status'] ?? 'ACTIVE',
            'qr_string' => $data['qr_string'] ?? '',
            'amount'    => (float) ($data['amount'] ?? 0),
        ];
    }

    /**
     * Normalize Xendit's raw status strings into common values the rest
     * of the application understands.
     *
     * EWallet statuses: PENDING | SUCCEEDED | FAILED | VOIDED
     * QR Code statuses: ACTIVE | INACTIVE | (transitions via webhook)
     *
     * @return string  'succeeded' | 'failed' | 'cancelled' | 'pending'
     */
    public static function normalizeStatus(string $status): string
    {
        return match (strtoupper(trim($status))) {
            'SUCCEEDED'          => 'succeeded',
            'FAILED'             => 'failed',
            'VOIDED', 'INACTIVE' => 'cancelled',
            default              => 'pending',  // PENDING, ACTIVE, etc.
        };
    }

    /**
     * Verify a Xendit webhook callback token from the x-callback-token header.
     * Returns true if the webhook_token config is empty (skips check in dev).
     */
    public static function verifyWebhookToken(string $token): bool
    {
        $expected = (string) config('xendit.webhook_token', '');
        if ($expected === '') {
            return true; // skip verification when not configured
        }

        return hash_equals($expected, $token);
    }

    /** Convert a PHP amount to a display string. */
    public static function formatAmount(float $amount): string
    {
        return '₱' . number_format($amount, 2);
    }

    /** Safely parse a Xendit error response into a readable message. */
    public static function readableError(Throwable $e): string
    {
        $message = $e->getMessage();

        // Xendit returns JSON errors with "message" key
        if (preg_match('/"message"\s*:\s*"([^"]+)"/', $message, $matches)) {
            return $matches[1];
        }

        // Sometimes uses "error_code"
        if (preg_match('/"error_code"\s*:\s*"([^"]+)"/', $message, $matches)) {
            return str_replace('_', ' ', ucfirst(strtolower($matches[1])));
        }

        return 'Payment could not be processed right now. Please try again.';
    }
}
