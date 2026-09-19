<?php

namespace Tests\Unit;

use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Services\PhilSmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhilSmsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_normalizes_philippine_phone_numbers(): void
    {
        $this->assertSame('639123456789', PhilSmsService::formatRecipient('+639123456789'));
        $this->assertSame('639123456789', PhilSmsService::formatRecipient('639123456789'));
        $this->assertSame('639123456789', PhilSmsService::formatRecipient('9123456789'));
        $this->assertSame('639123456789', PhilSmsService::formatRecipient('09123456789'));
        $this->assertSame('639123456789', PhilSmsService::formatRecipient('+63 912 345 6789'));
    }

    public function test_it_fails_gracefully_when_token_is_missing(): void
    {
        $service = new PhilSmsService(apiToken: '');
        $result = $service->sendSms('09123456789', 'Hello test');

        $this->assertFalse($result['success']);
        $this->assertSame('MISSING_API_TOKEN', $result['error']);
    }

    public function test_it_sends_sms_successfully_when_gateway_returns_success(): void
    {
        Http::fake([
            'https://dashboard.philsms.com/api/v3/sms/send' => Http::response([
                'status' => 'success',
                'message' => 'SMS queued successfully',
            ], 200),
        ]);

        $service = new PhilSmsService(apiToken: 'test_token_123', apiEndpoint: 'https://dashboard.philsms.com/api/v3/');
        $result = $service->sendSms('09123456789', 'Your reservation is confirmed.');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('639123456789', $result['message']);
    }

    public function test_it_formats_reservation_confirmation_message_and_dispatches(): void
    {
        Http::fake([
            'https://dashboard.philsms.com/api/v3/sms/send' => Http::response([
                'status' => 'success',
                'message' => 'Delivered',
            ], 200),
        ]);

        $reservation = Reservation::create([
            'booker_name' => 'Maria Clara',
            'phone' => '+639171234567',
            'email' => 'maria@example.com',
            'reservation_date' => '2026-10-01',
            'end_date' => '2026-10-01',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 4,
            'total_amount' => 1000.00,
            'amount_paid' => 500.00,
            'remaining_balance' => 500.00,
            'payment_status' => 'Partially Paid',
            'status' => 'Confirmed',
        ]);

        $service = new PhilSmsService(apiToken: 'valid_token');
        $result = $service->sendReservationConfirmation($reservation);

        $this->assertTrue($result['success']);

        Http::assertSent(function ($request) {
            $data = $request->data();
            return $data['recipient'] === '639171234567'
                && str_contains($data['message'], 'Maria')
                && str_contains($data['message'], 'CONFIRMED');
        });
    }
}
