<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reservation_charges')) {
            Schema::create('reservation_charges', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
                $table->string('amenity_id')->nullable();
                $table->foreign('amenity_id')->references('id')->on('amenities')->nullOnDelete();
                $table->text('description')->nullable();
                $table->enum('charge_type', ['damage', 'cleaning', 'lost', 'others']);
                $table->decimal('amount', 10, 2);
                $table->enum('status', ['paid', 'unpaid'])->default('unpaid');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_charges');
    }
};
