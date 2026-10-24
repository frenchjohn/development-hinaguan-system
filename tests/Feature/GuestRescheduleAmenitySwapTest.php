<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\RescheduleRequest;
use App\Models\StaffAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GuestRescheduleAmenitySwapTest extends TestCase
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

    private function setupAmenities(): array
    {
        // Cottage 1 (original)
        $c1 = Amenity::create([
            'id' => 'cottage-1',
            'amenities_name' => 'Cottage 1',
            'category' => 'Cottage',
            'daytime_price' => 500,
            'nighttime_price' => 700,
            'minimum_capacity' => 5,
            'maximum_capacity' => 15,
            'status' => true,
        ]);

        // Cottage 2 (same category, same price)
        $c2 = Amenity::create([
            'id' => 'cottage-2',
            'amenities_name' => 'Cottage 2',
            'category' => 'Cottage',
            'daytime_price' => 500,
            'nighttime_price' => 700,
            'minimum_capacity' => 5,
            'maximum_capacity' => 15,
            'status' => true,
        ]);

        // Cottage 3 (same category, DIFFERENT price)
        $c3 = Amenity::create([
            'id' => 'cottage-3',
            'amenities_name' => 'Cottage 3',
            'category' => 'Cottage',
            'daytime_price' => 650,
            'nighttime_price' => 850,
            'minimum_capacity' => 5,
            'maximum_capacity' => 15,
            'status' => true,
        ]);

        // Function Hall 1 (DIFFERENT category)
        $hall = Amenity::create([
            'id' => 'hall-1',
            'amenities_name' => 'Hall 1',
            'category' => 'Function Hall',
            'daytime_price' => 500,
            'nighttime_price' => 700,
            'minimum_capacity' => 20,
            'maximum_capacity' => 50,
            'status' => true,
        ]);

        return compact('c1', 'c2', 'c3', 'hall');
    }

    public function test_availability_endpoint_identifies_booked_amenity_and_offers_same_category_same_price_alternatives()
    {
        $amenities = $this->setupAmenities();
        $targetDate = '2026-11-15';

        // Reservation 1: Guest wanting to reschedule
        $res1 = Reservation::create([
            'booker_name' => 'Guest One',
            'email' => 'guest1@example.com',
            'phone' => '09930457138',
            'reservation_date' => '2026-11-01',
            'end_date' => '2026-11-01',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 4,
            'reservation_type' => 'online',
            'status' => 'Pending',
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $res1->id,
            'amenity_id' => $amenities['c1']->id,
            'pricing_type' => 'Daytime',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-01',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'price_at_booking' => 500,
            'quantity' => 1,
        ]);

        // Reservation 2: Other guest already booked Cottage 1 on targetDate (confirmed)
        $res2 = Reservation::create([
            'booker_name' => 'Existing Booker',
            'email' => 'booker@example.com',
            'phone' => '09123456789',
            'reservation_date' => $targetDate,
            'end_date' => $targetDate,
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 6,
            'reservation_type' => 'online',
            'status' => 'Confirmed',
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $res2->id,
            'amenity_id' => $amenities['c1']->id,
            'pricing_type' => 'Daytime',
            'start_date' => $targetDate,
            'end_date' => $targetDate,
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'price_at_booking' => 500,
            'quantity' => 1,
        ]);

        $token = Str::random(32);
        RescheduleRequest::create([
            'reservation_id' => $res1->id,
            'token' => $token,
            'original_date' => '2026-11-01',
            'expires_at' => now()->addDays(2),
            'status' => 'pending',
        ]);

        $response = $this->getJson("/reservation/reschedule/{$token}/availability?year=2026&month=10");
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['availability'][$targetDate]);
        $details = $data['date_details'][$targetDate];
        $this->assertTrue($details['requires_swap']);
        $this->assertEquals('cottage-1', $details['current_amenity']['id']);

        // Check that Cottage 2 is offered as replacement, but Cottage 3 (price diff) and Hall 1 (category diff) are NOT
        $replacementIds = array_column($details['replacements'], 'id');
        $this->assertContains('cottage-2', $replacementIds);
        $this->assertNotContains('cottage-3', $replacementIds);
        $this->assertNotContains('hall-1', $replacementIds);
    }

    public function test_guest_submits_reschedule_with_valid_replacement_amenity()
    {
        $amenities = $this->setupAmenities();
        $targetDate = '2026-11-15';

        $res1 = Reservation::create([
            'booker_name' => 'Guest One',
            'email' => 'guest1@example.com',
            'phone' => '09930457138',
            'reservation_date' => '2026-11-01',
            'end_date' => '2026-11-01',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 4,
            'reservation_type' => 'online',
            'status' => 'Pending',
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $res1->id,
            'amenity_id' => $amenities['c1']->id,
            'pricing_type' => 'Daytime',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-01',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'price_at_booking' => 500,
            'quantity' => 1,
        ]);

        $token = Str::random(32);
        $req = RescheduleRequest::create([
            'reservation_id' => $res1->id,
            'token' => $token,
            'original_date' => '2026-11-01',
            'expires_at' => now()->addDays(2),
            'status' => 'pending',
        ]);

        $postData = [
            'requested_date' => $targetDate,
            'replacement_amenity_id' => 'cottage-2',
        ];

        $response = $this->post("/reservation/reschedule/{$token}", $postData);
        $response->assertSessionHasNoErrors();
        $response->assertOk();

        $req->refresh();
        $this->assertEquals($targetDate, $req->requested_date->format('Y-m-d'));
        $this->assertEquals('cottage-2', $req->replacement_amenity_id);
    }

    public function test_staff_approves_reschedule_with_unit_swap()
    {
        $this->makeStaffSession();
        $amenities = $this->setupAmenities();
        $targetDate = '2026-11-15';

        $res1 = Reservation::create([
            'booker_name' => 'Guest One',
            'email' => 'guest1@example.com',
            'phone' => '09930457138',
            'reservation_date' => '2026-11-01',
            'end_date' => '2026-11-01',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 4,
            'reservation_type' => 'online',
            'status' => 'Pending',
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $resAmenity = ReservationAmenity::create([
            'reservation_id' => $res1->id,
            'amenity_id' => $amenities['c1']->id,
            'pricing_type' => 'Daytime',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-01',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'price_at_booking' => 500,
            'quantity' => 1,
        ]);

        $req = RescheduleRequest::create([
            'reservation_id' => $res1->id,
            'token' => Str::random(32),
            'original_date' => '2026-11-01',
            'requested_date' => $targetDate,
            'replacement_amenity_id' => 'cottage-2',
            'expires_at' => now()->addDays(2),
            'used_at' => now(),
            'status' => 'submitted',
        ]);

        $response = $this->postJson("/staff/reschedule-requests/{$req->id}/approve");
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $res1->refresh();
        $resAmenity->refresh();
        $req->refresh();

        $this->assertEquals($targetDate, $res1->reservation_date->format('Y-m-d'));
        $this->assertEquals($targetDate, $resAmenity->start_date->format('Y-m-d'));
        $this->assertEquals('cottage-2', $resAmenity->amenity_id);
        $this->assertEquals('approved', $req->status);
    }
}
