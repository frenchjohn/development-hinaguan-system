<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiDayBoundaryAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_day_reservation_boundary_slots_availability(): void
    {
        // Create an amenity
        $amenity = Amenity::create([
            'id' => 'AMENITY-TEST-001',
            'amenities_name' => 'Bamboo Villa',
            'daytime_price' => 1000,
            'nighttime_price' => 1200,
            'minimum_capacity' => 1,
            'maximum_capacity' => 10,
            'status' => true,
        ]);

        // Create a multi-day continuous reservation: Aug 28 Nighttime to Aug 30 Daytime
        $reservation = Reservation::create([
            'booker_name' => 'John MultiDay',
            'phone' => '09123456789',
            'email' => 'multiday@example.com',
            'reservation_date' => '2026-08-28',
            'end_date' => '2026-08-30',
            'start_slot' => 'Nighttime',
            'end_slot' => 'Daytime',
            'total_days' => 3,
            'number_of_guests' => 4,
            'status' => 'Pending',
            'total_amount' => 5000,
            'amount_paid' => 2500,
            'remaining_balance' => 2500,
            'payment_status' => 'Partially Paid',
            'reservation_type' => 'online',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $amenity->id,
            'start_date' => '2026-08-28',
            'end_date' => '2026-08-30',
            'start_slot' => 'Nighttime',
            'end_slot' => 'Daytime',
            'pricing_type' => 'Continuous Stay (3D)',
            'price_at_booking' => 5000,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        // 1. Check direct availability endpoint for Aug 28 Daytime (should be free)
        $resAug28Day = $this->getJson('/reservation/availability?start_date=2026-08-28&end_date=2026-08-28&start_slot=Daytime&end_slot=Daytime');
        $resAug28Day->assertOk();
        $this->assertNotContains($amenity->id, $resAug28Day->json('occupied_amenity_ids'), 'Aug 28 Daytime should NOT be occupied');

        // 2. Check direct availability endpoint for Aug 28 Nighttime (should be occupied)
        $resAug28Night = $this->getJson('/reservation/availability?start_date=2026-08-28&end_date=2026-08-28&start_slot=Nighttime&end_slot=Nighttime');
        $resAug28Night->assertOk();
        $this->assertContains($amenity->id, $resAug28Night->json('occupied_amenity_ids'), 'Aug 28 Nighttime should BE occupied');

        // 3. Check direct availability endpoint for Aug 30 Daytime (should be occupied)
        $resAug30Day = $this->getJson('/reservation/availability?start_date=2026-08-30&end_date=2026-08-30&start_slot=Daytime&end_slot=Daytime');
        $resAug30Day->assertOk();
        $this->assertContains($amenity->id, $resAug30Day->json('occupied_amenity_ids'), 'Aug 30 Daytime should BE occupied');

        // 4. Check direct availability endpoint for Aug 30 Nighttime (should be free)
        $resAug30Night = $this->getJson('/reservation/availability?start_date=2026-08-30&end_date=2026-08-30&start_slot=Nighttime&end_slot=Nighttime');
        $resAug30Night->assertOk();
        $this->assertNotContains($amenity->id, $resAug30Night->json('occupied_amenity_ids'), 'Aug 30 Nighttime should NOT be occupied');

        // 5. Check calendar endpoint
        $calendarResponse = $this->getJson('/reservation/availability/calendar?amenity_id=' . $amenity->id . '&month=7&year=2026');
        $calendarResponse->assertOk();
        $availList = $calendarResponse->json('availability');

        $aug28 = collect($availList)->firstWhere('date', '2026-08-28');
        $aug29 = collect($availList)->firstWhere('date', '2026-08-29');
        $aug30 = collect($availList)->firstWhere('date', '2026-08-30');
        $aug31 = collect($availList)->firstWhere('date', '2026-08-31');

        $this->assertNotNull($aug28);
        $this->assertNotNull($aug29);
        $this->assertNotNull($aug30);

        // Aug 28: Daytime available, Nighttime booked
        $this->assertTrue($aug28['daytime'], 'Aug 28 Daytime should be available in calendar');
        $this->assertFalse($aug28['nighttime'], 'Aug 28 Nighttime should be booked in calendar');

        // Aug 29: Both booked
        $this->assertFalse($aug29['daytime'], 'Aug 29 Daytime should be booked in calendar');
        $this->assertFalse($aug29['nighttime'], 'Aug 29 Nighttime should be booked in calendar');

        // Aug 30: Daytime booked, Nighttime available
        $this->assertFalse($aug30['daytime'], 'Aug 30 Daytime should be booked in calendar');
        $this->assertTrue($aug30['nighttime'], 'Aug 30 Nighttime should be available in calendar');

        // Aug 31: Both available
        $this->assertTrue($aug31['daytime'], 'Aug 31 Daytime should be available in calendar');
        $this->assertTrue($aug31['nighttime'], 'Aug 31 Nighttime should be available in calendar');
    }
}
