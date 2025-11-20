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
        Schema::table('emp_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_to')->nullable()->index(); // user id admin/reviewer
            $table->timestamp('assigned_at')->nullable();
            // opsional: hard-lock sederhana biar tidak dipegang terlalu lama
            $table->timestamp('lock_expires_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emp_documents', function (Blueprint $table) {
            $table->dropColumn(['assigned_to', 'assigned_at', 'lock_expires_at']);
        });
    }
};
