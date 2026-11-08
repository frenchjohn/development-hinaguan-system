<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Amenity;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\RescheduleRequest;
use App\Models\StaffAccount;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReservationRefundTest extends TestCase
{
    use RefreshDatabase;

    private function makeStaffSession(): StaffAccount
    {
        $staff = StaffAccount::create([
            'name' => 'Staff Maria',
            'email' => 'staff.maria@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->withSession([
            'auth_user' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'email' => $staff->email,
                'role' => 'staff',
            ]
        ]);

        return $staff;
    }

    private function createSampleReservation(float $total = 1000.00, float $downpayment = 500.00): Reservation
    {
        $res = Reservation::create([
            'booker_name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'phone' => '09171234567',
            'reservation_date' => Carbon::tomorrow()->toDateString(),
            'end_date' => Carbon::tomorrow()->toDateString(),
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 4,
            'reservation_type' => 'online',
            'status' => 'Pending',
            'total_amount' => $total,
            'amount_paid' => $downpayment,
            'refunded_amount' => 0.00,
            'remaining_balance' => $total - $downpayment,
            'payment_status' => 'Partially Paid',
        ]);

        $amenity = Amenity::firstOrCreate(
            ['id' => 'cottage-1'],
            [
                'amenities_name' => 'Cottage 1',
                'daytime_price' => 500,
                'nighttime_price' => 700,
                'minimum_capacity' => 5,
                'maximum_capacity' => 15,
                'status' => true,
            ]
        );

        ReservationAmenity::create([
            'reservation_id' => $res->id,
            'amenity_id' => $amenity->id,
            'pricing_type' => 'Daytime',
            'start_date' => $res->reservation_date,
            'end_date' => $res->end_date,
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'price_at_booking' => 500,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        return $res;
    }

    public function test_guest_sees_option_selection_buttons_first_before_entering_process()
    {
        $reservation = $this->createSampleReservation(total: 1000.00, downpayment: 500.00);

        $requestRecord = RescheduleRequest::create([
            'reservation_id' => $reservation->id,
            'token' => RescheduleRequest::generateToken(),
            'status' => 'pending',
            'original_date' => $reservation->reservation_date,
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->get("/reservation/reschedule/{$requestRecord->token}");
        $response->assertStatus(200);
        $response->assertViewIs('guest.reschedule');
        $response->assertViewHas('state', 'active');

        // Verify Step 1 option selection screen and choice buttons exist
        $response->assertSee('optionSelectionScreen');
        $response->assertSee('btnSelectResched');
        $response->assertSee('btnSelectRefund');
        $response->assertSee('Option 1: Reschedule Dates');
        $response->assertSee('Option 2: Cancel &amp; 50% Refund', false);
        $response->assertSee('Choose New Dates');
        $response->assertSee('Request 50% Refund');

        // Verify Step 2 process screen is present with back button
        $response->assertSee('processScreen');
        $response->assertSee('btnBackToOptions');
    }

    public function test_guest_can_request_50_percent_downpayment_refund()
    {
        $reservation = $this->createSampleReservation(total: 1000.00, downpayment: 500.00);

        $requestRecord = RescheduleRequest::create([
            'reservation_id' => $reservation->id,
            'token' => RescheduleRequest::generateToken(),
            'status' => 'pending',
            'original_date' => $reservation->reservation_date,
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->post("/reservation/reschedule/{$requestRecord->token}/refund", [
            'action_type' => 'refund',
            'ewallet_provider' => 'GCash',
            'ewallet_account_number' => '09171234567',
            'ewallet_account_name' => 'Juan Dela Cruz',
            'refund_reason' => 'Family emergency, unable to reschedule',
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('guest.reschedule');
        $response->assertViewHas('state', 'submitted_refund_success');

        $requestRecord->refresh();
        $this->assertEquals('submitted', $requestRecord->status);
        $this->assertEquals('refund', $requestRecord->request_type);
        $this->assertTrue($requestRecord->isRefund());
        $this->assertEquals(250.00, (float) $requestRecord->refund_amount);
        $this->assertEquals('GCash', $requestRecord->ewallet_provider);
        $this->assertEquals('09171234567', $requestRecord->ewallet_account_number);
        $this->assertEquals('Juan Dela Cruz', $requestRecord->ewallet_account_name);

        $log = ActivityLog::where('activity_type', 'reservation_refund_submitted')
            ->where('reservation_id', $reservation->id)
            ->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('50% Refund Requested', $log->title);
    }

    public function test_staff_can_list_and_filter_refund_requests()
    {
        $this->makeStaffSession();

        $reservation = $this->createSampleReservation(total: 1200.00, downpayment: 600.00);

        $refundReq = RescheduleRequest::create([
            'reservation_id' => $reservation->id,
            'token' => RescheduleRequest::generateToken(),
            'request_type' => 'refund',
            'status' => 'submitted',
            'refund_amount' => 300.00,
            'ewallet_provider' => 'GCash',
            'ewallet_account_number' => '09930457138',
            'ewallet_account_name' => 'Maria Clara',
            'original_date' => $reservation->reservation_date,
            'expires_at' => now()->addHours(24),
            'used_at' => now(),
        ]);

        $response = $this->getJson('/staff/reschedule-requests?status=refund');
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $data = $response->json();
        $this->assertGreaterThanOrEqual(1, $data['counts']['refund']);

        $item = collect($data['requests'])->firstWhere('id', $refundReq->id);
        $this->assertNotNull($item);
        $this->assertTrue($item['is_refund']);
        $this->assertEquals('refund', $item['request_type']);
        $this->assertEquals(300.00, $item['refund_amount']);
        $this->assertEquals('₱300.00', $item['refund_amount_formatted']);
        $this->assertEquals('GCash', $item['ewallet_provider']);
        $this->assertEquals('09930457138', $item['ewallet_account_number']);
    }

    public function test_staff_can_approve_and_confirm_manual_refund_sent()
    {
        Http::fake([
            'https://dashboard.philsms.com/*' => Http::response(['status' => 'success'], 200),
        ]);

        $staff = $this->makeStaffSession();
        $reservation = $this->createSampleReservation(total: 1000.00, downpayment: 500.00);

        $refundReq = RescheduleRequest::create([
            'reservation_id' => $reservation->id,
            'token' => RescheduleRequest::generateToken(),
            'request_type' => 'refund',
            'status' => 'submitted',
            'refund_amount' => 250.00,
            'ewallet_provider' => 'GCash',
            'ewallet_account_number' => '09171234567',
            'ewallet_account_name' => 'Juan Dela Cruz',
            'original_date' => $reservation->reservation_date,
            'expires_at' => now()->addHours(24),
            'used_at' => now(),
        ]);

        $response = $this->postJson("/staff/reschedule-requests/{$refundReq->id}/approve", [
            'reference_number' => 'GCASH-REF-889900',
            'notes' => 'Sent manually via Staff GCash phone',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify Reservation financial adjustments
        $reservation->refresh();
        $this->assertEquals(250.00, (float) $reservation->amount_paid, 'amount_paid must be reduced to retained 50%');
        $this->assertEquals(250.00, (float) $reservation->refunded_amount, 'refunded_amount must record the 50% returned');
        $this->assertEquals(0.00, (float) $reservation->remaining_balance);
        $this->assertEquals('Cancelled', $reservation->status);

        // Verify reserved amenities status updated to 'Completed' to respect enum and free timeline
        $amenityRel = $reservation->reservationAmenities()->first();
        $this->assertEquals('Completed', $amenityRel->status);

        // Verify request record updated
        $refundReq->refresh();
        $this->assertEquals('approved', $refundReq->status);
        $this->assertEquals('GCASH-REF-889900', $refundReq->refund_reference_number);
        $this->assertEquals('Sent manually via Staff GCash phone', $refundReq->refund_notes);
        $this->assertNotNull($refundReq->refunded_at);
        $this->assertEquals($staff->id, $refundReq->approved_by);

        // Verify financial activity log has negative payment amount and attribution of who refunded it
        $log = ActivityLog::where('activity_type', 'reservation_refund_processed')
            ->where('reservation_id', $reservation->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals(-250.00, (float) $log->payment_amount);
        $this->assertStringContainsString('Staff Maria', $log->title);
        $this->assertEquals('Staff Maria', $log->actor_name);
        $this->assertEquals('staff', $log->actor_role);
        $this->assertEquals('Staff Maria', $log->metadata['refunded_by']);
        $this->assertStringContainsString('GCASH-REF-889900', $log->description);

        // Verify SMS sent to guest confirming refund is done & successful
        Http::assertSent(function (\Illuminate\Http\Client\Request $req) {
            $msg = (string) ($req->data()['message'] ?? '');
            return str_contains($msg, 'DONE and successful')
                && str_contains($msg, '50% refund')
                && str_contains($msg, 'GCash');
        });
    }

    public function test_staff_can_decline_refund_request()
    {
        Http::fake([
            'https://dashboard.philsms.com/*' => Http::response(['status' => 'success'], 200),
        ]);

        $this->makeStaffSession();
        $reservation = $this->createSampleReservation(total: 1000.00, downpayment: 500.00);

        $refundReq = RescheduleRequest::create([
            'reservation_id' => $reservation->id,
            'token' => RescheduleRequest::generateToken(),
            'request_type' => 'refund',
            'status' => 'submitted',
            'refund_amount' => 250.00,
            'ewallet_provider' => 'GCash',
            'ewallet_account_number' => '09171234567',
            'ewallet_account_name' => 'Juan Dela Cruz',
            'original_date' => $reservation->reservation_date,
            'expires_at' => now()->addHours(24),
            'used_at' => now(),
        ]);

        $response = $this->postJson("/staff/reschedule-requests/{$refundReq->id}/decline", [
            'decline_reason' => 'Account name does not match booker ID',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $refundReq->refresh();
        $this->assertEquals('declined', $refundReq->status);
        $this->assertEquals('Account name does not match booker ID', $refundReq->decline_reason);

        // Reservation should remain active (not cancelled)
        $reservation->refresh();
        $this->assertEquals('Pending', $reservation->status);
        $this->assertEquals(500.00, (float) $reservation->amount_paid);
        $this->assertEquals(0.00, (float) $reservation->refunded_amount);
    }
}

