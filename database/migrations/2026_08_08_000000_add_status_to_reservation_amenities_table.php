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
        Schema::table('reservation_amenities', function (Blueprint $table) {
            $table->enum('status', ['Active', 'Completed'])->default('Active')->after('remarks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservation_amenities', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
