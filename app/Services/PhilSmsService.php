<?php

namespace App\Services;

use App\Models\ParkSetting;
use App\Models\Reservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PhilSmsService
{
    protected string $apiEndpoint;
    protected string $apiToken;
    protected string $senderId;

    public function __construct(?string $apiToken = null, ?string $apiEndpoint = null, ?string $senderId = null)
    {
        $this->apiEndpoint = $apiEndpoint ?? (string) config('philsms.api_endpoint', 'https://dashboard.philsms.com/api/v3/');
        $rawToken = $apiToken ?? (string) config('philsms.api_token', '');
        $this->apiToken = rtrim(trim($rawToken, "\"' \t\n\r"), "_");
        $this->senderId = $senderId ?? (string) config('philsms.sender_id', 'PhilSMS');
    }

    /**
     * Format a Philippine phone number for user-facing display (e.g. 09XXXXXXXXX).
     */
    public static function formatDisplayPhone(?string $phone): string
    {
        if (empty($phone)) {
            return '—';
        }

        $digits = preg_replace('/\D/', '', $phone);

        // If it starts with 639 and is 12 digits, convert to 09XXXXXXXXX
        if (str_starts_with($digits, '639') && strlen($digits) === 12) {
            return '09' . substr($digits, 3);
        }

        // If it starts with 9 and is 10 digits, convert to 09XXXXXXXXX
        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '0' . $digits;
        }

        // If it starts with 09 and is 11 digits, keep as is
        if (str_starts_with($digits, '09') && strlen($digits) === 11) {
            return $digits;
        }

        return $phone;
    }

    /**
     * Normalize Philippine phone numbers to 12-digit 639XXXXXXXXX format required by PhilSMS API.
     */
    public static function formatRecipient(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        // If it starts with 639 and is 12 digits (e.g. 639930457138), keep as is
        if (str_starts_with($digits, '639') && strlen($digits) === 12) {
            return $digits;
        }

        // If it starts with 09 and is 11 digits (e.g. 09930457138), convert to 639930457138
        if (str_starts_with($digits, '09') && strlen($digits) === 11) {
            return '63' . substr($digits, 1);
        }

        // If it starts with 9 and is 10 digits (e.g. 9930457138), convert to 639930457138
        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '63' . $digits;
        }

        return $digits;
    }

    /**
     * Send an SMS via PhilSMS API v3.
     *
     * @param  string  $recipient  Mobile phone number
     * @param  string  $message    Text content
     * @return array{success: bool, message: string, error: ?string, data: ?array}
     */
    public function sendSms(string $recipient, string $message): array
    {
        $formattedRecipient = self::formatRecipient($recipient);

        if (empty($this->apiToken)) {
            Log::warning('[PhilSMS] API token is not configured in .env (PHILSMS_API_TOKEN). Skipping SMS dispatch.');
            return [
                'success' => false,
                'message' => 'PhilSMS API token is not configured in .env',
                'error' => 'MISSING_API_TOKEN',
                'data' => null,
            ];
        }

        if (empty($formattedRecipient) || strlen($formattedRecipient) < 10) {
            Log::warning("[PhilSMS] Invalid recipient mobile number: '{$recipient}'");
            return [
                'success' => false,
                'message' => "Invalid recipient mobile number: '{$recipient}'",
                'error' => 'INVALID_RECIPIENT',
                'data' => null,
            ];
        }

        $endpoint = rtrim($this->apiEndpoint, '/') . '/sms/send';

        $payload = [
            'recipient' => $formattedRecipient,
            'sender_id' => !empty($this->senderId) ? $this->senderId : 'PhilSMS',
            'type' => 'plain',
            'message' => $message,
        ];

        try {
            Log::info("[PhilSMS] Dispatching SMS to {$formattedRecipient} via {$endpoint}");

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . trim($this->apiToken),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(15)->post($endpoint, $payload);

            $status = $response->status();
            $body = $response->json();

            Log::info("[PhilSMS] Response HTTP {$status}", ['body' => $body]);

            // PhilSMS returns {"status": "success", ...} or HTTP 200/201 on success
            $responseStatus = is_array($body) ? ($body['status'] ?? null) : null;
            $isSuccess = $response->successful() && $responseStatus !== 'error';

            if ($isSuccess) {
                return [
                    'success' => true,
                    'message' => "SMS successfully sent to {$formattedRecipient}.",
                    'error' => null,
                    'data' => $body,
                ];
            }

            $errMsg = is_array($body)
                ? ($body['message'] ?? $body['error'] ?? "PhilSMS returned HTTP {$status}")
                : "PhilSMS returned HTTP {$status}";

            Log::error("[PhilSMS] SMS dispatch failed: {$errMsg}", ['response' => $body]);

            return [
                'success' => false,
                'message' => $errMsg,
                'error' => 'API_ERROR_' . $status,
                'data' => $body,
            ];
        } catch (Throwable $e) {
            Log::error('[PhilSMS] Exception during SMS sending: ' . $e->getMessage(), ['exception' => $e]);

            return [
                'success' => false,
                'message' => 'Network error connecting to PhilSMS gateway: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Send reservation confirmation SMS to the booker.
     *
     * @param  Reservation  $reservation
     * @return array{success: bool, message: string, error: ?string, data: ?array}
     */
    public function sendReservationConfirmation(Reservation $reservation): array
    {
        $phone = $reservation->phone ?? '';
        if (empty(trim($phone))) {
            return [
                'success' => false,
                'message' => 'No phone number provided for this reservation.',
                'error' => 'NO_PHONE_NUMBER',
                'data' => null,
            ];
        }

        $parkSettings = ParkSetting::first();
        $parkContact = $parkSettings->contact_number ?? '0917 861 8383';

        // Format Date(s)
        $startDate = $reservation->reservation_date
            ? Carbon::parse($reservation->reservation_date)->format('M d, Y')
            : ($reservation->check_in ? Carbon::parse($reservation->check_in)->format('M d, Y') : 'your reservation date');

        $endDate = $reservation->end_date
            ? Carbon::parse($reservation->end_date)->format('M d, Y')
            : ($reservation->check_out ? Carbon::parse($reservation->check_out)->format('M d, Y') : null);

        $dateText = ($endDate && $endDate !== $startDate)
            ? "{$startDate} to {$endDate}"
            : $startDate;

        $slotText = $reservation->start_slot ?? 'Daytime';
        if (!empty($reservation->end_slot) && $reservation->end_slot !== $slotText) {
            $slotText .= " - {$reservation->end_slot}";
        }

        $firstName = trim(explode(' ', (string) $reservation->booker_name)[0] ?? 'Guest');
        if (empty($firstName)) {
            $firstName = 'Guest';
        }

        $message = "Hinaguan Park: Hi {$firstName}! Res #{$reservation->id} is CONFIRMED. Arrive on {$dateText} ({$slotText}). Bring valid ID & QR pass. Call: {$parkContact}.";

        // Guarantee single SMS limit (160 characters = 1 SMS unit)
        if (mb_strlen($message) > 160) {
            $message = "Hinaguan Park: Res #{$reservation->id} is CONFIRMED! Arrive on {$dateText} ({$slotText}). Bring ID & QR pass. Call: {$parkContact}.";
            if (mb_strlen($message) > 160) {
                $message = mb_substr($message, 0, 157) . '...';
            }
        }

        return $this->sendSms($phone, $message);
    }

    /**
     * Send reschedule request link SMS to the booker.
     * Automatically prefixes message with: "hi bookername of reservation_id" then actual message.
     *
     * @param  Reservation  $reservation
     * @param  string       $messageBody
     * @param  string|null  $recipientPhone
     * @return array{success: bool, message: string, error: ?string, data: ?array}
     */
    public function sendRescheduleLink(Reservation $reservation, string $messageBody, ?string $recipientPhone = null): array
    {
        $phone = $recipientPhone ?: ($reservation->phone ?? '');
        if (empty(trim($phone))) {
            return [
                'success' => false,
                'message' => 'No phone number provided for this reservation.',
                'error' => 'NO_PHONE_NUMBER',
                'data' => null,
            ];
        }

        $bookerName = trim((string) $reservation->booker_name) ?: 'Guest';
        $prefix = "hi {$bookerName} of reservation_{$reservation->id}, ";
        $fullMessage = $prefix . trim($messageBody);

        return $this->sendSms($phone, $fullMessage);
    }

    /**
     * Send reschedule approval SMS to the booker.
     *
     * @param  Reservation  $reservation
     * @param  string       $checkInText   Formatted check-in date & time (or full schedule text)
     * @param  string|null  $checkOutText  Formatted check-out date & time
     * @return array{success: bool, message: string, error: ?string, data: ?array}
     */
    public function sendRescheduleApproval(Reservation $reservation, string $checkInText, ?string $checkOutText = null): array
    {
        $phone = $reservation->phone ?? '';
        if (empty(trim($phone))) {
            return [
                'success' => false,
                'message' => 'No phone number provided for this reservation.',
                'error' => 'NO_PHONE_NUMBER',
                'data' => null,
            ];
        }

        $parkSettings = ParkSetting::first();
        $parkContact = $parkSettings->contact_number ?? '0917 861 8383';
        $firstName = trim(explode(' ', (string) $reservation->booker_name)[0] ?? 'Guest');

        if (!empty($checkOutText)) {
            $scheduleText = "Check-in: {$checkInText}, Check-out: {$checkOutText}";
        } else {
            $scheduleText = $checkInText;
        }

        $message = "Hinaguan Nature Park: Hi {$firstName}! Your reschedule request for Reservation #{$reservation->id} has been APPROVED. Your new schedule is: {$scheduleText}. See you there! Inquiries: {$parkContact}.";

        return $this->sendSms($phone, $message);
    }

    /**
     * Send reschedule declined SMS to the booker.
     *
     * @param  Reservation  $reservation
     * @param  string|null  $reason
     * @return array{success: bool, message: string, error: ?string, data: ?array}
     */
    public function sendRescheduleDeclined(Reservation $reservation, ?string $reason = null): array
    {
        $phone = $reservation->phone ?? '';
        if (empty(trim($phone))) {
            return [
                'success' => false,
                'message' => 'No phone number provided for this reservation.',
                'error' => 'NO_PHONE_NUMBER',
                'data' => null,
            ];
        }

        $parkSettings = ParkSetting::first();
        $parkContact = $parkSettings->contact_number ?? '0917 861 8383';
        $firstName = trim(explode(' ', (string) $reservation->booker_name)[0] ?? 'Guest');

        $reasonText = !empty($reason) ? " Reason: {$reason}." : '';
        $message = "Hinaguan Nature Park: Hi {$firstName}, unfortunately your reschedule request for Reservation #{$reservation->id} has been declined.{$reasonText} Please wait for an update or contact us at {$parkContact}.";

        return $this->sendSms($phone, $message);
    }
}
