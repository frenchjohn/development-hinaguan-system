<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\ParkSetting;

class ParkSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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

            'facebook_link' => null,
            'brenda_available' => true,
        ]);
    }
}