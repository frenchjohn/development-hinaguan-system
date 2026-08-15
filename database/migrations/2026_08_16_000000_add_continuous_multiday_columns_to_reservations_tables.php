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
        Schema::table('reservations', function (Blueprint $table) {
            $table->dateTime('end_date')->nullable()->after('reservation_date');
            $table->string('start_slot')->default('Daytime')->after('end_date');
            $table->string('end_slot')->default('Daytime')->after('start_slot');
            $table->unsignedInteger('total_days')->default(1)->after('end_slot');
        });

        Schema::table('reservation_amenities', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('amenity_id');
            $table->date('end_date')->nullable()->after('start_date');
            $table->string('start_slot')->default('Daytime')->after('end_date');
            $table->string('end_slot')->default('Daytime')->after('start_slot');
            $table->unsignedInteger('day_slots_count')->default(1)->after('end_slot');
            $table->unsignedInteger('night_slots_count')->default(0)->after('day_slots_count');
            
            // Allow more flexible pricing types or multiple instances
            $table->string('pricing_type', 100)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['end_date', 'start_slot', 'end_slot', 'total_days']);
        });

        Schema::table('reservation_amenities', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date', 'start_slot', 'end_slot', 'day_slots_count', 'night_slots_count']);
        });
    }
};
