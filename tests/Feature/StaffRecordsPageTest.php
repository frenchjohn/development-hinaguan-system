<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Reservation;
use App\Models\ReservationGuest;
use App\Models\StaffAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffRecordsPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeStaffSession(): void
    {
        $this->withSession(['auth_user' => ['id' => 1, 'name' => 'Staff User', 'role' => 'staff']]);
    }

    public function test_active_reservation_with_partial_checkout_does_not_appear_in_completed_reservations()
    {
        $this->makeStaffSession();

        // 1. Create a reservation that is Checked In with 3 guests
        $activeReservation = Reservation::create([
            'booker_name' => 'Yay Active',
            'email' => 'yay@example.com',
            'phone' => '09123456789',
            'reservation_date' => now()->toDateString(),
            'check_in' => now()->subHours(2)->toDateTimeString(),
            'check_out' => null,
            'status' => 'Checked In',
            'reservation_type' => 'online',
            'number_of_guests' => 3,
            'total_amount' => 1500,
            'amount_paid' => 1500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $mainCustomer = Customer::create([
            'first_name' => 'Yay',
            'last_name' => 'Main',
            'email' => 'yay@example.com',
            'gender' => 'Male',
        ]);

        $companion1 = Customer::create([
            'first_name' => 'Early',
            'last_name' => 'Leaver',
            'email' => 'early@example.com',
            'gender' => 'Female',
        ]);

        $companion2 = Customer::create([
            'first_name' => 'Still',
            'last_name' => 'Inside',
            'email' => 'inside@example.com',
            'gender' => 'Male',
        ]);

        // Main guest still inside
        ReservationGuest::create([
            'reservation_id' => $activeReservation->id,
            'customer_id' => $mainCustomer->id,
            'is_primary_guest' => true,
            'checked_out_at' => null,
        ]);

        // Companion 1 has checked out early
        ReservationGuest::create([
            'reservation_id' => $activeReservation->id,
            'customer_id' => $companion1->id,
            'is_primary_guest' => false,
            'checked_out_at' => now()->subHour()->toDateTimeString(),
        ]);

        // Companion 2 still inside
        ReservationGuest::create([
            'reservation_id' => $activeReservation->id,
            'customer_id' => $companion2->id,
            'is_primary_guest' => false,
            'checked_out_at' => null,
        ]);

        // 2. Create a fully checked-out reservation
        $completedReservation = Reservation::create([
            'booker_name' => 'Fully Completed',
            'email' => 'completed@example.com',
            'phone' => '09999999999',
            'reservation_date' => now()->subDay()->toDateString(),
            'check_in' => now()->subDay()->toDateTimeString(),
            'check_out' => now()->subDay()->addHours(3)->toDateTimeString(),
            'status' => 'Checked Out',
            'reservation_type' => 'walk_in',
            'number_of_guests' => 1,
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $completedCustomer = Customer::create([
            'first_name' => 'Fully',
            'last_name' => 'Completed',
            'email' => 'completed@example.com',
            'gender' => 'Male',
        ]);

        ReservationGuest::create([
            'reservation_id' => $completedReservation->id,
            'customer_id' => $completedCustomer->id,
            'is_primary_guest' => true,
            'checked_out_at' => now()->subDay()->addHours(3)->toDateTimeString(),
        ]);

        // 3. Request staff records page
        $response = $this->get('/staff/records');
        $response->assertOk();

        // Under Guest Records: Early Leaver IS present
        $response->assertSee('Early Leaver');
        // Under Guest Records: Still Inside is NOT present
        $response->assertDontSee('Still Inside');

        // Under Completed Reservations: Fully Completed IS present
        $response->assertSee('Fully Completed');

        // Under Completed Reservations: Active Reservation (Yay Active) is NOT in the table of completed reservations
        // Let's check view data directly:
        $checkedOutRes = $response->viewData('checkedOutReservations');
        $this->assertCount(1, $checkedOutRes);
        $this->assertEquals($completedReservation->id, $checkedOutRes->first()->id);
        $this->assertFalse($checkedOutRes->contains('id', $activeReservation->id));
    }

    public function test_completed_reservation_bulk_companions_are_grouped_in_records_table()
    {
        $this->makeStaffSession();

        $completedReservation = Reservation::create([
            'booker_name' => 'Bulk Booker',
            'email' => 'bulk@example.com',
            'phone' => '09991234567',
            'reservation_date' => now()->subDay()->toDateString(),
            'check_in' => now()->subDay()->toDateTimeString(),
            'check_out' => now()->subDay()->addHours(4)->toDateTimeString(),
            'status' => 'Checked Out',
            'reservation_type' => 'online',
            'number_of_guests' => 3,
            'total_amount' => 3000,
            'amount_paid' => 3000,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $mainCustomer = Customer::create([
            'first_name' => 'Bulk',
            'last_name' => 'Booker',
            'email' => 'bulk@example.com',
            'gender' => 'Male',
            'age' => 35,
        ]);

        ReservationGuest::create([
            'reservation_id' => $completedReservation->id,
            'customer_id' => $mainCustomer->id,
            'is_primary_guest' => true,
            'checked_out_at' => now()->subDay()->addHours(4)->toDateTimeString(),
        ]);

        // Create 2 bulk companions (Companion C0 and Companion C1) with same demographics (30, Male, Filipino)
        $bulkComp1 = Customer::create([
            'first_name' => 'Companion C0',
            'last_name' => '',
            'gender' => 'Male',
            'age' => 30,
            'is_foreigner' => false,
        ]);

        ReservationGuest::create([
            'reservation_id' => $completedReservation->id,
            'customer_id' => $bulkComp1->id,
            'is_primary_guest' => false,
            'checked_out_at' => now()->subDay()->addHours(4)->toDateTimeString(),
        ]);

        $bulkComp2 = Customer::create([
            'first_name' => 'Companion C1',
            'last_name' => '',
            'gender' => 'Male',
            'age' => 30,
            'is_foreigner' => false,
        ]);

        ReservationGuest::create([
            'reservation_id' => $completedReservation->id,
            'customer_id' => $bulkComp2->id,
            'is_primary_guest' => false,
            'checked_out_at' => now()->subDay()->addHours(4)->toDateTimeString(),
        ]);

        $response = $this->get('/staff/records');
        $response->assertOk();

        // Under completed reservations expansion:
        // Bulk companions should be grouped with "Bulk Companions", "2x", and "18-59"
        $content = $response->getContent();
        $this->assertStringContainsString('Bulk Companions', $content);
        $this->assertStringContainsString('2x', $content);
        $this->assertStringContainsString('18-59', $content);
        $this->assertStringContainsString('cell-person__avatar--bulk', $content);
        $this->assertMatchesRegularExpression('/companion-row.*?Bulk Companions.*?2x/s', $content);
    }
}
