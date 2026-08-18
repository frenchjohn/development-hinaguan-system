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
        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->string('user_type', 32); // 'staff' or 'admin'
            $table->unsignedBigInteger('user_id');
            $table->string('role', 16); // 'user' or 'assistant'
            $table->longText('content');
            $table->string('model', 128)->nullable();
            $table->timestamps();

            $table->index(['user_type', 'user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_messages');
    }
};
