<?php

namespace Tests\Feature;

use App\Models\AdminAccount;
use App\Models\ParkSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAmenitiesButtonPositionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ParkSetting::create([
            'park_name' => 'Hinaguan Nature Park',
            'daytime_start' => '08:00',
            'daytime_end' => '17:00',
            'nighttime_start' => '17:00',
            'nighttime_end' => '06:00',
            'daytime_adult_entrance_fee' => 70,
            'daytime_child_entrance_fee' => 30,
            'nighttime_adult_entrance_fee' => 100,
            'nighttime_child_entrance_fee' => 50,
            'day_pool_fee' => 50,
            'night_pool_fee' => 75,
            'park_status' => 'open',
        ]);
    }

    public function test_new_amenity_button_is_rendered_in_header(): void
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
        ])->get('/admin/amenities');

        $response->assertOk();

        $content = $response->getContent();

        // Ensure New Amenity button is rendered
        $this->assertStringContainsString('data-open-amenity-modal', $content);
        $this->assertStringContainsString('New Amenity', $content);

        // Ensure it is inside amenities-table-toolbar and NOT inside header
        $this->assertStringContainsString('amenities-table-toolbar__action', $content);
        $this->assertDoesNotMatchRegularExpression('/<header[^>]*>.*?data-open-amenity-modal.*?<\/header>/s', $content);

        // Ensure old empty space div is not rendered
        $this->assertStringNotContainsString('mb-6 flex items-center justify-end', $content);
    }
}
