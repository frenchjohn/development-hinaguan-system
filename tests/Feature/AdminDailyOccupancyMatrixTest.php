<?php

namespace Tests\Feature;

use App\Models\AdminAccount;
use App\Models\Amenity;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminDailyOccupancyMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_occupancy_matrix_uses_check_in_date_and_keeps_checked_out_stays(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $amenity1 = Amenity::create([
            'id' => (string) Str::uuid(),
            'amenities_name' => 'A-House 1',
            'daytime_price' => 1000,
            'nighttime_price' => 1500,
            'minimum_capacity' => 2,
            'maximum_capacity' => 6,
            'status' => true,
        ]);

        $amenity2 = Amenity::create([
            'id' => (string) Str::uuid(),
            'amenities_name' => 'A-House 2',
            'daytime_price' => 1000,
            'nighttime_price' => 1500,
            'minimum_capacity' => 2,
            'maximum_capacity' => 6,
            'status' => true,
        ]);

        // Reservation 1: Booked for Sep 10, but checked in on Sep 5
        $res1 = Reservation::create([
            'booker_name' => 'Guest One',
            'phone' => '09123456789',
            'email' => 'guest1@example.com',
            'reservation_date' => '2026-09-10',
            'check_in' => '2026-09-05 09:00:00',
            'end_date' => '2026-09-10',
            'total_days' => 1,
            'number_of_guests' => 2,
            'reservation_type' => 'online',
            'status' => 'Checked In',
            'total_amount' => 1000,
            'amount_paid' => 1000,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);
        ReservationAmenity::create([
            'reservation_id' => $res1->id,
            'amenity_id' => $amenity1->id,
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-10',
            'quantity' => 1,
            'price_at_booking' => 1000,
            'pricing_type' => 'Daytime',
        ]);

        // Reservation 2: Checked in on Sep 4 at 10:00 PM and Checked Out on Sep 5 at 8:00 AM (1 night stay)
        $res2 = Reservation::create([
            'booker_name' => 'Guest Two',
            'phone' => '09123456788',
            'email' => 'guest2@example.com',
            'reservation_date' => '2026-09-04',
            'check_in' => '2026-09-04 22:00:00',
            'check_out' => '2026-09-05 08:00:00',
            'end_date' => '2026-09-04',
            'total_days' => 1,
            'number_of_guests' => 1,
            'reservation_type' => 'walk_in',
            'status' => 'Checked Out',
            'total_amount' => 1000,
            'amount_paid' => 1000,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);
        ReservationAmenity::create([
            'reservation_id' => $res2->id,
            'amenity_id' => $amenity2->id,
            'start_date' => '2026-09-04',
            'end_date' => '2026-09-04',
            'quantity' => 1,
            'price_at_booking' => 1000,
            'pricing_type' => 'Daytime',
        ]);

        // Reservation 3: Confirmed / Pending (future stay, not checked in yet)
        $res3 = Reservation::create([
            'booker_name' => 'Guest Three',
            'phone' => '09123456787',
            'email' => 'guest3@example.com',
            'reservation_date' => '2026-09-15',
            'check_in' => null,
            'end_date' => '2026-09-15',
            'total_days' => 1,
            'number_of_guests' => 3,
            'reservation_type' => 'online',
            'status' => 'Confirmed',
            'total_amount' => 1000,
            'amount_paid' => 1000,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'admin',
            ],
        ])->get('/admin/reports');

        $response->assertOk();

        // Verify reportData passed to view has res1 check_in as 2026-09-05, NOT 2026-09-10
        $viewData = $response->viewData('reportData');
        $this->assertNotNull($viewData);

        $reservationsData = collect($viewData['reservations']);
        $r1Data = $reservationsData->firstWhere('id', $res1->id);
        $this->assertEquals('2026-09-05', $r1Data['check_in']);
        $this->assertEquals('2026-09-05', $r1Data['end_date']);
        $this->assertEquals('2026-09-05', $r1Data['amenities'][0]['start_date']);

        $r2Data = $reservationsData->firstWhere('id', $res2->id);
        $this->assertEquals('Checked Out', $r2Data['status']);
        // Checked in Sep 4 10pm, checked out Sep 5: records strictly on Sep 4!
        $this->assertEquals('2026-09-04', $r2Data['check_in']);
        $this->assertEquals('2026-09-04', $r2Data['end_date']);
        $this->assertEquals('2026-09-04', $r2Data['amenities'][0]['start_date']);
        $this->assertEquals('2026-09-04', $r2Data['amenities'][0]['end_date']);

        $r3Data = $reservationsData->firstWhere('id', $res3->id);
        $this->assertEquals('Confirmed', $r3Data['status']);
    }
}
