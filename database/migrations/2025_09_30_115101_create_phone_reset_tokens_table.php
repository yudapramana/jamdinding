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
        Schema::create('phone_reset_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 30)->index();        // 628xxx
            $table->string('token_hash');                // Hash dari OTP
            $table->timestamp('expires_at');             // default: now()+15 menit
            $table->timestamp('used_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['phone', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phone_reset_tokens');
    }
};
