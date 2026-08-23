<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisabledUnavailableAmenityOptionTest extends TestCase
{
    use RefreshDatabase;

    private function staffSession(): void
    {
        session(['auth_user' => ['id' => 1, 'name' => 'Staff Member', 'email' => 'staff@example.com', 'role' => 'staff']]);
    }

    private function createAmenity(string $id, string $name = 'Cottage'): Amenity
    {
        return Amenity::create([
            'id' => $id,
            'amenities_name' => $name . ' ' . $id,
            'daytime_price' => '500',
            'nighttime_price' => '700',
            'daytime_aircon_price' => '800',
            'nighttime_aircon_price' => '1000',
            'additional_per_head' => '100',
            'minimum_capacity' => '5',
            'maximum_capacity' => '15',
            'description' => 'Test amenity',
            'image' => null,
            'status' => true,
        ]);
    }

    public function test_returns_unavailable_amenity_ids_for_check_amenities_availability_endpoint(): void
    {
        $this->staffSession();
        $this->createAmenity('cottage-1', 'Cottage 1');
        $this->createAmenity('cottage-2', 'Cottage 2');

        // Reservation 1 occupying cottage-1 on Sept 25 Daytime
        $res1 = Reservation::create([
            'booker_name' => 'Guest 1',
            'phone' => '09170001111',
            'email' => 'guest1@example.com',
            'reservation_date' => '2026-09-25 00:00:00',
            'end_date' => '2026-09-25 00:00:00',
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
            'start_date' => '2026-09-25',
            'end_date' => '2026-09-25',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 500,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        // Reservation 2 being edited by staff
        $res2 = Reservation::create([
            'booker_name' => 'Guest 2',
            'phone' => '09170002222',
            'email' => 'guest2@example.com',
            'reservation_date' => '2026-09-25 00:00:00',
            'end_date' => '2026-09-25 00:00:00',
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

        // Check availability for Sept 25 Daytime
        $response = $this->postJson("/staff/reservations/{$res2->id}/check-amenities-availability", [
            'ranges' => [
                [
                    'index' => 0,
                    'start_date' => '2026-09-25',
                    'end_date' => '2026-09-25',
                    'start_slot' => 'Daytime',
                    'end_slot' => 'Daytime',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'availability' => [
                    '0' => ['cottage-1'],
                ],
            ]);
    }
}
