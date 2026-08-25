<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservation_guests', function (Blueprint $table) {
            $table->boolean('has_pool_access')->default(false)->after('is_primary_guest');
        });

        Schema::table('reservation_entrance_fees', function (Blueprint $table) {
            $table->string('pool_option')->default('no_pool')->after('pricing_type');
            $table->unsignedInteger('pool_access_count')->default(0)->after('pool_fee');
        });
    }

    public function down(): void
    {
        Schema::table('reservation_guests', function (Blueprint $table) {
            $table->dropColumn('has_pool_access');
        });

        Schema::table('reservation_entrance_fees', function (Blueprint $table) {
            $table->dropColumn(['pool_option', 'pool_access_count']);
        });
    }
};
