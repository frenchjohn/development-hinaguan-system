<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reschedule_requests')) {
            Schema::create('reschedule_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
                $table->string('token', 64)->unique();
                $table->date('original_date');
                $table->date('requested_date')->nullable();
                $table->enum('status', ['pending', 'submitted', 'approved', 'declined', 'expired'])->default('pending');
                $table->dateTime('expires_at');
                $table->dateTime('used_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->foreign('approved_by')->references('id')->on('staff_accounts')->nullOnDelete();
                $table->dateTime('approved_at')->nullable();
                $table->text('reason')->nullable();
                $table->text('decline_reason')->nullable();
                $table->timestamps();

                $table->index(['reservation_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reschedule_requests');
    }
};
