<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\ReservationGuest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffGuestCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_create_a_reservation_with_primary_guest_and_companions(): void
    {
        $amenity = Amenity::create([
            'id' => 'amenity-1',
            'amenities_name' => 'Mountain Picnic Hut',
            'daytime_price' => 2500,
            'nighttime_price' => 3000,
            'additional_per_head' => 150,
            'minimum_capacity' => 5,
            'maximum_capacity' => 15,
            'description' => 'Test amenity',
            'image' => null,
            'status' => true,
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => 1,
                'name' => 'Staff User',
                'email' => 'staff@example.com',
                'role' => 'staff',
            ],
        ])->post(route('staff.checkins.guests.store'), [
            'guest_mode' => 'with_primary',
            'reservation_type' => 'online',
            'check_in' => now()->addDay()->toDateString(),
            'primary_guest' => [
                'first_name' => 'Maria',
                'middle_name' => 'Clara',
                'last_name' => 'Santos',
                'age' => 29,
                'gender' => 'Female',
                'nationality_option' => 'Filipino',
                'phone' => '09171234567',
                'email' => 'maria@example.com',
            ],
            'companions' => [[
                'first_name' => 'Rico',
                'middle_name' => null,
                'last_name' => 'Dela Cruz',
                'age' => 31,
                'gender' => 'Male',
                'nationality_option' => 'Filipino',
                'phone' => '09181234567',
                'email' => 'rico@example.com',
            ]],
            'selected_amenities' => [
                ['amenity_id' => $amenity->id, 'pricing_type' => 'Daytime', 'price_at_booking' => 2500],
            ],
            'total_amount' => 2500,
        ]);

        $response->assertRedirect(route('staff.checkins'));
        $this->assertDatabaseHas('reservations', ['reservation_type' => 'online', 'status' => 'Checked In', 'number_of_guests' => 2]);
        $this->assertDatabaseHas('customers', ['email' => 'maria@example.com']);
        $this->assertDatabaseHas('reservation_guests', ['is_primary_guest' => true]);
        $this->assertDatabaseHas('reservation_amenities', ['amenity_id' => $amenity->id]);
    }
}
