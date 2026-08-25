<?php

namespace Tests\Feature;

use App\Mail\ReservationQrMail;
use App\Models\Amenity;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\ReservationEntranceFee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationQrMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_all_reservation_details_in_redesigned_email(): void
    {
        ParkSetting::create([
            'contact_number' => '0985-323-9532',
            'email' => 'parkhinaguan@gmail.com',
            'daytime_start' => '08:00:00',
            'daytime_end' => '18:00:00',
            'nighttime_start' => '18:00:00',
            'nighttime_end' => '08:00:00',
        ]);

        $amenity = Amenity::create([
            'id' => 'amenity-cabana',
            'amenities_name' => 'Riverside Cabana Deluxe',
            'daytime_price' => 1200,
            'nighttime_price' => 1500,
            'minimum_capacity' => 4,
            'maximum_capacity' => 10,
            'status' => true,
        ]);

        $reservation = Reservation::create([
            'booker_name' => 'Juan Dela Cruz',
            'phone' => '09181234567',
            'email' => 'juan@example.com',
            'reservation_date' => '2026-09-15',
            'end_date' => '2026-09-15',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 6,
            'total_amount' => 1500.00,
            'amount_paid' => 750.00,
            'remaining_balance' => 750.00,
            'payment_status' => 'Partially Paid',
            'payment_method' => 'GCash Online',
            'status' => 'Confirmed',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $amenity->id,
            'pricing_type' => 'Daytime',
            'price_at_booking' => 1200.00,
            'quantity' => 1,
        ]);

        ReservationEntranceFee::create([
            'reservation_id' => $reservation->id,
            'pricing_type' => 'Daytime',
            'total_amount' => 300.00,
            'pool_fee' => 70.00,
            'adult_count' => 4,
            'child_count' => 2,
        ]);

        $mailable = new ReservationQrMail($reservation);
        $html = $mailable->render();

        // Check essential user info
        $this->assertStringContainsString('Juan Dela Cruz', $html);
        $this->assertStringContainsString('09181234567', $html);
        $this->assertStringContainsString('juan@example.com', $html);
        $this->assertStringContainsString('6 Person(s)', $html);

        // Check amenity details
        $this->assertStringContainsString('Riverside Cabana Deluxe', $html);
        $this->assertStringContainsString('1,200.00', $html);
        $this->assertStringContainsString('Entrance Fee (4 Adults, 2 Kids)', $html);

        // Check arrival window & schedule
        $this->assertStringContainsString('Daytime Stay (8:00 AM - 6:00 PM)', $html);
        $this->assertStringContainsString('Arrive at Sep 15, 2026 at 8:00 AM', $html);
        $this->assertStringContainsString('better to be early', $html);
        $this->assertStringContainsString('September 15, 2026', $html);

        // Check billing
        $this->assertStringContainsString('1,500.00', $html);
        $this->assertStringContainsString('750.00', $html);
        $this->assertStringContainsString('GCash Online', $html);

        // Check notices & requirements
        $this->assertStringContainsString('Bring Your Entry QR Code (Required)', $html);
        $this->assertStringContainsString('Valid ID Verification', $html);
        $this->assertStringContainsString('Know Your Reservation ID', $html);

        // Check location & contact details
        $this->assertStringContainsString('Jasaan, Misamis Oriental, Philippines', $html);
        $this->assertStringContainsString('0985-323-9532', $html);
        $this->assertStringContainsString('parkhinaguan@gmail.com', $html);
    }

    public function test_it_formats_continuous_stay_arrival_time_and_instructions(): void
    {
        ParkSetting::create([
            'contact_number' => '0985-323-9532',
            'email' => 'parkhinaguan@gmail.com',
            'opening_time' => '08:00:00',
            'closing_time' => '18:00:00',
            'daytime_start' => '08:01:00',
            'daytime_end' => '18:00:00',
            'nighttime_start' => '18:01:00',
            'nighttime_end' => '08:00:00',
        ]);

        $reservation = Reservation::create([
            'booker_name' => 'Ana Reyes',
            'phone' => '09170001122',
            'email' => 'ana@example.com',
            'reservation_date' => '2026-09-01',
            'end_date' => '2026-09-05',
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 5,
            'number_of_guests' => 4,
            'total_amount' => 5000.00,
            'amount_paid' => 2500.00,
            'remaining_balance' => 2500.00,
            'payment_status' => 'Partially Paid',
            'status' => 'Confirmed',
        ]);

        $mailable = new ReservationQrMail($reservation);
        $html = $mailable->render();

        $this->assertStringContainsString('Continuous Stay (5 Days / Daytime to Daytime)', $html);
        $this->assertStringContainsString('Arrive at Sep 01, 2026 at 8:00 AM', $html);
        $this->assertStringContainsString("Please arrive on Sep 01, 2026 at 8:00 AM", $html);
        $this->assertStringContainsString("We can still check you in even if you arrive late", $html);
        $this->assertStringContainsString("better to be early", $html);
    }
}
