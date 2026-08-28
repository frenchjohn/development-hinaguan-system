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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();

            // Booker Information
            $table->string('booker_name');
            $table->string('phone');
            $table->string('email');

            // Reservation Details
            $table->date('reservation_date')->nullable();
            $table->dateTime('check_in')->nullable();
            $table->dateTime('check_out')->nullable();
            $table->unsignedInteger('number_of_guests');
            $table->enum('reservation_type', ['walk_in', 'online'])->default('online');

            // Reservation Status
            $table->enum('status', [
                'Pending',
                'Confirmed',
                'Checked In',
                'Checked Out',
                'Cancelled',
                'No Show'
            ])->default('Pending');

            // Payment Summary
            $table->decimal('total_amount', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('remaining_balance', 10, 2);

            $table->enum('payment_status', [
                'Partially Paid',
                'Paid'
            ])->default('Partially Paid');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};