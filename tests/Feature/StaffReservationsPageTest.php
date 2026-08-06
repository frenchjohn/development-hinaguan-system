<?php

namespace Tests\Feature;

use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffReservationsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_reservations_page_shows_pending_online_reservations(): void
    {
        session(['auth_user' => ['id' => 1, 'name' => 'Staff', 'email' => 'staff@example.com', 'role' => 'staff']]);

        Reservation::create([
            'booker_name' => 'Online Booker',
            'phone' => '09170000000',
            'email' => 'online@example.com',
            'check_in' => null, // not yet checked in — the page lists online bookings awaiting action
            'number_of_guests' => 2,
            'reservation_type' => 'online',
            'status' => 'Pending',
            'total_amount' => 1500,
            'amount_paid' => 750,
            'remaining_balance' => 750,
            'payment_status' => 'Partially Paid',
        ]);

        Reservation::create([
            'booker_name' => 'Checked In Guest',
            'phone' => '09170000001',
            'email' => 'checked@example.com',
            'check_in' => now()->toDateString(),
            'number_of_guests' => 1,
            'reservation_type' => 'online',
            'status' => 'Checked In',
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $response = $this->get('/staff/reservations');

        $response->assertOk();
        $response->assertViewHas('reservations', function ($reservations) {
            return $reservations->count() === 1 && $reservations->first()->booker_name === 'Online Booker';
        });
    }
}
