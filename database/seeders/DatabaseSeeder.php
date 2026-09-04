<?php

namespace Database\Seeders;

use App\Models\AdminAccount;
use App\Models\Amenity;
use App\Models\AmenityBenefit;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\ReservationAmenity;
use App\Models\ReservationGuest;
use App\Models\StaffAccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ParkSettingSeeder::class,
            ParkRuleSeeder::class,
            DailyWeatherShiftLogSeeder::class,
        ]);

        StaffAccount::firstOrCreate(
            ['email' => 'staff@example.com'],
            [
                'name' => 'Staff User',
                'password' => Hash::make('staff1234'),
            ]
        );

        AdminAccount::firstOrCreate(
            ['email' => 'parkhinaguan@gmail.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin1234'),
            ]
        );

        $ahouse1 = Amenity::create([
            'id' => Str::uuid(),
            'amenities_name' => 'A-House 1',
            'daytime_price' => 300,
            'nighttime_price' => 500,
            'additional_per_head' => 100,
            'minimum_capacity' => 1,
            'maximum_capacity' => 2,
            'description' => 'A comfortable and relaxing room featuring a cozy bed in a peaceful, refreshing, and chilled environment.',
            'image' => null,
            'status' => true,
        ]);
        AmenityBenefit::create([
            'amenity_id' => $ahouse1->id,
            'is_aircon' => true,
            'free_entrance' => true,
            'free_pool' => true,
        ]);

        $ahouse2 = Amenity::create([
            'id' => Str::uuid(),
            'amenities_name' => 'A-House 2',
            'daytime_price' => 300,
            'nighttime_price' => 500,
            'additional_per_head' => 100,
            'minimum_capacity' => 1,
            'maximum_capacity' => 2,
            'description' => 'A comfortable and relaxing room featuring a cozy bed in a peaceful, refreshing, and chilled environment.',
            'image' => null,
            'status' => true,
        ]);
        AmenityBenefit::create([
            'amenity_id' => $ahouse2->id,
            'is_aircon' => false,
            'free_entrance' => true,
            'free_pool' => true,
        ]);

        $cottage1 = Amenity::create([
            'id' => Str::uuid(),
            'amenities_name' => 'Cottage 1',
            'daytime_price' => 200,
            'nighttime_price' => 200,
            'additional_per_head' => null,
            'minimum_capacity' => 1,
            'maximum_capacity' => null,
            'description' => 'A comfortable open-air cottage complete with chairs and a table, perfect for unwinding and dining with family and friends.',
            'image' => null,
            'status' => true,
        ]);
        AmenityBenefit::create([
            'amenity_id' => $cottage1->id,
            'is_aircon' => false,
            'free_entrance' => false,
            'free_pool' => false,
        ]);

        $cottage2 = Amenity::create([
            'id' => Str::uuid(),
            'amenities_name' => 'Cottage 2',
            'daytime_price' => 200,
            'nighttime_price' => 200,
            'additional_per_head' => null,
            'minimum_capacity' => 1,
            'maximum_capacity' => null,
            'description' => 'A comfortable open-air cottage complete with chairs and a table, perfect for unwinding and dining with family and friends.',
            'image' => null,
            'status' => true,
        ]);
        AmenityBenefit::create([
            'amenity_id' => $cottage2->id,
            'is_aircon' => false,
            'free_entrance' => false,
            'free_pool' => false,
        ]);

        $cottage3 = Amenity::create([
            'id' => Str::uuid(),
            'amenities_name' => 'Cottage 3',
            'daytime_price' => 200,
            'nighttime_price' => 200,
            'additional_per_head' => null,
            'minimum_capacity' => 1,
            'maximum_capacity' => null,
            'description' => 'A comfortable open-air cottage complete with chairs and a table, perfect for unwinding and dining with family and friends.',
            'image' => null,
            'status' => true,
        ]);
        AmenityBenefit::create([
            'amenity_id' => $cottage3->id,
            'is_aircon' => false,
            'free_entrance' => false,
            'free_pool' => false,
        ]);

        $cottage4 = Amenity::create([
            'id' => Str::uuid(),
            'amenities_name' => 'Cottage 4',
            'daytime_price' => 200,
            'nighttime_price' => 200,
            'additional_per_head' => null,
            'minimum_capacity' => 1,
            'maximum_capacity' => null,
            'description' => 'A comfortable open-air cottage complete with chairs and a table, perfect for unwinding and dining with family and friends.',
            'image' => null,
            'status' => true,
        ]);
        AmenityBenefit::create([
            'amenity_id' => $cottage4->id,
            'is_aircon' => false,
            'free_entrance' => false,
            'free_pool' => false,
        ]);
    }
}
