<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\RescheduleRequest;
use App\Models\StaffAccount;
use App\Services\PhilSmsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReservationRescheduleTest extends TestCase
{
    use RefreshDatabase;

    private function makeStaffSession(): StaffAccount
    {
        $staff = StaffAccount::create([
            'name' => 'Staff Admin',
            'email' => 'staff@example.com',
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

    private function createSampleReservation(string $date = '2026-10-01'): Reservation
    {
        $res = Reservation::create([
            'booker_name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'phone' => '09930457138',
            'reservation_date' => $date,
            'end_date' => $date,
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 4,
            'reservation_type' => 'online',
            'status' => 'Pending',
            'total_amount' => 1000,
            'amount_paid' => 500,
            'remaining_balance' => 500,
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
            'start_date' => $date,
            'end_date' => $date,
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'price_at_booking' => 500,
            'quantity' => 1,
        ]);

        return $res;
    }

    public function test_philsms_formats_reschedule_message_with_prefix()
    {
        Http::fake([
            'https://dashboard.philsms.com/*' => Http::response(['status' => 'success'], 200),
        ]);

        $res = $this->createSampleReservation();
        $service = new PhilSmsService('fake-token');

        $result = $service->sendRescheduleLink($res, 'Please choose a new date at http://example.com/resched');
        $this->assertTrue($result['success']);

        Http::assertSent(function ($request) use ($res) {
            $msg = $request['message'];
            return str_starts_with($msg, "hi {$res->booker_name} of reservation_{$res->id}, ");
        });
    }

    public function test_staff_can_send_reschedule_request_and_generates_24hr_token()
    {
        $this->makeStaffSession();
        $res = $this->createSampleReservation();

        Http::fake([
            'https://dashboard.philsms.com/*' => Http::response(['status' => 'success'], 200),
        ]);

        $response = $this->postJson("/staff/reservations/{$res->id}/send-reschedule-request", [
            'phone' => '09930457138',
            'message' => 'Park is undergoing maintenance on your date. Use {link} to choose a new date.',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $token = $response->json('token');
        $this->assertNotEmpty($token);

        $requestRecord = RescheduleRequest::where('token', $token)->first();
        $this->assertNotNull($requestRecord);
        $this->assertEquals($res->id, $requestRecord->reservation_id);
        $this->assertEquals('pending', $requestRecord->status);
        $this->assertNull($requestRecord->used_at);
        $this->assertTrue(Carbon::parse($requestRecord->expires_at)->isFuture());
    }

    public function test_guest_can_open_valid_rescheduling_link()
    {
        $res = $this->createSampleReservation();
        $req = RescheduleRequest::create([
            'reservation_id' => $res->id,
            'token' => 'valid-token-123',
            'original_date' => $res->reservation_date,
            'status' => 'pending',
            'expires_at' => now()->addHours(24),
            'reason' => 'Park closure',
        ]);

        $response = $this->get("/reservation/reschedule/{$req->token}");
        $response->assertOk();
        $response->assertSee('John Doe');
        $response->assertSee('#' . $res->id);
        $response->assertSee('Choose Your Preferred Date');
    }

    public function test_guest_cannot_use_expired_link()
    {
        $res = $this->createSampleReservation();
        $req = RescheduleRequest::create([
            'reservation_id' => $res->id,
            'token' => 'expired-token-123',
            'original_date' => $res->reservation_date,
            'status' => 'pending',
            'expires_at' => now()->subMinutes(10), // Expired
            'reason' => 'Park closure',
        ]);

        $response = $this->get("/reservation/reschedule/{$req->token}");
        $response->assertOk();
        $response->assertSee('Link Has Expired');
    }

    public function test_guest_submits_preferred_date_and_marks_token_used()
    {
        $res = $this->createSampleReservation();
        $req = RescheduleRequest::create([
            'reservation_id' => $res->id,
            'token' => 'submit-token-123',
            'original_date' => $res->reservation_date,
            'status' => 'pending',
            'expires_at' => now()->addHours(24),
            'reason' => 'Park closure',
        ]);

        $newPreferredDate = now()->addDays(5)->toDateString();

        $response = $this->post("/reservation/reschedule/{$req->token}", [
            'requested_date' => $newPreferredDate,
        ]);

        $response->assertOk();
        $response->assertSee('Request Submitted!');

        $req->refresh();
        $this->assertEquals('submitted', $req->status);
        $this->assertEquals($newPreferredDate, $req->requested_date->toDateString());
        $this->assertNotNull($req->used_at);

        // Accessing the link again shows "Link Already Used"
        $secondVisit = $this->get("/reservation/reschedule/{$req->token}");
        $secondVisit->assertOk();
        $secondVisit->assertSee('Link Already Used');
    }

    public function test_staff_can_view_reschedule_requests_and_approve()
    {
        $staff = $this->makeStaffSession();
        $res = $this->createSampleReservation('2026-10-01');

        $newDate = '2026-10-15';
        $req = RescheduleRequest::create([
            'reservation_id' => $res->id,
            'token' => 'approve-token-123',
            'original_date' => '2026-10-01',
            'requested_date' => $newDate,
            'status' => 'submitted',
            'expires_at' => now()->addHours(24),
            'used_at' => now(),
            'reason' => 'Park closure',
        ]);

        Http::fake([
            'https://dashboard.philsms.com/*' => Http::response(['status' => 'success'], 200),
        ]);

        // Staff lists requests
        $listResponse = $this->getJson('/staff/reschedule-requests');
        $listResponse->assertOk()
            ->assertJsonPath('submitted_count', 1);

        // Staff approves request
        $approveResponse = $this->postJson("/staff/reschedule-requests/{$req->id}/approve");
        $approveResponse->assertOk()
            ->assertJson(['success' => true]);

        // Verify reservation dates updated
        $res->refresh();
        $this->assertEquals($newDate, Carbon::parse($res->reservation_date)->toDateString());

        // Verify amenity date updated
        $ra = $res->reservationAmenities->first();
        $this->assertEquals($newDate, Carbon::parse($ra->start_date)->toDateString());

        // Verify reschedule request marked approved
        $req->refresh();
        $this->assertEquals('approved', $req->status);
        $this->assertEquals($staff->id, $req->approved_by);
        $this->assertNotNull($req->approved_at);
    }

    public function test_staff_can_decline_reschedule_request()
    {
        $staff = $this->makeStaffSession();
        $res = $this->createSampleReservation('2026-10-01');

        $req = RescheduleRequest::create([
            'reservation_id' => $res->id,
            'token' => 'decline-token-123',
            'original_date' => '2026-10-01',
            'requested_date' => '2026-10-15',
            'status' => 'submitted',
            'expires_at' => now()->addHours(24),
            'used_at' => now(),
            'reason' => 'Park closure',
        ]);

        Http::fake([
            'https://dashboard.philsms.com/*' => Http::response(['status' => 'success'], 200),
        ]);

        $response = $this->postJson("/staff/reschedule-requests/{$req->id}/decline", [
            'decline_reason' => 'Park fully booked on this date',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $req->refresh();
        $this->assertEquals('declined', $req->status);
        $this->assertEquals($staff->id, $req->approved_by);
        $this->assertEquals('Park fully booked on this date', $req->decline_reason);
    }

    public function test_multi_day_stay_adaptation_and_session_locking()
    {
        $staff = $this->makeStaffSession();

        // Create a 5-day continuous reservation with Daytime session
        $res = $this->createSampleReservation('2026-10-01');
        $res->total_days = 5;
        $res->start_slot = 'Daytime';
        $res->end_slot = 'Daytime';
        $res->end_date = '2026-10-05';
        $res->save();

        $ra = $res->reservationAmenities->first();
        $ra->end_date = '2026-10-05';
        $ra->start_slot = 'Daytime';
        $ra->end_slot = 'Daytime';
        $ra->save();

        $req = RescheduleRequest::create([
            'reservation_id' => $res->id,
            'token' => 'five-day-token-123',
            'original_date' => '2026-10-01',
            'status' => 'pending',
            'expires_at' => now()->addHours(24),
        ]);

        // 1. Guest views page: should adapt to 5-Day Stay and show session is fixed
        $pageResponse = $this->get("/reservation/reschedule/{$req->token}");
        $pageResponse->assertOk();
        $pageResponse->assertSee('5-Day Stay');
        $pageResponse->assertSee('5 Days Continuous Stay');
        $pageResponse->assertSee('Fixed (same as original)');

        // Original date 2026-10-01 should be disabled in availability
        $availResponse = $this->getJson("/reservation/reschedule/{$req->token}/availability?year=2026&month=9");
        $availResponse->assertOk();
        $this->assertFalse($availResponse->json('availability.2026-10-01'), 'Original start date must be disabled');

        // Submitting the original date must be rejected
        $badSubmitResponse = $this->from("/reservation/reschedule/{$req->token}")
            ->post("/reservation/reschedule/{$req->token}", [
                'requested_date' => '2026-10-01',
            ]);
        $badSubmitResponse->assertSessionHasErrors('requested_date');

        // 2. Submit new check-in date: 2026-10-20
        $newStartDate = '2026-10-20';
        $submitResponse = $this->post("/reservation/reschedule/{$req->token}", [
            'requested_date' => $newStartDate,
        ]);
        $submitResponse->assertOk();
        $submitResponse->assertSee('Request Submitted!');
        $submitResponse->assertSee('Check-In');
        $submitResponse->assertSee('Check-Out');
        $submitResponse->assertSee('October 20, 2026');
        $submitResponse->assertDontSee('Nighttime Session (Fixed)');

        // 3. Staff lists requests: displays full 5-day range and session
        $listResponse = $this->getJson('/staff/reschedule-requests');
        $listResponse->assertOk();
        $items = $listResponse->json('requests');
        $matched = collect($items)->firstWhere('id', $req->id);
        $this->assertNotNull($matched);
        $this->assertEquals(5, $matched['total_days']);
        $this->assertEquals('Daytime', $matched['start_slot']);
        $this->assertStringContainsString('5D, Daytime', $matched['requested_date']);

        // 4. Staff approves: shifts both reservation_date and end_date (2026-10-20 to 2026-10-24)
        Http::fake([
            'https://dashboard.philsms.com/*' => Http::response(['status' => 'success'], 200),
        ]);

        $approveResponse = $this->postJson("/staff/reschedule-requests/{$req->id}/approve");
        $approveResponse->assertOk();

        $res->refresh();
        $this->assertEquals('2026-10-20', Carbon::parse($res->reservation_date)->toDateString());
        $this->assertEquals('2026-10-24', Carbon::parse($res->end_date)->toDateString());
        $this->assertEquals('Daytime', $res->start_slot);
        $this->assertEquals('Daytime', $res->end_slot);

        $ra->refresh();
        $this->assertEquals('2026-10-20', Carbon::parse($ra->start_date)->toDateString());
        $this->assertEquals('2026-10-24', Carbon::parse($ra->end_date)->toDateString());
    }

    public function test_dates_disabled_when_availed_amenity_is_unavailable_in_stay_window()
    {
        // Reservation A: Guest with Cottage A for 5-day stay
        $resA = $this->createSampleReservation('2026-11-01');
        $resA->total_days = 5;
        $resA->start_slot = 'Daytime';
        $resA->end_slot = 'Daytime';
        $resA->end_date = '2026-11-05';
        $resA->save();

        $amenity = Amenity::first();

        $req = RescheduleRequest::create([
            'reservation_id' => $resA->id,
            'token' => 'amenity-conflict-token-123',
            'original_date' => '2026-11-01',
            'status' => 'pending',
            'expires_at' => now()->addHours(24),
        ]);

        // Reservation B: Confirmed booking occupying Cottage A on 2026-11-12
        $resB = $this->createSampleReservation('2026-11-12');
        $resB->status = 'Confirmed';
        $resB->save();

        // Check availability API for November 2026
        $availResponse = $this->getJson("/reservation/reschedule/{$req->token}/availability?year=2026&month=10");
        $availResponse->assertOk();
        $availability = $availResponse->json('availability');

        // Since resB occupies Cottage A on Nov 12, any 5-day stay window covering Nov 12 must be unavailable (false)
        // Nov 08 (Nov 08 - Nov 12 covers Nov 12) -> false
        // Nov 09 (Nov 09 - Nov 13 covers Nov 12) -> false
        // Nov 10 (Nov 10 - Nov 14 covers Nov 12) -> false
        // Nov 11 (Nov 11 - Nov 15 covers Nov 12) -> false
        // Nov 12 (Nov 12 - Nov 16 covers Nov 12) -> false
        $this->assertFalse($availability['2026-11-08'], 'Nov 08 should be unavailable because 5-day stay hits Nov 12');
        $this->assertFalse($availability['2026-11-12'], 'Nov 12 should be unavailable because Cottage is booked');
        // Nov 13 (Nov 13 - Nov 17) does not overlap Nov 12 -> true
        $this->assertTrue($availability['2026-11-13'], 'Nov 13 should be available');
    }
}

