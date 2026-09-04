<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffReservationAmenityEditTest extends TestCase
{
    use RefreshDatabase;

    private function staffSession(): void
    {
        session(['auth_user' => ['id' => 1, 'name' => 'Staff Member', 'email' => 'staff@example.com', 'role' => 'staff']]);
    }

    private function createAmenity(string $id, string $name = 'Cottage', int $dayPrice = 500, int $nightPrice = 700): Amenity
    {
        return Amenity::create([
            'id' => $id,
            'amenities_name' => $name . ' ' . $id,
            'daytime_price' => (string) $dayPrice,
            'nighttime_price' => (string) $nightPrice,
            'additional_per_head' => '100',
            'minimum_capacity' => '5',
            'maximum_capacity' => '15',
            'description' => 'Test amenity',
            'image' => null,
            'status' => true,
        ]);
    }

    public function test_can_swap_booked_amenity_to_another_available_amenity(): void
    {
        $this->staffSession();
        $this->createAmenity('cottage-1', 'Cottage', 500, 700);
        $this->createAmenity('cottage-2', 'Luxury Villa', 1000, 1500);

        $reservation = Reservation::create([
            'booker_name' => 'Maria Clara',
            'phone' => '09170000000',
            'email' => 'maria@example.com',
            'reservation_date' => '2026-09-15 00:00:00',
            'end_date' => '2026-09-15 00:00:00',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 2,
            'reservation_type' => 'online',
            'status' => 'Pending',
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $ra = ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => 'cottage-1',
            'start_date' => '2026-09-15',
            'end_date' => '2026-09-15',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 500,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        // Swap amenity from cottage-1 to cottage-2
        $response = $this->postJson("/staff/reservations/{$reservation->id}/update", [
            'booker_name' => 'Maria Clara',
            'email' => 'maria@example.com',
            'phone' => '09170000000',
            'reservation_date' => '2026-09-15',
            'end_date' => '2026-09-15',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'number_of_guests' => 2,
            'status' => 'Confirmed',
            'amenities' => [
                [
                    'id' => $ra->id,
                    'amenity_id' => 'cottage-2',
                    'start_date' => '2026-09-15',
                    'end_date' => '2026-09-15',
                    'start_slot' => 'Daytime',
                    'end_slot' => 'Daytime',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'reservation' => [
                    'total_amount' => 1000,
                    'remaining_balance' => 500,
                    'payment_status' => 'Partially Paid',
                ],
            ]);

        $this->assertDatabaseHas('reservation_amenities', [
            'id' => $ra->id,
            'amenity_id' => 'cottage-2',
            'price_at_booking' => 1000,
        ]);
    }

    public function test_shifting_master_stay_schedule_shifts_amenity_dates_automatically(): void
    {
        $this->staffSession();
        $this->createAmenity('cottage-1', 'Cottage', 500, 700);

        // Initial stay: Sept 10 to Sept 12. Amenity booked for Sept 10 to Sept 11 Nighttime.
        $reservation = Reservation::create([
            'booker_name' => 'Jose Rizal',
            'phone' => '09171112222',
            'email' => 'jose@example.com',
            'reservation_date' => '2026-09-10 00:00:00',
            'end_date' => '2026-09-12 00:00:00',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 3,
            'number_of_guests' => 3,
            'reservation_type' => 'online',
            'status' => 'Confirmed',
            'total_amount' => 1900,
            'amount_paid' => 1900,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $ra = ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => 'cottage-1',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-11',
            'start_slot' => 'Nighttime',
            'end_slot' => 'Nighttime',
            'day_slots_count' => 1,
            'night_slots_count' => 2,
            'pricing_type' => 'Continuous Stay (2D)',
            'price_at_booking' => 1900,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        // Shift master stay schedule by +10 days: Sept 20 to Sept 22
        $response = $this->postJson("/staff/reservations/{$reservation->id}/update", [
            'booker_name' => 'Jose Rizal',
            'email' => 'jose@example.com',
            'phone' => '09171112222',
            'reservation_date' => '2026-09-20',
            'end_date' => '2026-09-22',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'number_of_guests' => 3,
            'status' => 'Confirmed',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        // Verify amenity was automatically shifted by +10 days to Sept 20 - Sept 21
        $this->assertDatabaseHas('reservation_amenities', [
            'id' => $ra->id,
            'amenity_id' => 'cottage-1',
            'start_date' => '2026-09-20 00:00:00',
            'end_date' => '2026-09-21 00:00:00',
            'start_slot' => 'Nighttime',
            'end_slot' => 'Nighttime',
        ]);
    }

    public function test_blocks_update_when_amenity_is_unavailable_and_returns_exact_error_message(): void
    {
        $this->staffSession();
        $this->createAmenity('cottage-1', 'Cottage', 500, 700);

        // Reservation 1 occupying cottage-1 on Sept 20 Daytime
        $res1 = Reservation::create([
            'booker_name' => 'Occupant Guest',
            'phone' => '09173334444',
            'email' => 'occupant@example.com',
            'reservation_date' => '2026-09-20 00:00:00',
            'end_date' => '2026-09-20 00:00:00',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 2,
            'reservation_type' => 'online',
            'status' => 'Confirmed',
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $res1->id,
            'amenity_id' => 'cottage-1',
            'start_date' => '2026-09-20',
            'end_date' => '2026-09-20',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 500,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        // Reservation 2 attempting to move to Sept 20 Daytime with cottage-1
        $res2 = Reservation::create([
            'booker_name' => 'Challenger Guest',
            'phone' => '09175556666',
            'email' => 'challenger@example.com',
            'reservation_date' => '2026-09-10 00:00:00',
            'end_date' => '2026-09-10 00:00:00',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 2,
            'reservation_type' => 'online',
            'status' => 'Pending',
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $res2->id,
            'amenity_id' => 'cottage-1',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-10',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 500,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        // Reschedule res2 to Sept 20 -> should be blocked because cottage-1 is taken
        $response = $this->postJson("/staff/reservations/{$res2->id}/update", [
            'booker_name' => 'Challenger Guest',
            'email' => 'challenger@example.com',
            'phone' => '09175556666',
            'reservation_date' => '2026-09-20',
            'end_date' => '2026-09-20',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'number_of_guests' => 2,
            'status' => 'Pending',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'cant change date cause amenity aavailed is not available on selected date',
            ]);
    }
}
