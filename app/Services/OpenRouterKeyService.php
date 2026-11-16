<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterKeyService
{
    const CACHE_KEY = 'openrouter_key_health_status';
    const CACHE_TTL_SECONDS = 300; // 5 minutes

    /**
     * Get the current OpenRouter API key health and expiration status.
     * Uses caching to prevent excessive external requests.
     */
    public static function getStatus(bool $forceCheck = false): array
    {
        $apiKey = config('services.openrouter.key') ?: env('OPENROUTER_API_KEY');

        if (empty($apiKey)) {
            return [
                'is_expired' => true,
                'status' => 'missing',
                'code' => 0,
                'label' => 'Key Missing',
                'message' => 'OpenRouter API key is not configured in .env',
            ];
        }

        if ($forceCheck) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () use ($apiKey) {
            return self::checkKeyDirectly($apiKey);
        });
    }

    /**
     * Directly verify the API key with OpenRouter auth endpoint.
     */
    public static function checkKeyDirectly(string $apiKey): array
    {
        try {
            $response = Http::timeout(4)->withToken($apiKey)->get('https://openrouter.ai/api/v1/auth/key');
            $status = $response->status();
            $body = $response->body();

            if ($status === 401) {
                $err = $response->json('error.message') ?? 'Unauthorized';
                return [
                    'is_expired' => true,
                    'status' => 'expired',
                    'code' => 401,
                    'label' => 'Key Expired / Revoked',
                    'message' => 'OpenRouter API key expired, revoked, or unauthorized: ' . $err,
                ];
            }

            if ($status === 402) {
                return [
                    'is_expired' => true,
                    'status' => 'exhausted',
                    'code' => 402,
                    'label' => 'Credits Exhausted',
                    'message' => 'OpenRouter credit balance is exhausted or depleted.',
                ];
            }

            if ($status === 403) {
                return [
                    'is_expired' => true,
                    'status' => 'revoked',
                    'code' => 403,
                    'label' => 'Key Revoked',
                    'message' => 'OpenRouter API key is deactivated, forbidden, or revoked.',
                ];
            }

            if ($response->successful()) {
                $data = $response->json('data') ?? [];
                $limit = $data['limit'] ?? null;
                $usage = $data['usage'] ?? null;
                if ($limit !== null && $usage !== null && (float) $usage >= (float) $limit) {
                    return [
                        'is_expired' => true,
                        'status' => 'expired',
                        'code' => 200,
                        'label' => 'Usage Limit Reached',
                        'message' => 'OpenRouter key has reached its usage limit.',
                    ];
                }

                return [
                    'is_expired' => false,
                    'status' => 'active',
                    'code' => 200,
                    'label' => 'Online',
                    'message' => 'OpenRouter key is active and online.',
                ];
            }

            // Check if error payload mentions user not found or expired
            if (str_contains($body, 'User not found') || str_contains($body, 'expired') || str_contains($body, 'revoked')) {
                return [
                    'is_expired' => true,
                    'status' => 'expired',
                    'code' => $status,
                    'label' => 'Key Expired / Revoked',
                    'message' => 'OpenRouter reported key expired or revoked.',
                ];
            }

            return [
                'is_expired' => false,
                'status' => 'unknown',
                'code' => $status,
                'label' => 'Status Check Issue',
                'message' => 'OpenRouter responded with status ' . $status,
            ];
        } catch (\Exception $e) {
            Log::debug('OpenRouter key verification network exception: ' . $e->getMessage());
            return [
                'is_expired' => false,
                'status' => 'unreachable',
                'code' => 0,
                'label' => 'Network Issue',
                'message' => 'Could not connect to OpenRouter auth endpoint.',
            ];
        }
    }

    /**
     * Mark the key status based on an error response from chat completions.
     */
    public static function markFromCompletionResponse(int $statusCode, string $body): array
    {
        $isExpired = false;
        $statusStr = 'error';
        $label = 'Error';
        $msg = 'OpenRouter request failed';

        if ($statusCode === 401 || str_contains($body, 'User not found') || str_contains($body, 'Key has expired') || str_contains($body, 'Key revoked') || str_contains($body, 'Invalid API key')) {
            $isExpired = true;
            $statusStr = 'expired';
            $label = 'Key Expired / Revoked';
            $msg = 'OpenRouter API key is expired or revoked (401 Unauthorized).';
        } elseif ($statusCode === 402 || str_contains($body, 'insufficient credits') || str_contains($body, 'balance')) {
            $isExpired = true;
            $statusStr = 'exhausted';
            $label = 'Credits Exhausted';
            $msg = 'OpenRouter credit balance is zero or exhausted (402 Payment Required).';
        } elseif ($statusCode === 403) {
            $isExpired = true;
            $statusStr = 'revoked';
            $label = 'Key Revoked';
            $msg = 'OpenRouter API key is forbidden or revoked (403 Forbidden).';
        }

        $result = [
            'is_expired' => $isExpired,
            'status' => $statusStr,
            'code' => $statusCode,
            'label' => $label,
            'message' => $msg,
        ];

        if ($isExpired) {
            Cache::put(self::CACHE_KEY, $result, self::CACHE_TTL_SECONDS);
        }

        return $result;
    }

    /**
     * Mark the key as active upon a successful completion.
     */
    public static function markActive(): void
    {
        Cache::put(self::CACHE_KEY, [
            'is_expired' => false,
            'status' => 'active',
            'code' => 200,
            'label' => 'Online',
            'message' => 'OpenRouter key is active and online.',
        ], self::CACHE_TTL_SECONDS);
    }
}

