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

    public function test_it_formats_reschedule_approval_with_checkin_and_checkout_datetimes(): void
    {
        Http::fake([
            'https://dashboard.philsms.com/api/v3/sms/send' => Http::response(['status' => 'success'], 200),
        ]);

        $reservation = Reservation::create([
            'booker_name' => 'John Doe',
            'phone' => '09930457138',
            'email' => 'john@example.com',
            'reservation_date' => '2026-10-07',
            'end_date' => '2026-10-09',
            'start_slot' => 'Nighttime',
            'end_slot' => 'Nighttime',
            'total_days' => 3,
            'number_of_guests' => 2,
            'total_amount' => 1500,
            'amount_paid' => 1500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
            'status' => 'Pending',
        ]);

        $service = new PhilSmsService(apiToken: 'valid_token');
        $result = $service->sendRescheduleApproval($reservation, 'Oct 07, 2026 at 6:00 PM', 'Oct 10, 2026 at 6:00 AM');

        $this->assertTrue($result['success']);

        Http::assertSent(function ($request) {
            $data = $request->data();
            return $data['recipient'] === '639930457138'
                && str_contains($data['message'], 'APPROVED')
                && str_contains($data['message'], 'Check-in: Oct 07, 2026 at 6:00 PM')
                && str_contains($data['message'], 'Check-out: Oct 10, 2026 at 6:00 AM');
        });
    }

    public function test_it_formats_reschedule_declined_message(): void
    {
        Http::fake([
            'https://dashboard.philsms.com/api/v3/sms/send' => Http::response(['status' => 'success'], 200),
        ]);

        $reservation = Reservation::create([
            'booker_name' => 'Jane Smith',
            'phone' => '09930457138',
            'email' => 'jane@example.com',
            'reservation_date' => '2026-10-07',
            'end_date' => '2026-10-07',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 2,
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
            'status' => 'Pending',
        ]);

        $service = new PhilSmsService(apiToken: 'valid_token');
        $result = $service->sendRescheduleDeclined($reservation, 'Park fully booked');

        $this->assertTrue($result['success']);

        Http::assertSent(function ($request) {
            $data = $request->data();
            return $data['recipient'] === '639930457138'
                && str_contains($data['message'], 'unfortunately your reschedule request')
                && str_contains($data['message'], 'declined')
                && str_contains($data['message'], 'wait for an update')
                && str_contains($data['message'], 'Park fully booked');
        });
    }
}
