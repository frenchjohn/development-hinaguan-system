<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('park_settings', function (Blueprint $table) {
            $table->id();

            // Park Information
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();

            // Operating Hours
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();

            // Reservation Time
            $table->time('daytime_start');
            $table->time('daytime_end');
            $table->time('nighttime_start');
            $table->time('nighttime_end');

            // Entrance Fees
            $table->decimal('daytime_adult_entrance_fee', 8, 2)->default(0);
            $table->decimal('daytime_child_entrance_fee', 8, 2)->default(0);
            $table->decimal('nighttime_adult_entrance_fee', 8, 2)->default(0);
            $table->decimal('nighttime_child_entrance_fee', 8, 2)->default(0);
            $table->decimal('day_pool_fee', 8, 2)->default(0);
            $table->decimal('night_pool_fee', 8, 2)->default(0);

            // Social Media
            $table->string('facebook_link')->nullable();

            // Celebrity / Mascot Availability
            $table->boolean('brenda_available')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('park_settings');
    }
};