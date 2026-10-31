<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reservation_guests') && ! Schema::hasColumn('reservation_guests', 'amenity_id')) {
            Schema::table('reservation_guests', function (Blueprint $table) {
                $table->string('amenity_id')->nullable()->after('reservation_id');
                $table->foreign('amenity_id')->references('id')->on('amenities')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reservation_guests') && Schema::hasColumn('reservation_guests', 'amenity_id')) {
            Schema::table('reservation_guests', function (Blueprint $table) {
                $table->dropForeign(['amenity_id']);
                $table->dropColumn('amenity_id');
            });
        }
    }
};

