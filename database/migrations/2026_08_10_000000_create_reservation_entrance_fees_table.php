<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stores the entrance (+pool) charge for a reservation, separate from
     * amenity fees. pricing_type is the chosen time period and is only set
     * when the reservation has NO amenities — when amenities exist, the
     * checkout timer references the amenity rows' pricing_type instead,
     * so this column stays null.
     */
    public function up(): void
    {
        Schema::create('reservation_entrance_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->string('pricing_type')->nullable();
            $table->decimal('total_amount', 8, 2)->default(0);
            $table->decimal('pool_fee', 8, 2)->default(0);
            $table->unsignedInteger('adult_count')->default(0);
            $table->unsignedInteger('child_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_entrance_fees');
    }
};
