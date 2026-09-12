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

        // Under Guest Records: Early Leaver IS present in checkedOutGuests collection
        $checkedOutGuests = $response->viewData('checkedOutGuests');
        $this->assertTrue($checkedOutGuests->pluck('customer_id')->contains($companion1->id));
        // Still Inside is NOT present in checkedOutGuests
        $this->assertFalse($checkedOutGuests->pluck('customer_id')->contains($companion2->id));

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

    public function test_records_table_displays_clean_id_without_tag_icon_and_omits_pool_badge_and_supplies_modal_charges_data()
    {
        $this->makeStaffSession();

        $completedReservation = Reservation::create([
            'booker_name' => 'John Clean',
            'email' => 'clean@example.com',
            'phone' => '09129876543',
            'reservation_date' => now()->subDays(2)->toDateString(),
            'check_in' => now()->subDays(2)->setTime(8, 0)->toDateTimeString(),
            'check_out' => now()->subDays(2)->setTime(17, 0)->toDateTimeString(),
            'status' => 'Checked Out',
            'reservation_type' => 'walk_in',
            'number_of_guests' => 2,
            'total_amount' => 1200,
            'amount_paid' => 1200,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $leadCustomer = Customer::create([
            'first_name' => 'John',
            'last_name' => 'Clean',
            'email' => 'clean@example.com',
            'gender' => 'Male',
            'age' => 28,
        ]);

        ReservationGuest::create([
            'reservation_id' => $completedReservation->id,
            'customer_id' => $leadCustomer->id,
            'is_primary_guest' => true,
            'has_pool_access' => true,
            'checked_out_at' => now()->subDays(2)->setTime(17, 0)->toDateTimeString(),
        ]);

        // Add an entrance fee with pool
        \App\Models\ReservationEntranceFee::create([
            'reservation_id' => $completedReservation->id,
            'pricing_type' => 'Daytime',
            'base_entrance_fee' => 300,
            'adult_count' => 2,
            'child_count' => 0,
            'total_entrance_fee' => 300,
            'pool_fee' => 200,
            'pool_option' => 'with_pool',
            'pool_access_count' => 2,
        ]);

        // Add a post-checkout / additional charge
        \App\Models\ReservationCharge::create([
            'reservation_id' => $completedReservation->id,
            'description' => 'Late checkout fee - 1 hour',
            'charge_type' => 'others',
            'amount' => 150,
            'status' => 'paid',
        ]);

        $response = $this->get('/staff/records');
        $response->assertOk();

        $content = $response->getContent();

        // Verify ID is displayed as #<id> cleanly
        $this->assertStringContainsString("#{$completedReservation->id}", $content);

        // Verify the tag icon svg path M5.5 3A2.5 is not in the table display
        $this->assertStringNotContainsString('M5.5 3A2.5', $content);

        // Verify the reservation data sent to JS has charges, entrance fee, and timestamps
        $resData = $response->viewData('reservationData');
        $this->assertArrayHasKey($completedReservation->id, $resData);

        $resEntry = $resData[$completedReservation->id];
        $this->assertCount(1, $resEntry['reservation_charges']);
        $this->assertEquals('Late checkout fee - 1 hour', $resEntry['reservation_charges'][0]['description']);
        $this->assertEquals(150, $resEntry['reservation_charges'][0]['amount']);
        $this->assertNotNull($resEntry['check_in']);
        $this->assertNotNull($resEntry['check_out']);
        $this->assertTrue($resEntry['reservation_guests'][0]['has_pool_access']);
        $this->assertNotNull($resEntry['entrance_fee']);
        $this->assertEquals(200, $resEntry['entrance_fee']['pool_fee']);
    }

    public function test_companion_group_checkout_display_same_and_different_dates()
    {
        $this->makeStaffSession();

        // 1. Reservation where companions share the SAME checkout date
        $resSame = Reservation::create([
            'booker_name' => 'Same Checkout Family',
            'email' => 'same@example.com',
            'phone' => '09111111111',
            'reservation_date' => '2025-10-14',
            'check_in' => '2025-10-14 08:00:00',
            'check_out' => '2025-10-14 17:00:00',
            'status' => 'Checked Out',
            'reservation_type' => 'walk_in',
            'number_of_guests' => 3,
            'total_amount' => 1000,
            'amount_paid' => 1000,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $leadSame = Customer::create(['first_name' => 'Same', 'last_name' => 'Family', 'gender' => 'Male']);
        $compSame1 = Customer::create(['first_name' => 'Companion 1', 'last_name' => '', 'age' => 25, 'gender' => 'Female']);
        $compSame2 = Customer::create(['first_name' => 'Companion 2', 'last_name' => '', 'age' => 25, 'gender' => 'Female']);

        ReservationGuest::create([
            'reservation_id' => $resSame->id,
            'customer_id' => $leadSame->id,
            'is_primary_guest' => true,
            'checked_out_at' => '2025-10-14 17:00:00',
        ]);
        ReservationGuest::create([
            'reservation_id' => $resSame->id,
            'customer_id' => $compSame1->id,
            'is_primary_guest' => false,
            'checked_out_at' => '2025-10-14 17:00:00',
        ]);
        ReservationGuest::create([
            'reservation_id' => $resSame->id,
            'customer_id' => $compSame2->id,
            'is_primary_guest' => false,
            'checked_out_at' => '2025-10-14 17:00:00',
        ]);

        // 2. Reservation where companions have DIFFERENT checkout dates
        $resDiff = Reservation::create([
            'booker_name' => 'Different Checkout Group',
            'email' => 'diff@example.com',
            'phone' => '09222222222',
            'reservation_date' => '2025-10-14',
            'check_in' => '2025-10-14 08:00:00',
            'check_out' => '2025-10-14 17:00:00',
            'status' => 'Checked Out',
            'reservation_type' => 'walk_in',
            'number_of_guests' => 4,
            'total_amount' => 1500,
            'amount_paid' => 1500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $leadDiff = Customer::create(['first_name' => 'Diff', 'last_name' => 'Group', 'gender' => 'Male']);
        $compDiff1 = Customer::create(['first_name' => 'Companion A', 'last_name' => '', 'age' => 22, 'gender' => 'Male']);
        $compDiff2 = Customer::create(['first_name' => 'Companion B', 'last_name' => '', 'age' => 22, 'gender' => 'Male']);
        $compDiff3 = Customer::create(['first_name' => 'Companion C', 'last_name' => '', 'age' => 22, 'gender' => 'Male']);

        ReservationGuest::create([
            'reservation_id' => $resDiff->id,
            'customer_id' => $leadDiff->id,
            'is_primary_guest' => true,
            'checked_out_at' => '2025-10-14 17:00:00',
        ]);
        // 1 checked out early at 02:00 PM
        ReservationGuest::create([
            'reservation_id' => $resDiff->id,
            'customer_id' => $compDiff1->id,
            'is_primary_guest' => false,
            'checked_out_at' => '2025-10-14 14:00:00',
        ]);
        // 2 checked out at 05:00 PM
        ReservationGuest::create([
            'reservation_id' => $resDiff->id,
            'customer_id' => $compDiff2->id,
            'is_primary_guest' => false,
            'checked_out_at' => '2025-10-14 17:00:00',
        ]);
        ReservationGuest::create([
            'reservation_id' => $resDiff->id,
            'customer_id' => $compDiff3->id,
            'is_primary_guest' => false,
            'checked_out_at' => '2025-10-14 17:00:00',
        ]);

        $response = $this->get('/staff/records');
        $response->assertOk();

        $content = $response->getContent();
        $resData = $response->viewData('reservationData');

        // Verify Same Checkout: Displays single date without "2x ("
        $this->assertEquals('Oct 14, 2025 · 05:00 PM', $resData[$resSame->id]['companions_checkout_summary']);
        $this->assertStringContainsString('Oct 14, 2025 · 05:00 PM', $content);

        // Verify Different Checkout: Displays "1x (Oct 14, 2025 · 02:00 PM), 2x (Oct 14, 2025 · 05:00 PM)"
        $expectedDiffSummary = '1x (Oct 14, 2025 · 02:00 PM), 2x (Oct 14, 2025 · 05:00 PM)';
        $this->assertEquals($expectedDiffSummary, $resData[$resDiff->id]['companions_checkout_summary']);
        $this->assertStringContainsString($expectedDiffSummary, $content);
    }
}

