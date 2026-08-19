<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ParkSetting;
use App\Models\StaffAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParkStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ParkSetting::create([
            'contact_number' => '0985-323-9532',
            'email' => 'parkhinaguan@gmail.com',
            'park_status' => 'open',
            'close_description' => null,
            'daytime_start' => '08:01:00',
            'daytime_end' => '18:00:00',
            'nighttime_start' => '18:01:00',
            'nighttime_end' => '08:00:00',
            'daytime_adult_entrance_fee' => 70,
            'daytime_child_entrance_fee' => 50,
            'nighttime_adult_entrance_fee' => 100,
            'nighttime_child_entrance_fee' => 70,
            'day_pool_fee' => 100,
            'night_pool_fee' => 150,
        ]);
    }

    public function test_admin_can_close_park_with_description()
    {
        $response = $this->withSession([
            'auth_user' => [
                'id' => 1,
                'name' => 'Super Admin',
                'role' => 'admin',
            ]
        ])->postJson(route('admin.settings.park.update'), [
            'contact_number' => '0985-323-9532',
            'email' => 'parkhinaguan@gmail.com',
            'park_status' => 'closed',
            'close_description' => 'Temporarily closed for scheduled river maintenance.',
            'daytime_start' => '08:01',
            'daytime_end' => '18:00',
            'nighttime_start' => '18:01',
            'nighttime_end' => '08:00',
            'daytime_adult_entrance_fee' => 70,
            'daytime_child_entrance_fee' => 50,
            'nighttime_adult_entrance_fee' => 100,
            'nighttime_child_entrance_fee' => 70,
            'day_pool_fee' => 100,
            'night_pool_fee' => 150,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'park_status' => 'closed',
            'close_description' => 'Temporarily closed for scheduled river maintenance.',
        ]);

        $settings = ParkSetting::first();
        $this->assertEquals('closed', $settings->park_status);
        $this->assertEquals('Temporarily closed for scheduled river maintenance.', $settings->close_description);
        $this->assertTrue($settings->isClosed());
        $this->assertFalse($settings->isOpen());

        // Check activity log
        $this->assertDatabaseHas('activity_logs', [
            'activity_type' => 'park_status_updated',
            'title' => 'Park Status: Closed',
        ]);
    }

    public function test_setting_park_to_open_auto_clears_close_description()
    {
        $settings = ParkSetting::first();
        $settings->update([
            'park_status' => 'closed',
            'close_description' => 'Closed due to weather conditions.',
        ]);

        $response = $this->withSession([
            'auth_user' => [
                'id' => 1,
                'name' => 'Super Admin',
                'role' => 'admin',
            ]
        ])->postJson(route('admin.settings.park.update'), [
            'contact_number' => '0985-323-9532',
            'email' => 'parkhinaguan@gmail.com',
            'park_status' => 'open',
            'close_description' => 'Some stale description left in form',
            'daytime_start' => '08:01',
            'daytime_end' => '18:00',
            'nighttime_start' => '18:01',
            'nighttime_end' => '08:00',
            'daytime_adult_entrance_fee' => 70,
            'daytime_child_entrance_fee' => 50,
            'nighttime_adult_entrance_fee' => 100,
            'nighttime_child_entrance_fee' => 70,
            'day_pool_fee' => 100,
            'night_pool_fee' => 150,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'park_status' => 'open',
            'close_description' => null,
        ]);

        $freshSettings = ParkSetting::first();
        $this->assertEquals('open', $freshSettings->park_status);
        $this->assertNull($freshSettings->close_description);
        $this->assertTrue($freshSettings->isOpen());

        // Check activity log
        $this->assertDatabaseHas('activity_logs', [
            'activity_type' => 'park_status_updated',
            'title' => 'Park Status: Open',
        ]);
    }

    public function test_api_park_settings_returns_status_and_description()
    {
        $settings = ParkSetting::first();
        $settings->update([
            'park_status' => 'closed',
            'close_description' => 'Closed for pool repairs.',
        ]);

        $response = $this->getJson('/api/park-settings');
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'park_status' => 'closed',
            'close_description' => 'Closed for pool repairs.',
        ]);
    }

    public function test_homepage_renders_closed_status_and_description()
    {
        $settings = ParkSetting::first();
        $settings->update([
            'park_status' => 'closed',
            'close_description' => 'Closed today for water treatment.',
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Park Closed');
        $response->assertSee('Closed today for water treatment.');
    }
}
