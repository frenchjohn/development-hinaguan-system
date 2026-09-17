<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffReservationCheckInPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function makeStaffSession(): void
    {
        $this->withSession(['auth_user' => ['id' => 1, 'name' => 'Staff User', 'role' => 'staff']]);
    }

    public function test_online_reservation_check_in_sets_amount_paid_to_full_total(): void
    {
        $this->makeStaffSession();

        // 1. Create online reservation: Total 4300, Deposit 150, Remaining 4150
        $reservation = Reservation::create([
            'booker_name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '09123456789',
            'reservation_date' => now()->toDateString(),
            'check_in' => null,
            'check_out' => null,
            'status' => 'Pending',
            'reservation_type' => 'online',
            'number_of_guests' => 1,
            'total_amount' => 4300,
            'amount_paid' => 150,
            'remaining_balance' => 4150,
            'payment_status' => 'Partially Paid',
        ]);

        $amenity = Amenity::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'amenities_name' => 'Payag Test',
            'daytime_price' => 500,
            'nighttime_price' => 600,
            'minimum_capacity' => 1,
            'maximum_capacity' => 10,
            'status' => true,
        ]);

        ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $amenity->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'pricing_type' => 'Daytime',
            'price_at_booking' => 4300,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        // 2. Check in via staff endpoint with adult entrance fee
        $response = $this->postJson("/staff/reservations/{$reservation->id}/check-in", [
            'guest_mode' => 'with_primary',
            'primary_guest' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'age' => '25',
                'gender' => 'Male',
                'is_foreigner' => false,
                'phone' => '09123456789',
                'email' => 'john@example.com',
            ],
            'companions' => [],
            'pool_option' => 'no_pool',
            'include_pool' => '0',
        ]);

        $response->assertOk();

        // 3. Verify reservation updated with amount_paid equal to total_amount
        $reservation->refresh();
        $this->assertEquals('Checked In', $reservation->status);
        $this->assertEquals('Paid', $reservation->payment_status);
        $this->assertEquals(0, (float) $reservation->remaining_balance);
        $this->assertGreaterThanOrEqual(4300, (float) $reservation->total_amount);
        $this->assertEquals((float) $reservation->total_amount, (float) $reservation->amount_paid);

        // 4. Check out reservation and check records page
        $checkoutResponse = $this->postJson("/staff/reservations/{$reservation->id}/check-out");
        $checkoutResponse->assertOk();

        $recordsResponse = $this->get('/staff/records');
        $recordsResponse->assertOk();

        $checkedOutReservations = $recordsResponse->viewData('checkedOutReservations');
        $this->assertTrue($checkedOutReservations->contains('id', $reservation->id));
        $record = $checkedOutReservations->firstWhere('id', $reservation->id);
        $this->assertEquals((float) $record->total_amount, (float) $record->amount_paid);
    }
}
