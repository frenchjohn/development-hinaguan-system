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

        // Clean up any historical duplicate amenities by amenities_name
        $groupedAmenities = Amenity::all()->groupBy('amenities_name');
        foreach ($groupedAmenities as $records) {
            if ($records->count() > 1) {
                $keeper = $records->first(function ($r) {
                    return ReservationAmenity::where('amenity_id', $r->id)->exists();
                }) ?? $records->first();

                foreach ($records as $r) {
                    if ($r->id !== $keeper->id) {
                        AmenityBenefit::where('amenity_id', $r->id)->delete();
                        $r->delete();
                    }
                }
            }
        }

        // Clean up historical duplicate amenities or removed A-House 9
        $ahouse9 = Amenity::where('amenities_name', 'A-House 9')->first();
        if ($ahouse9) {
            AmenityBenefit::where('amenity_id', $ahouse9->id)->delete();
            ReservationAmenity::where('amenity_id', $ahouse9->id)->delete();
            $ahouse9->delete();
        }

        // A-Houses (1 to 8)
        $ahouseAircon = [2, 3];
        for ($i = 1; $i <= 8; $i++) {
            $specificImage = "amenities_images/ahouse{$i}.jpg";
            if (file_exists(storage_path("app/public/{$specificImage}"))) {
                $imageFile = $specificImage;
            } elseif (in_array($i, $ahouseAircon, true)) {
                $imageFile = 'amenities_images/ahouse2.jpg';
            } else {
                $imageFile = 'amenities_images/ahouse1.jpg';
            }

            $ahouse = Amenity::where('amenities_name', "A-House {$i}")->first();
            $ahouseData = [
                'daytime_price' => 300,
                'nighttime_price' => 500,
                'additional_per_head' => 100,
                'minimum_capacity' => 1,
                'maximum_capacity' => 2,
                'description' => 'A comfortable and relaxing room featuring a cozy bed in a peaceful, refreshing, and chilled environment.',
                'image' => $imageFile,
                'status' => true,
            ];

            if ($ahouse) {
                $ahouse->update($ahouseData);
            } else {
                $ahouseData['id'] = (string) Str::uuid();
                $ahouseData['amenities_name'] = "A-House {$i}";
                $ahouse = Amenity::create($ahouseData);
            }

            AmenityBenefit::updateOrCreate(
                ['amenity_id' => $ahouse->id],
                [
                    'is_aircon' => in_array($i, $ahouseAircon, true),
                    'free_entrance' => true,
                    'free_pool' => true,
                ]
            );
        }

        // Cottages (1 to 6)
        for ($num = 1; $num <= 6; $num++) {
            $specificImage = "amenities_images/cottage{$num}.jpg";
            if (file_exists(storage_path("app/public/{$specificImage}"))) {
                $img = $specificImage;
            } elseif ($num === 4 && file_exists(storage_path('app/public/amenities_images/cottage44.jpg'))) {
                $img = 'amenities_images/cottage44.jpg';
            } else {
                $img = 'amenities_images/cottage' . ((($num - 1) % 4) + 1) . '.jpg';
            }

            $cottage = Amenity::where('amenities_name', "Cottage {$num}")->first();
            $cottageData = [
                'daytime_price' => 200,
                'nighttime_price' => 200,
                'additional_per_head' => null,
                'minimum_capacity' => 1,
                'maximum_capacity' => null,
                'description' => 'A comfortable open-air cottage complete with chairs and a table, perfect for unwinding and dining with family and friends.',
                'image' => $img,
                'status' => true,
            ];

            if ($cottage) {
                $cottage->update($cottageData);
            } else {
                $cottageData['id'] = (string) Str::uuid();
                $cottageData['amenities_name'] = "Cottage {$num}";
                $cottage = Amenity::create($cottageData);
            }

            AmenityBenefit::updateOrCreate(
                ['amenity_id' => $cottage->id],
                [
                    'is_aircon' => false,
                    'free_entrance' => false,
                    'free_pool' => false,
                ]
            );
        }

        // Payags (1 to 6)
        $payagDescriptions = [
            1 => 'A traditional native Filipino hut with natural bamboo ventilation, providing an authentic and relaxing open-air park stay.',
            2 => 'A charming riverside native payag surrounded by lush greenery, ideal for relaxing afternoons and tranquil evening getaways.',
            3 => 'A cozy native wooden payag offering refreshing mountain breeze, perfect for small groups and peaceful picnics.',
            4 => 'An authentic open-air bamboo payag with scenic nature views, ideal for day tours and evening relaxation.',
            5 => 'A serene garden-side native payag nestled under shade trees, providing a cool and comfortable outdoor haven.',
            6 => 'A spacious riverside payag crafted with indigenous materials, perfect for dining and bonding with loved ones.',
        ];

        for ($num = 1; $num <= 6; $num++) {
            $specificImage = "amenities_images/payag{$num}.jpg";
            $img = file_exists(storage_path("app/public/{$specificImage}"))
                ? $specificImage
                : 'amenities_images/payag' . ((($num - 1) % 2) + 1) . '.jpg';

            $payag = Amenity::where('amenities_name', "Payag {$num}")->first();
            $payagData = [
                'daytime_price' => 300,
                'nighttime_price' => 300,
                'additional_per_head' => null,
                'minimum_capacity' => 1,
                'maximum_capacity' => null,
                'description' => $payagDescriptions[$num] ?? $payagDescriptions[1],
                'image' => $img,
                'status' => true,
            ];

            if ($payag) {
                $payag->update($payagData);
            } else {
                $payagData['id'] = (string) Str::uuid();
                $payagData['amenities_name'] = "Payag {$num}";
                $payag = Amenity::create($payagData);
            }

            AmenityBenefit::updateOrCreate(
                ['amenity_id' => $payag->id],
                [
                    'is_aircon' => false,
                    'free_entrance' => false,
                    'free_pool' => false,
                ]
            );
        }

        // Function Hall
        $functionHall = Amenity::where('amenities_name', 'Function Hall')->first();
        $functionHallData = [
            'daytime_price' => 5000,
            'nighttime_price' => 10000,
            'additional_per_head' => null,
            'minimum_capacity' => 1,
            'maximum_capacity' => null,
            'description' => 'A spacious and scenic event pavilion surrounded by nature, perfect for celebrations, reunions, gatherings, and special occasions.',
            'image' => 'amenities_images/function_hall.jpg',
            'status' => true,
        ];

        if ($functionHall) {
            $functionHall->update($functionHallData);
        } else {
            $functionHallData['id'] = (string) Str::uuid();
            $functionHallData['amenities_name'] = 'Function Hall';
            $functionHall = Amenity::create($functionHallData);
        }

        AmenityBenefit::updateOrCreate(
            ['amenity_id' => $functionHall->id],
            [
                'is_aircon' => false,
                'free_entrance' => true,
                'free_pool' => true,
            ]
        );
    }
}
