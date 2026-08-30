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
        Schema::create('daily_weather_shift_logs', function (Blueprint $table) {
            $table->id();
            $table->date('log_date')->index();
            $table->enum('shift', ['Daytime', 'Nighttime'])->index();
            $table->string('weather_condition', 50)->default('Sunny'); // Sunny, Partly Cloudy, Cloudy, Rainy, Heavy Rain, Stormy
            $table->decimal('temperature_celsius', 5, 2)->nullable();
            $table->unsignedTinyInteger('precipitation_probability')->default(0); // 0 - 100%
            $table->unsignedInteger('actual_guests')->default(0);
            $table->unsignedInteger('actual_reservations')->default(0);
            $table->time('earliest_arrival_time')->nullable();
            $table->time('peak_arrival_time')->nullable();
            $table->time('latest_arrival_time')->nullable();
            $table->boolean('is_weekend')->default(false);
            $table->boolean('is_holiday')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['log_date', 'shift']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_weather_shift_logs');
    }
};
