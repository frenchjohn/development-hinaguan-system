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
        if (Schema::hasTable('reservation_charges') && Schema::hasColumn('reservation_charges', 'amenity_id')) {
            Schema::table('reservation_charges', function (Blueprint $table) {
                // Drop foreign key if exists
                try {
                    $table->dropForeign(['amenity_id']);
                } catch (\Throwable $e) {
                    // Fallback in case constraint name differs
                }

                $table->dropColumn('amenity_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('reservation_charges') && ! Schema::hasColumn('reservation_charges', 'amenity_id')) {
            Schema::table('reservation_charges', function (Blueprint $table) {
                $table->string('amenity_id')->nullable()->after('reservation_id');
                $table->foreign('amenity_id')->references('id')->on('amenities')->nullOnDelete();
            });
        }
    }
};

