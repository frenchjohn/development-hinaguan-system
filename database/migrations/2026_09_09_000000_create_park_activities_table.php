<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('park_activities')) {
            Schema::create('park_activities', function (Blueprint $table) {
                $table->id();
                $table->string('activity');
                $table->text('description');
                $table->string('image')->nullable();
                $table->timestamps();
            });

            DB::table('park_activities')->insert([
                [
                    'activity' => 'River Trekking',
                    'description' => 'Follow scenic trails along the riverbank and discover hidden spots, rock formations, and lush vegetation.',
                    'image' => 'images/River_Trecking.jpg',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'activity' => 'Swimming & Wading',
                    'description' => 'Cool off in the natural pool or wade in the shallow river areas, perfect for kids and adults alike.',
                    'image' => 'images/swimming_and_wading.jpg',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'activity' => 'Picnic & Bonding',
                    'description' => 'Spread out at open picnic areas, enjoy meals with loved ones, and soak in the peaceful riverside atmosphere.',
                    'image' => 'images/picnic_and_bonding.jpg',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'activity' => 'Photography',
                    'description' => "Capture stunning shots along the riverside, scenic landscapes, and rustic cottages - a content creator's paradise.",
                    'image' => 'images/photography.jpg',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('park_activities');
    }
};
