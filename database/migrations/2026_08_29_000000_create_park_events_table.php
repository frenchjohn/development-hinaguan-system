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
        if (!Schema::hasTable('park_events')) {
            Schema::create('park_events', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->date('date');
                $table->string('day'); // e.g. Saturday, Sunday, Monday
                $table->text('event'); // Description of what event is taking place
                $table->text('description')->nullable();
                $table->string('time')->nullable();
                $table->string('location')->nullable();
                $table->string('badge')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('park_events');
    }
};
