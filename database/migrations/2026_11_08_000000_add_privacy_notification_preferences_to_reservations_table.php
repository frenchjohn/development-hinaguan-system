<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reservations')) {
            Schema::table('reservations', function (Blueprint $table) {
                if (! Schema::hasColumn('reservations', 'allow_sms')) {
                    $table->boolean('allow_sms')->default(true)->after('payment_method');
                }
                if (! Schema::hasColumn('reservations', 'allow_email')) {
                    $table->boolean('allow_email')->default(true)->after('allow_sms');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reservations')) {
            Schema::table('reservations', function (Blueprint $table) {
                if (Schema::hasColumn('reservations', 'allow_email')) {
                    $table->dropColumn('allow_email');
                }
                if (Schema::hasColumn('reservations', 'allow_sms')) {
                    $table->dropColumn('allow_sms');
                }
            });
        }
    }
};

