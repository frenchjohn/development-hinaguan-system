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
        Schema::create('reservation_amenities', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->string('amenity_id')->nullable();

            // Price selected during reservation
            $table->enum('pricing_type', [
                'Daytime',
                'Nighttime',
                'Daytime Aircon',
                'Nighttime Aircon',
                'DayNight Time',
                'DayNight Time Aircon'
            ]);

            // Snapshot of the selected price
            $table->decimal('price_at_booking', 10, 2)->nullable();

            // Number of this amenity reserved
            $table->unsignedInteger('quantity')->default(1);

            // Optional remarks
            $table->text('remarks')->nullable();

            $table->timestamps();

            // Foreign Keys
            $table->foreign('amenity_id')
                ->references('id')
                ->on('amenities')
                ->cascadeOnDelete();

            // Prevent duplicate amenity entries
            $table->unique(
                ['reservation_id', 'amenity_id', 'pricing_type'],
                'reservation_amenity_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_amenities');
    }
};