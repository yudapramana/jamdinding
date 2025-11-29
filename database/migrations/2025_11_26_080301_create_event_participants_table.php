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
        Schema::create('event_participants', function (Blueprint $table) {
            $table->id();

            // Relasi utama
            $table->foreignId('event_id')->constrained('events');
            $table->foreignId('participant_id')->constrained('participants');

            // Cabang lomba
            $table->foreignId('event_competition_branch_id')
                ->nullable()
                ->constrained('event_competition_branches')
                ->nullOnDelete();

            // Umur dihitung per event
            $table->integer('age_year');
            $table->integer('age_month');
            $table->integer('age_day');

            // Status pendaftaran khusus event ini
            $table->enum('status_pendaftaran', [
                'bankdata',
                'proses',
                'diterima',
                'perbaiki',
                'mundur',
                'tolak',
            ])->default('bankdata');

            $table->text('registration_notes')->nullable();

            $table->foreignId('moved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('verified_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // 1 peserta hanya boleh 1x daftar per event
            $table->unique(['event_id', 'participant_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_participants');
    }
};
