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
        Schema::create('amenities', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('amenities_name');
            $table->string('daytime_price');
            $table->string('nighttime_price');
            $table->string('additional_per_head')->nullable();
            $table->string('minimum_capacity');
            $table->string('maximum_capacity')->nullable();
            $table->string('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('status')->default(true); // true = enabled, false = disabled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amenities');
    }
};
