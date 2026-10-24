<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reschedule_requests') && ! Schema::hasColumn('reschedule_requests', 'replacement_amenity_id')) {
            Schema::table('reschedule_requests', function (Blueprint $table) {
                $table->string('replacement_amenity_id')->nullable()->after('requested_date');
                $table->foreign('replacement_amenity_id')->references('id')->on('amenities')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reschedule_requests') && Schema::hasColumn('reschedule_requests', 'replacement_amenity_id')) {
            Schema::table('reschedule_requests', function (Blueprint $table) {
                $table->dropForeign(['replacement_amenity_id']);
                $table->dropColumn('replacement_amenity_id');
            });
        }
    }
};
