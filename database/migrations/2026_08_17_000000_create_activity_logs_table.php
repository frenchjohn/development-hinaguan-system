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
            $table->foreignId('staff_id')->nullable()->constrained('staff_accounts')->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
            $table->string('action', 64)->index();
            $table->string('activity_type', 64)->nullable()->index();
            $table->decimal('payment_amount', 10, 2)->default(0.00);
            $table->string('title', 128);
            $table->text('description');
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
