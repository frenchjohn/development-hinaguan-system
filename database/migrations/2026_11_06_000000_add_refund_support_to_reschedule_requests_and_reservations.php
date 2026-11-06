<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reschedule_requests')) {
            Schema::table('reschedule_requests', function (Blueprint $table) {
                if (! Schema::hasColumn('reschedule_requests', 'request_type')) {
                    $table->string('request_type', 20)->default('reschedule')->after('requested_date');
                }
                if (! Schema::hasColumn('reschedule_requests', 'refund_amount')) {
                    $table->decimal('refund_amount', 10, 2)->nullable()->after('request_type');
                }
                if (! Schema::hasColumn('reschedule_requests', 'ewallet_provider')) {
                    $table->string('ewallet_provider', 50)->nullable()->after('refund_amount');
                }
                if (! Schema::hasColumn('reschedule_requests', 'ewallet_account_number')) {
                    $table->string('ewallet_account_number', 50)->nullable()->after('ewallet_provider');
                }
                if (! Schema::hasColumn('reschedule_requests', 'ewallet_account_name')) {
                    $table->string('ewallet_account_name', 100)->nullable()->after('ewallet_account_number');
                }
                if (! Schema::hasColumn('reschedule_requests', 'refund_reference_number')) {
                    $table->string('refund_reference_number', 100)->nullable()->after('ewallet_account_name');
                }
                if (! Schema::hasColumn('reschedule_requests', 'refund_notes')) {
                    $table->text('refund_notes')->nullable()->after('refund_reference_number');
                }
                if (! Schema::hasColumn('reschedule_requests', 'refunded_at')) {
                    $table->dateTime('refunded_at')->nullable()->after('refund_notes');
                }
            });
        }

        if (Schema::hasTable('reservations')) {
            Schema::table('reservations', function (Blueprint $table) {
                if (! Schema::hasColumn('reservations', 'refunded_amount')) {
                    $table->decimal('refunded_amount', 10, 2)->default(0)->after('amount_paid');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reschedule_requests')) {
            Schema::table('reschedule_requests', function (Blueprint $table) {
                $cols = [
                    'request_type', 'refund_amount', 'ewallet_provider',
                    'ewallet_account_number', 'ewallet_account_name',
                    'refund_reference_number', 'refund_notes', 'refunded_at'
                ];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('reschedule_requests', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('reservations')) {
            Schema::table('reservations', function (Blueprint $table) {
                if (Schema::hasColumn('reservations', 'refunded_amount')) {
                    $table->dropColumn('refunded_amount');
                }
            });
        }
    }
};

