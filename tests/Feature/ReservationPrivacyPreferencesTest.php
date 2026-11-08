<?php

namespace Tests\Feature;

use App\Mail\CheckoutReceiptMail;
use App\Models\ActivityLog;
use App\Models\Amenity;
use App\Models\Customer;
use App\Models\ParkSetting;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\ReservationGuest;
use App\Models\StaffAccount;
use App\Services\PhilSmsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReservationPrivacyPreferencesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ParkSetting::create([
            'daytime_start' => '08:00',
            'daytime_end' => '17:00',
            'nighttime_start' => '18:00',
            'nighttime_end' => '06:00',
            'daytime_adult_entrance_fee' => 100,
            'daytime_child_entrance_fee' => 50,
            'nighttime_adult_entrance_fee' => 120,
            'nighttime_child_entrance_fee' => 60,
            'day_pool_fee' => 50,
            'night_pool_fee' => 70,
        ]);
    }

    private function makeStaffSession(): StaffAccount
    {
        $staff = StaffAccount::create([
            'name' => 'Staff Maria',
            'email' => 'staff.maria@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->withSession([
            'auth_user' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'email' => $staff->email,
                'role' => 'staff',
            ]
        ]);

        return $staff;
    }

    private function createSampleReservation(bool $allowSms = true, bool $allowEmail = true): Reservation
    {
        $res = Reservation::create([
            'booker_name' => 'Secret Guest',
            'email' => 'secret@example.com',
            'phone' => '09171234567',
            'reservation_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'total_days' => 1,
            'number_of_guests' => 2,
            'reservation_type' => 'online',
            'status' => 'Pending',
            'total_amount' => 500.00,
            'amount_paid' => 250.00,
            'remaining_balance' => 250.00,
            'payment_status' => 'Partially Paid',
            'allow_sms' => $allowSms,
            'allow_email' => $allowEmail,
        ]);

        $amenity = Amenity::firstOrCreate(
            ['id' => 'payag-test'],
            [
                'amenities_name' => 'Payag Test',
                'daytime_price' => 500,
                'nighttime_price' => 700,
                'minimum_capacity' => 1,
                'maximum_capacity' => 10,
                'status' => true,
            ]
        );

        ReservationAmenity::create([
            'reservation_id' => $res->id,
            'amenity_id' => $amenity->id,
            'pricing_type' => 'Daytime',
            'start_date' => $res->reservation_date,
            'end_date' => $res->end_date,
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'day_slots_count' => 1,
            'night_slots_count' => 0,
            'price_at_booking' => 500,
            'quantity' => 1,
            'status' => 'Active',
        ]);

        return $res;
    }

    public function test_reservation_defaults_allow_sms_and_allow_email_to_true(): void
    {
        $res = Reservation::create([
            'booker_name' => 'Default Guest',
            'email' => 'default@example.com',
            'phone' => '09171234567',
            'reservation_date' => Carbon::now()->toDateString(),
            'total_days' => 1,
            'number_of_guests' => 1,
            'status' => 'Pending',
            'total_amount' => 100.00,
            'amount_paid' => 100.00,
            'remaining_balance' => 0.00,
            'payment_status' => 'Paid',
        ]);

        $res->refresh();
        $this->assertTrue($res->allow_sms);
        $this->assertTrue($res->allow_email);
        $this->assertTrue($res->canReceiveSms());
        $this->assertTrue($res->canReceiveEmail());
    }

    public function test_walk_in_creation_respects_privacy_preferences_opt_out(): void
    {
        $this->makeStaffSession();

        $payload = [
            'guest_mode' => 'with_primary',
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
            'start_slot' => 'Daytime',
            'end_slot' => 'Daytime',
            'entrance_option' => 'all_paid',
            'pool_option' => 'no_pool',
            'primary_guest' => [
                'first_name' => 'Private',
                'last_name' => 'Visitor',
                'age' => 30,
                'gender' => 'Male',
                'is_foreigner' => false,
                'phone' => '09179998888',
                'email' => 'private@example.com',
            ],
            'allow_sms' => false,
            'allow_email' => false,
            'total_amount' => 100.00,
        ];

        $response = $this->post(route('staff.checkins.guests.store'), $payload);
        $response->assertStatus(302); // Redirect back on successful walk-in creation

        $reservation = Reservation::where('phone', '09179998888')->first();
        $this->assertNotNull($reservation);
        $this->assertFalse($reservation->allow_sms);
        $this->assertFalse($reservation->allow_email);
        $this->assertFalse($reservation->canReceiveSms());
        $this->assertFalse($reservation->canReceiveEmail());
    }

    public function test_reservation_check_in_updates_privacy_preferences(): void
    {
        $this->makeStaffSession();
        $res = $this->createSampleReservation(allowSms: true, allowEmail: true);

        $response = $this->postJson("/staff/reservations/{$res->id}/check-in", [
            'guest_mode' => 'with_primary',
            'primary_guest' => [
                'first_name' => 'Secret',
                'last_name' => 'Guest',
                'age' => 28,
                'gender' => 'Male',
            ],
            'total_amount_to_pay' => 250.00,
            'amount_collected' => 250.00,
            'allow_sms' => false,
            'allow_email' => false,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'allow_sms' => false,
            'allow_email' => false,
        ]);

        $res->refresh();
        $this->assertEquals('Checked In', $res->status);
        $this->assertFalse($res->allow_sms);
        $this->assertFalse($res->allow_email);
        $this->assertFalse($res->canReceiveSms());
        $this->assertFalse($res->canReceiveEmail());
    }

    public function test_checkout_receipt_email_is_skipped_when_guest_opted_out_of_email(): void
    {
        Mail::fake();
        $this->makeStaffSession();

        $res = $this->createSampleReservation(allowSms: true, allowEmail: false);
        $res->update(['status' => 'Checked In', 'check_in' => now()->subHours(3)]);

        $customer = Customer::create([
            'first_name' => 'Secret',
            'last_name' => 'Guest',
            'email' => 'secret@example.com',
            'phone' => '09171234567',
            'gender' => 'Male',
        ]);

        ReservationGuest::create([
            'reservation_id' => $res->id,
            'customer_id' => $customer->id,
            'is_primary_guest' => true,
        ]);

        $response = $this->postJson("/staff/reservations/{$res->id}/check-out", [
            'reservation_id' => $res->id,
        ]);

        $response->assertOk();
        Mail::assertNotSent(CheckoutReceiptMail::class);
    }

    public function test_phil_sms_confirmation_is_skipped_when_guest_opted_out_of_sms(): void
    {
        Http::fake();
        $res = $this->createSampleReservation(allowSms: false, allowEmail: true);

        $smsService = new PhilSmsService(apiToken: 'fake-token');
        $result = $smsService->sendReservationConfirmation($res);

        $this->assertTrue($result['success']);
        $this->assertEquals(['opted_out' => true], $result['data']);
        Http::assertNothingSent();
    }

    public function test_announcement_sms_broadcast_skips_opted_out_reservation(): void
    {
        $this->withSession([
            'auth_user' => [
                'id' => 1,
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'role' => 'admin',
            ]
        ]);

        $allowedRes = $this->createSampleReservation(allowSms: true, allowEmail: true);
        $allowedRes->update(['phone' => '09171111111']);

        $optedOutRes = $this->createSampleReservation(allowSms: false, allowEmail: true);
        $optedOutRes->update(['phone' => '09172222222']);

        Http::fake([
            '*' => Http::response(['status' => 'success', 'message' => 'SMS queued'], 200),
        ]);

        $response = $this->postJson('/admin/announcements/send-sms', [
            'reservation_ids' => [$allowedRes->id, $optedOutRes->id],
            'message' => 'Park will be closed for maintenance tomorrow.',
        ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertEquals(1, $data['sent_count']);

        $skipped = collect($data['announcement']['delivery_details'])->firstWhere('reservation_id', $optedOutRes->id);
        $this->assertNotNull($skipped);
        $this->assertEquals('skipped', $skipped['status']);
        $this->assertStringContainsString('opted out of SMS', $skipped['reason']);
    }

    public function test_staff_can_update_privacy_preferences_endpoint(): void
    {
        $this->makeStaffSession();
        $res = $this->createSampleReservation(allowSms: true, allowEmail: true);

        $response = $this->postJson("/staff/reservations/{$res->id}/privacy-preferences", [
            'allow_sms' => false,
            'allow_email' => true,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'allow_sms' => false,
            'allow_email' => true,
        ]);

        $res->refresh();
        $this->assertFalse($res->allow_sms);
        $this->assertTrue($res->allow_email);

        $this->assertDatabaseHas('activity_logs', [
            'reservation_id' => $res->id,
            'activity_type' => 'privacy_preferences_updated',
        ]);
    }
}
