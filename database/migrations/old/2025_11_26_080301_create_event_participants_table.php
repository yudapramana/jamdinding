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

            $table->enum('status_daftar_ulang', [
                'belum',          // peserta belum hadir daftar ulang
                'terverifikasi',  // peserta lolos tahap daftar ulang
                'gagal',          // peserta tidak lolos daftar ulang
            ])->default('belum');

            // Metadata tambahan
            $table->timestamp('daftar_ulang_at')->nullable();   // kapan peserta diverifikasi ulang
            $table->foreignId('daftar_ulang_by')                // petugas yang melakukan verifikasi ulang
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('daftar_ulang_notes')->nullable();     // catatan kekurangan / alasan gagal

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
