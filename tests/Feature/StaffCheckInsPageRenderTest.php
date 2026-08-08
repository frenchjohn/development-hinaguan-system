<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Customer;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\ReservationGuest;
use App\Models\StaffAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class StaffCheckInsPageRenderTest extends TestCase
{
    use RefreshDatabase;

    private function makeStaffSession(StaffAccount $staff): void
    {
        $this->withSession(['auth_user' => ['id' => $staff->id, 'name' => $staff->name, 'role' => 'staff']]);
    }

    public function test_check_ins_page_renders_without_undefined_variable(): void
    {
        $staff = StaffAccount::create([
            'name' => 'Staff One',
            'email' => 'staff@test.com',
            'password' => bcrypt('password'),
            'ban_status' => false,
        ]);

        $this->makeStaffSession($staff);

        $amenity = Amenity::create([
            'id' => (string) Str::uuid(),
            'amenities_name' => 'Cottage A',
            'daytime_price' => 500,
            'nighttime_price' => 700,
            'minimum_capacity' => 1,
            'maximum_capacity' => 10,
            'status' => true,
        ]);

        $reservation = Reservation::create([
            'booker_name' => 'Juan Dela Cruz',
            'email' => 'juan@test.com',
            'phone' => '09170000000',
            'reservation_date' => now()->toDateString(),
            'check_in' => now()->toDateTimeString(),
            'status' => 'Checked In',
            'reservation_type' => 'online',
            'number_of_guests' => 2,
            'total_amount' => 1200,
            'amount_paid' => 1200,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        ParkSetting::firstOrCreate([], [
            'daytime_start' => '06:00',
            'daytime_end' => '18:00',
            'nighttime_start' => '18:00',
            'nighttime_end' => '06:00',
        ]);

        $ra1 = ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $amenity->id,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 500,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $amenity->id,
            'pricing_type' => 'Nighttime',
            'price_at_booking' => 700,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        $customer = Customer::create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'age' => 30,
            'gender' => 'Male',
            'is_foreigner' => false,
        ]);

        ReservationGuest::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'is_primary_guest' => true,
        ]);

        $response = $this->get('/staff/check-ins');

        $response->assertStatus(200);
        $response->assertSee('Time left');
        $response->assertDontSee('Undefined variable');
    }

    public function test_check_ins_countdown_anchors_to_check_in_not_future_booking_date(): void
    {
        $staff = StaffAccount::create([
            'name' => 'Staff Two',
            'email' => 'staff2@test.com',
            'password' => bcrypt('password'),
            'ban_status' => false,
        ]);

        $this->makeStaffSession($staff);

        ParkSetting::firstOrCreate([], [
            'daytime_start' => '06:00',
            'daytime_end' => '18:00',
            'nighttime_start' => '18:00',
            'nighttime_end' => '06:00',
        ]);

        $amenity = Amenity::create([
            'id' => (string) Str::uuid(),
            'amenities_name' => 'Cottage A',
            'daytime_price' => 500,
            'nighttime_price' => 700,
            'minimum_capacity' => 1,
            'maximum_capacity' => 10,
            'status' => true,
        ]);

        // Guest booked for a FUTURE date but already checked in today — the
        // countdown must be anchored to the actual check-in (today), not the
        // future reservation_date (which would show 300+ hours).
        $today = Carbon::today();
        $reservation = Reservation::create([
            'booker_name' => 'Maria Santos',
            'email' => 'maria@test.com',
            'phone' => '09170000001',
            'reservation_date' => $today->copy()->addDays(14)->toDateString(),
            'check_in' => $today->copy()->setTime(9, 0)->toDateTimeString(),
            'status' => 'Checked In',
            'reservation_type' => 'online',
            'number_of_guests' => 2,
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $ra = ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $amenity->id,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 500,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        $customer = Customer::create([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'age' => 28,
            'gender' => 'Female',
            'is_foreigner' => false,
        ]);

        ReservationGuest::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'is_primary_guest' => true,
        ]);

        $response = $this->get('/staff/check-ins');
        $response->assertStatus(200);

        $content = $response->getContent();
        $expectedDaytimeCheckout = $today->copy()->setTime(18, 0)->toIso8601String();
        $futureBookingCheckout = $today->copy()->addDays(14)->setTime(18, 0)->toIso8601String();

        $this->assertStringContainsString(
            'data-checkout-at="' . $expectedDaytimeCheckout . '"',
            $content,
            'Countdown should use today\'s checkout time (anchored to check-in).'
        );
        $this->assertStringNotContainsString(
            'data-checkout-at="' . $futureBookingCheckout . '"',
            $content,
            'Countdown must NOT use the future reservation_date checkout time.'
        );
    }

    public function test_check_in_stores_single_and_bulk_companions(): void
    {
        $staff = StaffAccount::create([
            'name' => 'Staff Three',
            'email' => 'staff3@test.com',
            'password' => bcrypt('password'),
            'ban_status' => false,
        ]);
        $this->makeStaffSession($staff);

        $reservation = Reservation::create([
            'booker_name' => 'Ana Reyes',
            'email' => 'ana@test.com',
            'phone' => '09170000002',
            'reservation_date' => now()->toDateString(),
            'status' => 'Pending',
            'reservation_type' => 'online',
            'number_of_guests' => 1,
            'total_amount' => 500,
            'amount_paid' => 500,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $response = $this->postJson("/staff/reservations/{$reservation->id}/check-in", [
            'guest_mode' => 'with_primary',
            'primary_guest_id' => null,
            'primary_guest' => [
                'first_name' => 'Ana',
                'last_name' => 'Reyes',
                'age' => '30',
                'gender' => 'Female',
                'is_foreigner' => false,
                'email' => 'ana@test.com',
                'phone' => '09170000002',
            ],
            // Single companion + 2 bulk-generated companions (the exact shape
            // staff_reservations.js now sends via getAllCheckInCompanions()).
            'companions' => [
                [
                    'first_name' => 'Carlo',
                    'last_name' => 'Lopez',
                    'age' => '28',
                    'gender' => 'Male',
                    'is_foreigner' => false,
                    'phone' => '',
                    'email' => '',
                ],
                [
                    'first_name' => 'Companion',
                    'last_name' => 'C1',
                    'age' => '30',
                    'gender' => 'Male',
                    'is_foreigner' => false,
                    'phone' => '',
                    'email' => '',
                ],
                [
                    'first_name' => 'Companion',
                    'last_name' => 'C2',
                    'age' => '30',
                    'gender' => 'Female',
                    'is_foreigner' => true,
                    'phone' => '',
                    'email' => '',
                ],
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'status' => 'Checked In']);
        // Primary + 3 companions = 4 reservation guests, all stored
        $this->assertSame(4, ReservationGuest::where('reservation_id', $reservation->id)->count());
        $this->assertDatabaseHas('customers', ['first_name' => 'Carlo', 'last_name' => 'Lopez']);
        $this->assertDatabaseHas('customers', ['first_name' => 'Companion', 'last_name' => 'C1']);
        $this->assertDatabaseHas('customers', ['first_name' => 'Companion', 'last_name' => 'C2']);
    }
}
