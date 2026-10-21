<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('feedback_tokens')) {
            Schema::create('feedback_tokens', function (Blueprint $table) {
                $table->id();
                $table->string('token', 64)->unique();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->foreignId('reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
                $table->foreignId('feedback_id')->nullable()->constrained('feedbacks')->nullOnDelete();
                $table->dateTime('expires_at');
                $table->dateTime('submitted_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('feedbacks')) {
            Schema::table('feedbacks', function (Blueprint $table) {
                if (! Schema::hasColumn('feedbacks', 'reservation_id')) {
                    $table->foreignId('reservation_id')->nullable()->after('is_shown')->constrained('reservations')->nullOnDelete();
                }
                if (! Schema::hasColumn('feedbacks', 'customer_id')) {
                    $table->foreignId('customer_id')->nullable()->after('reservation_id')->constrained('customers')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('feedbacks')) {
            Schema::table('feedbacks', function (Blueprint $table) {
                if (Schema::hasColumn('feedbacks', 'customer_id')) {
                    $table->dropForeign(['customer_id']);
                    $table->dropColumn('customer_id');
                }
                if (Schema::hasColumn('feedbacks', 'reservation_id')) {
                    $table->dropForeign(['reservation_id']);
                    $table->dropColumn('reservation_id');
                }
            });
        }

        Schema::dropIfExists('feedback_tokens');
    }
};
