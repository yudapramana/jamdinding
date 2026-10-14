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
        Schema::create('event_judge_panel_clerks', function (Blueprint $table) {
            $table->id();

            // Relasi ke majelis hakim
            $table->foreignId('event_judge_panel_id')
                ->constrained('event_judge_panels')
                ->cascadeOnDelete();

            // Relasi ke tabel users (untuk akun panitera)
            // Tambahkan unique() di sini untuk memastikan 1 user = 1 majelis
            $table->foreignId('user_id')
                ->unique('uq_event_panel_clerks_user')
                ->constrained('users')
                ->cascadeOnDelete();

            // Status aktif penugasan
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_judge_panel_clerks');
    }
};