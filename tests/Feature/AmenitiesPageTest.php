<?php

namespace Tests\Feature;

use App\Models\Amenity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmenitiesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_displays_amenities_with_available_dates_on_the_amenities_page(): void
    {
        Amenity::create([
            'id' => 'amenity-1',
            'amenities_name' => 'Picnic Area',
            'daytime_price' => '500',
            'nighttime_price' => '700',
            'additional_per_head' => '100',
            'minimum_capacity' => '10',
            'maximum_capacity' => '20',
            'description' => 'Test amenity',
            'image' => null,
            'status' => true,
        ]);

        $response = $this->get('/amenities');

        $response->assertOk();
        $response->assertSee('Picnic Area');
        $response->assertSee('Amenity Details');
        $response->assertSee('View details');
    }
}
