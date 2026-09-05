<?php

namespace Tests\Feature;

use App\Models\AdminAccount;
use App\Models\ParkSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BrendaAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ParkSetting::create([
            'contact_number' => '0985-323-9532',
            'email' => 'parkhinaguan@gmail.com',
            'opening_time' => '08:00:00',
            'closing_time' => '17:00:00',
            'daytime_start' => '08:00:00',
            'daytime_end' => '17:00:00',
            'nighttime_start' => '18:00:00',
            'nighttime_end' => '08:00:00',
            'daytime_adult_entrance_fee' => 20,
            'daytime_child_entrance_fee' => 0,
            'nighttime_adult_entrance_fee' => 50,
            'nighttime_child_entrance_fee' => 0,
            'day_pool_fee' => 50,
            'night_pool_fee' => 70,
            'park_status' => 'open',
            'brenda_available' => true,
        ]);
    }

    public function test_homepage_shows_brenda_when_available(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Brenda is in the park');
    }

    public function test_homepage_hides_brenda_when_not_available(): void
    {
        $parkSettings = ParkSetting::first();
        $parkSettings->update(['brenda_available' => false]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('Brenda is in the park');
    }

    public function test_admin_can_update_brenda_availability(): void
    {
        $admin = AdminAccount::create([
            'name' => 'Admin User',
            'email' => 'admin@hinaguan.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'admin',
            ],
        ])->postJson(route('admin.settings.park.update'), [
            'contact_number' => '0985-323-9532',
            'email' => 'parkhinaguan@gmail.com',
            'park_status' => 'open',
            'daytime_start' => '08:00',
            'daytime_end' => '17:00',
            'nighttime_start' => '18:00',
            'nighttime_end' => '08:00',
            'daytime_adult_entrance_fee' => 25,
            'daytime_child_entrance_fee' => 0,
            'nighttime_adult_entrance_fee' => 50,
            'nighttime_child_entrance_fee' => 0,
            'day_pool_fee' => 50,
            'night_pool_fee' => 70,
            'brenda_available' => 0,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'brenda_available' => false,
        ]);

        $this->assertDatabaseHas('park_settings', [
            'brenda_available' => 0,
        ]);
    }
}
