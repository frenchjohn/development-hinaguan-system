<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiKeyService
{
    const CACHE_KEY = 'openai_key_health_status';
    const CACHE_TTL_SECONDS = 300; // 5 minutes cache

    /**
     * Get the current OpenAI API key health, quota, and expiration status.
     * Uses caching to prevent excessive external requests.
     */
    public static function getStatus(bool $forceCheck = false): array
    {
        $apiKey = config('services.openai.key') ?: env('OPENAI_API_KEY');

        if (empty($apiKey)) {
            return [
                'is_expired' => true,
                'status' => 'missing',
                'code' => 0,
                'label' => 'Key Missing',
                'message' => 'OpenAI API key is not configured in .env (OPENAI_API_KEY is empty)',
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
     * Check if OpenAI is currently healthy and has available credits.
     */
    public static function isAvailable(): bool
    {
        $apiKey = config('services.openai.key') ?: env('OPENAI_API_KEY');
        if (empty($apiKey)) {
            return false;
        }

        $status = self::getStatus();
        return empty($status['is_expired']);
    }

    /**
     * Directly verify the API key and quota status with OpenAI endpoint.
     */
    public static function checkKeyDirectly(string $apiKey): array
    {
        try {
            $baseUrl = rtrim(config('services.openai.base_url') ?: env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/');
            
            // Lightweight model retrieval request to test auth and account quota
            $response = Http::timeout(4)->withToken($apiKey)->get("{$baseUrl}/models");
            $status = $response->status();
            $body = $response->body();

            if ($status === 401) {
                $err = $response->json('error.message') ?? 'Invalid API key';
                return [
                    'is_expired' => true,
                    'status' => 'expired',
                    'code' => 401,
                    'label' => 'Key Invalid / Expired',
                    'message' => 'OpenAI API key is invalid or revoked: ' . $err,
                ];
            }

            if ($status === 402 || $status === 429) {
                $err = $response->json('error.message') ?? 'Credit is 0 or quota exceeded';
                $isQuota = str_contains(strtolower($err), 'quota') || str_contains(strtolower($err), 'billing') || str_contains(strtolower($err), 'credit');
                return [
                    'is_expired' => true,
                    'status' => 'exhausted',
                    'code' => $status,
                    'label' => $isQuota ? 'Credits 0 / Quota Exceeded' : 'Rate Limited / Insufficient Credits',
                    'message' => 'OpenAI credit balance is 0 or quota exceeded: ' . $err,
                ];
            }

            if ($status === 403) {
                return [
                    'is_expired' => true,
                    'status' => 'revoked',
                    'code' => 403,
                    'label' => 'Key Forbidden / Blocked',
                    'message' => 'OpenAI access is forbidden or blocked.',
                ];
            }

            if ($response->successful()) {
                return [
                    'is_expired' => false,
                    'status' => 'active',
                    'code' => 200,
                    'label' => 'Online',
                    'message' => 'OpenAI GPT-6 Luna is active and online with available credits.',
                ];
            }

            if (str_contains(strtolower($body), 'insufficient_quota') || str_contains(strtolower($body), 'exceeded your current quota') || str_contains(strtolower($body), 'billing')) {
                return [
                    'is_expired' => true,
                    'status' => 'exhausted',
                    'code' => $status,
                    'label' => 'Credits 0 / Quota Exceeded',
                    'message' => 'OpenAI credit balance is depleted or quota exceeded.',
                ];
            }

            return [
                'is_expired' => false,
                'status' => 'unknown',
                'code' => $status,
                'label' => 'Status Check Issue',
                'message' => 'OpenAI responded with status ' . $status,
            ];
        } catch (\Exception $e) {
            Log::debug('OpenAI key verification network exception: ' . $e->getMessage());
            return [
                'is_expired' => false,
                'status' => 'unreachable',
                'code' => 0,
                'label' => 'Network Issue',
                'message' => 'Could not connect to OpenAI endpoint: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Mark key status dynamically based on response from chat completions.
     */
    public static function markFromCompletionResponse(int $statusCode, string $body): array
    {
        $isExpired = false;
        $statusStr = 'error';
        $label = 'Error';
        $msg = 'OpenAI request failed';

        $bodyLower = strtolower($body);

        if ($statusCode === 401 || str_contains($bodyLower, 'invalid api key') || str_contains($bodyLower, 'incorrect api key')) {
            $isExpired = true;
            $statusStr = 'expired';
            $label = 'Key Invalid / Expired';
            $msg = 'OpenAI API key is invalid or revoked (401 Unauthorized).';
        } elseif ($statusCode === 402 || $statusCode === 429 || str_contains($bodyLower, 'insufficient_quota') || str_contains($bodyLower, 'exceeded your current quota') || str_contains($bodyLower, 'credit') || str_contains($bodyLower, 'billing')) {
            $isExpired = true;
            $statusStr = 'exhausted';
            $label = 'Credits 0 / Quota Exceeded';
            $msg = 'OpenAI credit balance is 0 or quota is depleted (HTTP ' . $statusCode . ').';
        } elseif ($statusCode === 403) {
            $isExpired = true;
            $statusStr = 'revoked';
            $label = 'Access Forbidden';
            $msg = 'OpenAI access is forbidden (403 Forbidden).';
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
            'message' => 'OpenAI GPT-6 Luna is active and online with available credits.',
        ], self::CACHE_TTL_SECONDS);
    }
}
