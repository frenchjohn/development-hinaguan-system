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
        Schema::create('user_activity_reads', function (Blueprint $table) {
            $table->id();
            $table->string('user_type', 32); // 'admin' or 'staff'
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('last_seen_activity_id')->default(0);
            $table->timestamps();

            $table->unique(['user_type', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activity_reads');
    }
};
