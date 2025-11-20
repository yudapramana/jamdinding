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
        Schema::table('users', function (Blueprint $table) {
             // 🔹 Relasi opsional ke event (user per-event)
            $table->foreignId('event_id')
                ->nullable()
                ->constrained()      // default ke tabel 'events'
                ->nullOnDelete();    // kalau event dihapus, event_id di-set null
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_id');
        });
    }
};
