<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add PayMongo payment tracking to reservations.
     *
     * - payment_intent_id: the PayMongo Payment Intent that holds the deposit.
     * - payment_method:   which PayMongo method paid (gcash / card / paymaya…).
     * - payment_status:   gains 'Unpaid' so an intent can exist before money
     *                     actually moves (the old enum only knew
     *                     'Partially Paid' / 'Paid').
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('payment_intent_id')->nullable()->after('payment_status');
            $table->string('payment_method')->nullable()->after('payment_intent_id');
        });

        // MySQL enum change needs a fresh column definition.
        Schema::table('reservations', function (Blueprint $table) {
            $table->enum('payment_status', [
                'Unpaid',
                'Partially Paid',
                'Paid',
            ])->default('Unpaid')->change();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->enum('payment_status', [
                'Partially Paid',
                'Paid',
            ])->default('Partially Paid')->change();
            $table->dropColumn(['payment_intent_id', 'payment_method']);
        });
    }
};
