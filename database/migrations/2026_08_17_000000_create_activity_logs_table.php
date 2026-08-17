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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('activity_type', 64)->index();
            $table->string('title', 128);
            $table->text('description');
            $table->unsignedBigInteger('reservation_id')->nullable()->index();
            $table->string('staff_id', 64)->nullable()->index();
            $table->string('actor_name', 128)->default('System');
            $table->string('actor_role', 32)->default('system'); // staff, admin, guest, system
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
