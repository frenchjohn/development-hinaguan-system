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
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('message');
            $table->string('category')->default('sms_broadcast');
            $table->string('target_type')->default('selected_reservations');
            $table->unsignedInteger('recipient_count')->default(0);
            $table->json('target_reservation_ids')->nullable();
            $table->json('recipient_phones')->nullable();
            $table->string('delivery_status')->default('sent');
            $table->json('delivery_details')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
