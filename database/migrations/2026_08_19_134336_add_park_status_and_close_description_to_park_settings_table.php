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
        Schema::table('park_settings', function (Blueprint $table) {
            $table->string('park_status', 20)->default('open')->after('email');
            $table->text('close_description')->nullable()->after('park_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('park_settings', function (Blueprint $table) {
            $table->dropColumn(['park_status', 'close_description']);
        });
    }
};
