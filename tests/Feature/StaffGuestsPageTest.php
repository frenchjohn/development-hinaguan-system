<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Reservation;
use App\Models\ReservationGuest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffGuestsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_guests_page_lists_customers(): void
    {
        $customer = Customer::create([
            'first_name' => 'Maria',
            'middle_name' => 'Clara',
            'last_name' => 'Santos',
            'age' => 29,
            'gender' => 'Female',
            'nationality' => 'Filipino',
            'is_foreigner' => false,
            'phone' => '09171234567',
            'email' => 'maria@example.com',
        ]);

        // The records page shows guests who have already checked out,
        // so give Maria a completed reservation entry.
        $reservation = Reservation::create([
            'booker_name' => 'Maria Santos',
            'phone' => '09171234567',
            'email' => 'maria@example.com',
            'reservation_date' => now()->toDateString(),
            'check_in' => now()->subDay()->toDateString(),
            'check_out' => now()->toDateString(),
            'number_of_guests' => 1,
            'reservation_type' => 'walk_in',
            'status' => 'Checked Out',
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        ReservationGuest::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'is_primary_guest' => true,
            'checked_out_at' => now(),
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => 1,
                'name' => 'Staff User',
                'email' => 'staff@example.com',
                'role' => 'staff',
            ],
        ])->get(route('staff.records'));

        $response->assertOk()
            ->assertSee('Records')
            ->assertSee($customer->first_name)
            ->assertSee($customer->last_name);
    }
}
