<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Thin wrapper around the PayMongo Payment Intents API.
 *
 * All amounts are handled in centavos (PayMongo's smallest currency unit)
 * and converted to/from pesos at the boundary of this class.
 */
class PayMongoService
{
    private const BASE_URL = 'https://api.paymongo.com/v1';

    private string $publicKey;

    private string $secretKey;

    public function __construct(?string $publicKey = null, ?string $secretKey = null)
    {
        $this->publicKey = $publicKey ?? (string) config('paymongo.public_key');
        $this->secretKey = $secretKey ?? (string) config('paymongo.secret_key');
    }

    /** PayMongo Basic auth expects `key:` (empty password) base64-encoded. */
    private function authHeader(bool $secret = true): string
    {
        $key = $secret ? $this->secretKey : $this->publicKey;

        if ($key === '') {
            throw new RuntimeException('PayMongo API key is not configured.');
        }

        return 'Basic ' . base64_encode($key . ':');
    }

    private function client(bool $secret = true): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->withHeaders(['Authorization' => $this->authHeader($secret)])
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    /**
     * Create a Payment Intent (server-side).
     *
     * @param  int  $amountCentavos  amount to charge, in centavos
     * @param  array  $allowedMethods  e.g. ['gcash', 'card']
     * @return array{id: string, client_key: string, status: string, amount: int, currency: string}
     */
    public function createPaymentIntent(int $amountCentavos, string $description, array $allowedMethods = ['gcash', 'card'], ?string $statementDescriptor = null): array
    {
        $payload = [
            'data' => [
                'attributes' => [
                    'amount' => $amountCentavos,
                    'currency' => 'PHP',
                    'payment_method_allowed' => $allowedMethods,
                    'description' => $description,
                    'statement_descriptor' => $statementDescriptor ?? config('paymongo.statement_descriptor', 'HINAGUAN NATURE PARK'),
                ],
            ],
        ];

        $response = $this->client(true)->post('/payment_intents', $payload);

        if ($response->failed()) {
            throw new RuntimeException('PayMongo createPaymentIntent failed: ' . $response->body(), $response->status());
        }

        $attributes = $response->json('data.attributes', []);

        return [
            'id' => $response->json('data.id'),
            'client_key' => $attributes['client_key'] ?? null,
            'status' => $attributes['status'] ?? 'awaiting_payment_method',
            'amount' => (int) ($attributes['amount'] ?? 0),
            'currency' => $attributes['currency'] ?? 'PHP',
        ];
    }

    /**
     * Retrieve a Payment Intent (server-side) to verify its status.
     *
     * @return array{id: string, status: string, amount: int, currency: string, next_action: ?array, last_payment_error: ?array}
     */
    public function getPaymentIntent(string $paymentIntentId): array
    {
        $response = $this->client(true)->get('/payment_intents/' . $paymentIntentId);

        if ($response->failed()) {
            throw new RuntimeException('PayMongo getPaymentIntent failed: ' . $response->body(), $response->status());
        }

        $attributes = $response->json('data.attributes', []);

        return [
            'id' => $response->json('data.id'),
            'status' => $attributes['status'] ?? 'unknown',
            'amount' => (int) ($attributes['amount'] ?? 0),
            'currency' => $attributes['currency'] ?? 'PHP',
            'next_action' => $attributes['next_action'] ?? null,
            'last_payment_error' => $attributes['last_payment_error'] ?? null,
            'payments' => $attributes['payments'] ?? [],
        ];
    }

    /**
     * Create a Payment Method.
     *
     * @param  string  $type  'gcash' | 'paymaya' | 'card' | 'qrph'
     */
    public function createPaymentMethod(string $type, array $details = [], array $billing = []): array
    {
        $attributes = [
            'type' => $type,
        ];

        if (! empty($billing)) {
            $attributes['billing'] = array_filter([
                'name' => $billing['name'] ?? null,
                'email' => $billing['email'] ?? null,
                'phone' => $billing['phone'] ?? null,
            ]);
        }

        if ($type === 'card' && ! empty($details)) {
            $attributes['details'] = [
                'card_number' => str_replace(' ', '', $details['card_number'] ?? ''),
                'exp_month' => (int) ($details['exp_month'] ?? 0),
                'exp_year' => (int) ($details['exp_year'] ?? 0),
                'cvc' => (string) ($details['cvc'] ?? ''),
            ];
        }

        $payload = [
            'data' => [
                'attributes' => $attributes,
            ],
        ];

        $response = $this->client(true)->post('/payment_methods', $payload);

        if ($response->failed()) {
            throw new RuntimeException('PayMongo createPaymentMethod failed: ' . $response->body(), $response->status());
        }

        return [
            'id' => $response->json('data.id'),
            'type' => $response->json('data.attributes.type'),
        ];
    }

    /**
     * Attach a Payment Method to a Payment Intent.
     */
    public function attachPaymentMethod(string $paymentIntentId, string $paymentMethodId, ?string $clientKey = null, ?string $returnUrl = null): array
    {
        $payload = [
            'data' => [
                'attributes' => array_filter([
                    'payment_method' => $paymentMethodId,
                    'client_key' => $clientKey,
                    'return_url' => $returnUrl,
                ]),
            ],
        ];

        $response = $this->client(true)->post('/payment_intents/' . $paymentIntentId . '/attach', $payload);

        if ($response->failed()) {
            throw new RuntimeException('PayMongo attachPaymentMethod failed: ' . $response->body(), $response->status());
        }

        $attributes = $response->json('data.attributes', []);

        return [
            'id' => $response->json('data.id'),
            'status' => $attributes['status'] ?? 'unknown',
            'next_action' => $attributes['next_action'] ?? null,
            'last_payment_error' => $attributes['last_payment_error'] ?? null,
        ];
    }

    /** Convert pesos to centavos (rounded to the nearest integer). */
    public static function toCentavos(float|int $pesos): int
    {
        return (int) round((float) $pesos * 100);
    }

    /** Convert centavos back to pesos. */
    public static function toPesos(int $centavos): float
    {
        return $centavos / 100;
    }

    /** Safely parse a PayMongo error body into a readable message. */
    public static function readableError(Throwable $e): string
    {
        $message = $e->getMessage();

        if (preg_match('/"detail"\s*:\s*"([^"]+)"/', $message, $matches)) {
            return $matches[1];
        }

        if (preg_match('/"message"\s*:\s*"([^"]+)"/', $message, $matches)) {
            return $matches[1];
        }

        return 'Payment could not be processed right now. Please try again.';
    }
}
