<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompletedAmenityAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private Amenity $payag;
    private Amenity $functionHall;
    private Reservation $reservation;
    private ReservationAmenity $payagRa;
    private ReservationAmenity $functionHallRa;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-01 08:00:00');

        ParkSetting::create([
            'opening_time' => '08:00',
            'closing_time' => '17:00',
            'daytime_start' => '08:00',
            'daytime_end' => '17:00',
            'nighttime_start' => '17:00',
            'nighttime_end' => '22:00',
        ]);

        $this->payag = Amenity::create([
            'id' => 'AMENITY-PAYAG-001',
            'amenities_name' => 'Payag',
            'description' => 'Payag cottage',
            'daytime_price' => 250,
            'nighttime_price' => 300,
            'minimum_capacity' => 1,
            'maximum_capacity' => 10,
            'status' => true,
        ]);

        $this->functionHall = Amenity::create([
            'id' => 'AMENITY-HALL-001',
            'amenities_name' => 'Function Hall',
            'description' => 'Grand function hall',
            'daytime_price' => 5000,
            'nighttime_price' => 6000,
            'minimum_capacity' => 20,
            'maximum_capacity' => 100,
            'status' => true,
        ]);

        $this->reservation = Reservation::create([
            'booker_name' => 'John MultiAmenity',
            'phone' => '09123456789',
            'email' => 'john@example.com',
            'reservation_date' => '2026-08-27',
            'end_date' => '2026-08-31',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 5,
            'number_of_guests' => 10,
            'status' => 'Checked In',
            'total_amount' => 67200,
            'amount_paid' => 67200,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
            'reservation_type' => 'online',
        ]);

        $this->payagRa = ReservationAmenity::create([
            'reservation_id' => $this->reservation->id,
            'amenity_id' => $this->payag->id,
            'start_date' => '2026-08-27',
            'end_date' => '2026-08-30',
            'start_slot' => 'Daytime',
            'end_slot' => 'Nighttime',
            'pricing_type' => 'Continuous Stay (4D)',
            'price_at_booking' => 2200,
            'status' => 'Active',
        ]);

        $this->functionHallRa = ReservationAmenity::create([
            'reservation_id' => $this->reservation->id,
            'amenity_id' => $this->functionHall->id,
            'start_date' => '2026-08-27',
            'end_date' => '2026-08-31',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'pricing_type' => 'Continuous Stay (5D)',
            'price_at_booking' => 65000,
            'status' => 'Active',
        ]);
    }

    public function test_both_amenities_are_unavailable_when_active()
    {
        // Check Function Hall calendar
        $resHall = $this->getJson("/reservation/availability/calendar?amenity_id={$this->functionHall->id}&month=7&year=2026");
        $resHall->assertStatus(200);
        $availHall = collect($resHall->json('availability'))->keyBy('date');
        $this->assertFalse($availHall['2026-08-27']['daytime']);

        // Check Payag calendar
        $resPayag = $this->getJson("/reservation/availability/calendar?amenity_id={$this->payag->id}&month=7&year=2026");
        $resPayag->assertStatus(200);
        $availPayag = collect($resPayag->json('availability'))->keyBy('date');
        $this->assertFalse($availPayag['2026-08-27']['daytime']);

        // Check continuous range occupied IDs
        $resRange = $this->getJson("/reservation/availability?start_date=2026-08-27&end_date=2026-08-30&start_slot=Daytime&end_slot=Daytime");
        $resRange->assertStatus(200);
        $this->assertContains($this->functionHall->id, $resRange->json('occupied_amenity_ids'));
        $this->assertContains($this->payag->id, $resRange->json('occupied_amenity_ids'));
    }

    public function test_completed_amenity_becomes_available_even_if_reservation_is_still_checked_in()
    {
        // Check out only Function Hall (status -> Completed)
        $this->functionHallRa->update(['status' => 'Completed']);

        // 1. Function Hall calendar should NOW be AVAILABLE on August 27-31
        $resHall = $this->getJson("/reservation/availability/calendar?amenity_id={$this->functionHall->id}&month=7&year=2026");
        $resHall->assertStatus(200);
        $availHall = collect($resHall->json('availability'))->keyBy('date');
        $this->assertTrue($availHall['2026-08-27']['daytime'], 'Function Hall daytime should be available on 2026-08-27 after checkout');
        $this->assertTrue($availHall['2026-08-27']['nighttime'], 'Function Hall nighttime should be available on 2026-08-27 after checkout');
        $this->assertTrue($availHall['2026-08-28']['daytime'], 'Function Hall daytime should be available on 2026-08-28 after checkout');

        // 2. Payag should STILL be UNAVAILABLE
        $resPayag = $this->getJson("/reservation/availability/calendar?amenity_id={$this->payag->id}&month=7&year=2026");
        $resPayag->assertStatus(200);
        $availPayag = collect($resPayag->json('availability'))->keyBy('date');
        $this->assertFalse($availPayag['2026-08-27']['daytime'], 'Payag should still be unavailable on 2026-08-27');

        // 3. Range availability endpoint should NOT report Function Hall as occupied
        $resRange = $this->getJson("/reservation/availability?start_date=2026-08-27&end_date=2026-08-30&start_slot=Daytime&end_slot=Daytime");
        $resRange->assertStatus(200);
        $this->assertNotContains($this->functionHall->id, $resRange->json('occupied_amenity_ids'));
        $this->assertContains($this->payag->id, $resRange->json('occupied_amenity_ids'));
    }
}
