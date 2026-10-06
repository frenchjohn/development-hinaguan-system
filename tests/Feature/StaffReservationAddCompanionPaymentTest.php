<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\ReservationEntranceFee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffReservationAddCompanionPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function makeStaffSession(): void
    {
        $this->withSession(['auth_user' => ['id' => 1, 'name' => 'Staff User', 'role' => 'staff']]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        ParkSetting::updateOrCreate(
            ['id' => 1],
            [
                'daytime_start' => '06:00',
                'daytime_end' => '18:00',
                'nighttime_start' => '18:00',
                'nighttime_end' => '06:00',
                'daytime_adult_entrance_fee' => 20.00,
                'daytime_child_entrance_fee' => 10.00,
                'nighttime_adult_entrance_fee' => 50.00,
                'nighttime_child_entrance_fee' => 25.00,
                'day_pool_fee' => 50.00,
                'night_pool_fee' => 80.00,
            ]
        );
    }

    public function test_calculate_companion_fee_preview_endpoint_computes_exact_breakdown_including_zero_payment(): void
    {
        $this->makeStaffSession();

        $reservation = Reservation::create([
            'booker_name' => 'Alice Walker',
            'email' => 'alice@example.com',
            'phone' => '09123456789',
            'reservation_date' => now()->toDateString(),
            'check_in' => now()->toDateTimeString(),
            'status' => 'Checked In',
            'reservation_type' => 'walk_in',
            'number_of_guests' => 1,
            'total_amount' => 100,
            'amount_paid' => 100,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        ReservationEntranceFee::create([
            'reservation_id' => $reservation->id,
            'pricing_type' => 'Daytime',
            'pool_option' => 'no_pool',
            'total_amount' => 100,
            'pool_fee' => 0,
            'adult_count' => 1,
            'child_count' => 0,
        ]);

        // 1. Calculate fee for 2 adult companions (1 paying, 1 free) + 1 child + 1 pool pass
        $response = $this->postJson("/staff/reservations/{$reservation->id}/calculate-companion-fee", [
            'companions' => [
                [
                    'first_name' => 'Bob',
                    'last_name' => 'Walker',
                    'age' => 30,
                    'is_free_entrance' => false,
                    'pool_access' => true,
                ],
                [
                    'first_name' => 'Charlie',
                    'last_name' => 'Walker',
                    'age' => 28,
                    'is_free_entrance' => true, // Free entrance
                    'pool_access' => false,
                ],
                [
                    'first_name' => 'Danny',
                    'last_name' => 'Walker',
                    'age' => 8, // Child
                    'is_free_entrance' => false,
                    'pool_access' => false,
                ],
            ]
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'effective_period' => 'daytime',
                'adult_rate' => 20.0,
                'child_rate' => 10.0,
                'pool_rate' => 50.0,
                'adult_count' => 2,
                'child_count' => 1,
                'paying_adult_count' => 1,
                'paying_child_count' => 1,
                'free_count' => 1,
                'pool_count' => 1,
                'adult_subtotal' => 20.0,
                'child_subtotal' => 10.0,
                'entrance_fee' => 30.0,
                'pool_fee' => 50.0,
                'extra_head_fee' => 0.0,
                'total_payment' => 80.0,
            ]);

        // 2. Calculate fee when all companions have free passes and no pool (should calculate 0 payment)
        $zeroResponse = $this->postJson("/staff/reservations/{$reservation->id}/calculate-companion-fee", [
            'companions' => [
                [
                    'first_name' => 'Guest1',
                    'last_name' => 'Free',
                    'age' => 25,
                    'is_free_entrance' => true,
                    'pool_access' => false,
                ],
            ]
        ]);

        $zeroResponse->assertOk()
            ->assertJson([
                'success' => true,
                'paying_adult_count' => 0,
                'paying_child_count' => 0,
                'free_count' => 1,
                'entrance_fee' => 0.0,
                'pool_fee' => 0.0,
                'extra_head_fee' => 0.0,
                'total_payment' => 0.0,
            ]);
    }

    public function test_add_companion_returns_payment_totals_and_updates_balances(): void
    {
        $this->makeStaffSession();

        $reservation = Reservation::create([
            'booker_name' => 'Alice Walker',
            'email' => 'alice@example.com',
            'phone' => '09123456789',
            'reservation_date' => now()->toDateString(),
            'check_in' => now()->toDateTimeString(),
            'status' => 'Checked In',
            'reservation_type' => 'walk_in',
            'number_of_guests' => 1,
            'total_amount' => 100,
            'amount_paid' => 100,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        $primaryCustomer = \App\Models\Customer::create([
            'first_name' => 'Alice',
            'last_name' => 'Walker',
            'age' => 30,
            'gender' => 'Female',
        ]);

        \App\Models\ReservationGuest::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $primaryCustomer->id,
            'is_primary_guest' => true,
        ]);

        ReservationEntranceFee::create([
            'reservation_id' => $reservation->id,
            'pricing_type' => 'Daytime',
            'pool_option' => 'no_pool',
            'total_amount' => 100,
            'pool_fee' => 0,
            'adult_count' => 1,
            'child_count' => 0,
        ]);

        $response = $this->postJson("/staff/reservations/{$reservation->id}/add-companion", [
            'companions' => [
                [
                    'first_name' => 'Jane',
                    'last_name' => 'Doe',
                    'age' => 30,
                    'gender' => 'Female',
                    'is_foreigner' => false,
                    'is_free_entrance' => false,
                    'pool_access' => true,
                ],
                [
                    'first_name' => 'Baby',
                    'last_name' => 'Doe',
                    'age' => 5,
                    'gender' => 'Male',
                    'is_foreigner' => false,
                    'is_free_entrance' => true, // Free child entrance
                    'pool_access' => false,
                ]
            ]
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'added' => 2,
                'entrance_fee' => 20.0, // 1 adult @ 20, 1 child free
                'pool_fee' => 50.0,     // 1 pool @ 50
                'extra_head_fee' => 0.0,
                'total_payment' => 70.0,
                'number_of_guests' => 3,
                'total_amount' => 170.0,
                'amount_paid' => 170.0, // Paid status updates amount_paid
                'remaining_balance' => 0.0,
            ]);

        $reservation->refresh();
        $this->assertEquals(170.0, (float) $reservation->total_amount);
        $this->assertEquals(170.0, (float) $reservation->amount_paid);
        $this->assertEquals(0.0, (float) $reservation->remaining_balance);
        $this->assertEquals(3, $reservation->number_of_guests);
    }

    public function test_staff_check_ins_page_renders_payment_calculation_card_and_confirmation_modal(): void
    {
        $this->makeStaffSession();

        $response = $this->get('/staff/check-ins');

        $response->assertOk();
        $content = $response->getContent();

        // 1. Verify calculation UI elements are in the DOM
        $this->assertStringContainsString('id="resAddCalcGrandTotal"', $content);
        $this->assertStringContainsString('id="resAddFooterTotalPayment"', $content);
        $this->assertStringContainsString('Payment Calculation', $content);

        // 2. Verify confirmation modal is in the DOM
        $this->assertStringContainsString('id="reservationAddCompanionConfirmModal"', $content);
        $this->assertStringContainsString('id="resAddConfirmGrandTotal"', $content);
        $this->assertStringContainsString('id="resAddConfirmSubmitBtn"', $content);
        $this->assertStringContainsString('Confirm Additional Companion Payment', $content);
    }

    public function test_companion_added_with_no_amenity_does_not_incur_extra_head_fee_even_if_reservation_amenity_capacity_is_exceeded(): void
    {
        $this->makeStaffSession();

        $amenity = Amenity::create([
            'id' => 'AMENITY-TEST-CAP-01',
            'amenities_name' => 'VIP Gazebo',
            'minimum_capacity' => 1,
            'maximum_capacity' => 1, // Capacity 1
            'additional_per_head' => 150.0,
            'daytime_price' => 500,
            'nighttime_price' => 700,
            'status' => true,
        ]);

        $reservation = Reservation::create([
            'booker_name' => 'Mark Spencer',
            'email' => 'mark@example.com',
            'phone' => '09123456789',
            'reservation_date' => now()->toDateString(),
            'check_in' => now()->toDateTimeString(),
            'status' => 'Checked In',
            'reservation_type' => 'walk_in',
            'number_of_guests' => 1, // Already at capacity 1
            'total_amount' => 520,
            'amount_paid' => 520,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $amenity->id,
            'price_at_booking' => 500,
            'quantity' => 1,
            'pricing_type' => 'Daytime',
        ]);

        $customer = \App\Models\Customer::create([
            'first_name' => 'Mark',
            'last_name' => 'Spencer',
            'age' => 30,
            'gender' => 'Male',
        ]);

        \App\Models\ReservationGuest::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'is_primary_guest' => true,
            'has_pool_access' => false,
        ]);

        ReservationEntranceFee::create([
            'reservation_id' => $reservation->id,
            'pricing_type' => 'Daytime',
            'pool_option' => 'no_pool',
            'total_amount' => 20,
            'pool_fee' => 0,
            'adult_count' => 1,
            'child_count' => 0,
        ]);

        // 1. Preview calculation: companion with NO amenity assigned (amenity_id => null or '')
        $previewResponse = $this->postJson("/staff/reservations/{$reservation->id}/calculate-companion-fee", [
            'companions' => [
                [
                    'age' => 25,
                    'is_free_entrance' => false,
                    'pool_access' => false,
                    'amenity_id' => null, // Explicitly no amenity
                ]
            ]
        ]);

        $previewResponse->assertOk()
            ->assertJson([
                'success' => true,
                'extra_head_fee' => 0.0,
                'entrance_fee' => 20.0,
                'pool_fee' => 0.0,
                'total_payment' => 20.0, // Only 20 entrance fee, NO 150 extra head fee!
            ]);

        // 2. Add companion with NO amenity: should not charge extra head fee
        $addResponse = $this->postJson("/staff/reservations/{$reservation->id}/add-companion", [
            'companions' => [
                [
                    'first_name' => 'David',
                    'last_name' => 'Spencer',
                    'age' => 25,
                    'gender' => 'Male',
                    'is_foreigner' => false,
                    'is_free_entrance' => false,
                    'pool_access' => false,
                    'amenity_id' => null, // No amenity chosen
                ]
            ]
        ]);

        $addResponse->assertOk()
            ->assertJson([
                'success' => true,
                'added' => 1,
                'extra_head_fee' => 0.0,
                'entrance_fee' => 20.0,
                'total_payment' => 20.0,
            ]);

        // 3. Now verify: if assigned to the amenity whose capacity is exceeded, extra head fee DOES apply
        $assignedPreview = $this->postJson("/staff/reservations/{$reservation->id}/calculate-companion-fee", [
            'companions' => [
                [
                    'age' => 22,
                    'is_free_entrance' => false,
                    'pool_access' => false,
                    'amenity_id' => $amenity->id, // Assigned to VIP Gazebo
                ]
            ]
        ]);

        $assignedPreview->assertOk()
            ->assertJson([
                'success' => true,
                'extra_head_fee' => 150.0, // 150 extra head fee applied!
                'entrance_fee' => 20.0,
                'total_payment' => 170.0,
            ]);
    }

    public function test_companion_assigned_to_amenity_with_free_entrance_and_free_pool_benefits_waives_both_fees(): void
    {
        $this->makeStaffSession();

        $amenity = Amenity::create([
            'id' => 'AMENITY-BENEFIT-01',
            'amenities_name' => 'Villa Deluxe',
            'minimum_capacity' => 1,
            'maximum_capacity' => 10,
            'additional_per_head' => 100.0,
            'daytime_price' => 1000,
            'nighttime_price' => 1500,
            'status' => true,
        ]);

        // Add benefits: Free Entrance and Free Pool
        \App\Models\AmenityBenefit::create([
            'amenity_id' => $amenity->id,
            'free_entrance' => true,
            'free_pool' => true,
            'is_aircon' => true,
        ]);

        $reservation = Reservation::create([
            'booker_name' => 'Clara Oswald',
            'email' => 'clara@example.com',
            'phone' => '09123456789',
            'reservation_date' => now()->toDateString(),
            'check_in' => now()->toDateTimeString(),
            'status' => 'Checked In',
            'reservation_type' => 'walk_in',
            'number_of_guests' => 1,
            'total_amount' => 1000,
            'amount_paid' => 1000,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $amenity->id,
            'price_at_booking' => 1000,
            'quantity' => 1,
            'pricing_type' => 'Daytime',
        ]);

        ReservationEntranceFee::create([
            'reservation_id' => $reservation->id,
            'pricing_type' => 'Daytime',
            'pool_option' => 'all_free',
            'total_amount' => 0,
            'pool_fee' => 0,
            'adult_count' => 1,
            'child_count' => 0,
        ]);

        // Preview fee for companion assigned to Villa Deluxe:
        // Even though adult rate is 20 and pool is 50, both should be waived (0 total)
        $preview = $this->postJson("/staff/reservations/{$reservation->id}/calculate-companion-fee", [
            'companions' => [
                [
                    'age' => 28,
                    'pool_access' => true,
                    'amenity_id' => $amenity->id,
                ]
            ]
        ]);

        $preview->assertOk()
            ->assertJson([
                'success' => true,
                'entrance_fee' => 0.0,
                'pool_fee' => 0.0,
                'extra_head_fee' => 0.0,
                'total_payment' => 0.0,
                'free_count' => 1,
                'paying_pool_count' => 0,
            ]);

        // Add companion assigned to Villa Deluxe
        $addResponse = $this->postJson("/staff/reservations/{$reservation->id}/add-companion", [
            'companions' => [
                [
                    'first_name' => 'Danny',
                    'last_name' => 'Pink',
                    'age' => 28,
                    'gender' => 'Male',
                    'is_foreigner' => false,
                    'pool_access' => true,
                    'amenity_id' => $amenity->id,
                ]
            ]
        ]);

        $addResponse->assertOk()
            ->assertJson([
                'success' => true,
                'added' => 1,
                'entrance_fee' => 0.0,
                'pool_fee' => 0.0,
                'extra_head_fee' => 0.0,
                'total_payment' => 0.0,
            ]);

        // Verify ReservationGuest received pool access granted by benefit
        $guest = \App\Models\ReservationGuest::where('reservation_id', $reservation->id)
            ->whereHas('customer', fn ($q) => $q->where('first_name', 'Danny'))
            ->first();

        $this->assertNotNull($guest);
        $this->assertTrue((bool) $guest->has_pool_access);
    }

    public function test_companion_added_with_no_amenity_pays_entrance_fee_and_gets_no_amenity_benefits(): void
    {
        $this->makeStaffSession();

        // Create an amenity with free entrance and free pool benefits
        $amenity = Amenity::create([
            'id' => 'AMENITY-BENEFIT-02',
            'amenities_name' => 'Royal Villa',
            'minimum_capacity' => 1,
            'maximum_capacity' => 8,
            'additional_per_head' => 150.0,
            'daytime_price' => 2000,
            'nighttime_price' => 3000,
            'status' => true,
        ]);

        \App\Models\AmenityBenefit::create([
            'amenity_id' => $amenity->id,
            'free_entrance' => true,
            'free_pool' => true,
            'is_aircon' => true,
        ]);

        $reservation = Reservation::create([
            'booker_name' => 'Rory Williams',
            'email' => 'rory@example.com',
            'phone' => '09123456789',
            'reservation_date' => now()->toDateString(),
            'check_in' => now()->toDateTimeString(),
            'status' => 'Checked In',
            'reservation_type' => 'walk_in',
            'number_of_guests' => 1,
            'total_amount' => 2000,
            'amount_paid' => 2000,
            'remaining_balance' => 0,
            'payment_status' => 'Paid',
        ]);

        ReservationAmenity::create([
            'reservation_id' => $reservation->id,
            'amenity_id' => $amenity->id,
            'price_at_booking' => 2000,
            'quantity' => 1,
            'pricing_type' => 'Daytime',
        ]);

        ReservationEntranceFee::create([
            'reservation_id' => $reservation->id,
            'pricing_type' => 'Daytime',
            'pool_option' => 'all_free',
            'total_amount' => 0,
            'pool_fee' => 0,
            'adult_count' => 1,
            'child_count' => 0,
        ]);

        // When staff adds a companion with NO amenity (amenity_id is null / empty),
        // they have NO amenity benefits at all: they must pay entrance fee (20.00)
        $preview = $this->postJson("/staff/reservations/{$reservation->id}/calculate-companion-fee", [
            'companions' => [
                [
                    'age' => 30,
                    'pool_access' => false,
                    'is_free_entrance' => false,
                    'amenity_id' => null,
                ]
            ]
        ]);

        $preview->assertOk()
            ->assertJson([
                'success' => true,
                'paying_adult_count' => 1,
                'paying_child_count' => 0,
                'free_count' => 0,
                'adult_subtotal' => 20.0,
                'entrance_fee' => 20.0,
                'pool_fee' => 0.0,
                'extra_head_fee' => 0.0,
                'total_payment' => 20.0,
            ]);

        // When adding companion with no amenity and wanting pool access:
        // Must pay both entrance fee (20.00) + pool fee (50.00) = 70.00 (no amenity benefits applied!)
        $addResponse = $this->postJson("/staff/reservations/{$reservation->id}/add-companion", [
            'companions' => [
                [
                    'first_name' => 'Arthur',
                    'last_name' => 'Dent',
                    'age' => 35,
                    'gender' => 'Male',
                    'is_foreigner' => false,
                    'pool_access' => true,
                    'is_free_entrance' => false,
                    'amenity_id' => null, // No amenity chosen
                ]
            ]
        ]);

        $addResponse->assertOk()
            ->assertJson([
                'success' => true,
                'added' => 1,
                'entrance_fee' => 20.0,
                'pool_fee' => 50.0,
                'extra_head_fee' => 0.0,
                'total_payment' => 70.0,
                'total_amount' => 2070.0,
            ]);

        // Verify ReservationGuest received pool access because they paid for it
        $guest = \App\Models\ReservationGuest::where('reservation_id', $reservation->id)
            ->whereHas('customer', fn ($q) => $q->where('first_name', 'Arthur'))
            ->first();

        $this->assertNotNull($guest);
        $this->assertTrue((bool) $guest->has_pool_access);
    }
}
