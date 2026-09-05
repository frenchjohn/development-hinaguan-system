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

        // A-Houses (1 to 9)
        $ahouseAircon = [2, 3];
        for ($i = 1; $i <= 9; $i++) {
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

        // Cottages (1 to 4)
        $cottages = [
            1 => 'amenities_images/cottage1.jpg',
            2 => 'amenities_images/cottage2.jpg',
            3 => 'amenities_images/cottage3.jpg',
            4 => file_exists(storage_path('app/public/amenities_images/cottage4.jpg'))
                ? 'amenities_images/cottage4.jpg'
                : 'amenities_images/cottage44.jpg',
        ];

        foreach ($cottages as $num => $img) {
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

        // Payags (1 and 2)
        $payags = [
            1 => [
                'image' => 'amenities_images/payag1.jpg',
                'description' => 'A traditional native Filipino hut with natural bamboo ventilation, providing an authentic and relaxing open-air park stay.',
            ],
            2 => [
                'image' => 'amenities_images/payag2.jpg',
                'description' => 'A charming riverside native payag surrounded by lush greenery, ideal for relaxing afternoons and tranquil evening getaways.',
            ],
        ];

        foreach ($payags as $num => $p) {
            $payag = Amenity::where('amenities_name', "Payag {$num}")->first();
            $payagData = [
                'daytime_price' => 300,
                'nighttime_price' => 300,
                'additional_per_head' => null,
                'minimum_capacity' => 1,
                'maximum_capacity' => null,
                'description' => $p['description'],
                'image' => $p['image'],
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
    }
}
