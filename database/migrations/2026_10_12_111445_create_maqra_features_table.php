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
        /*
        |--------------------------------------------------------------------------
        | 1. UPDATE TABEL EVENT GROUPS
        |--------------------------------------------------------------------------
        | Menambahkan flag dan pengaturan maqra pada master data golongan di event.
        */
        Schema::table('event_groups', function (Blueprint $table) {
            // Flag untuk menentukan apakah golongan ini menggunakan sistem cabut maqra/soal
            $table->boolean('has_maqra_drawing')->default(false)->after('use_custom_judges');
            
            // Waktu persiapan dalam menit (contoh: 1440 untuk H-1, 30 untuk tartil, 0 untuk tahfiz di panggung)
            $table->integer('preparation_time_minutes')->default(0)->after('has_maqra_drawing');
            
            // Penanda jika golongan ini butuh dua kali pengundian (contoh: 1 Juz + Tilawah)
            $table->boolean('is_double_drawing')->default(false)->after('preparation_time_minutes');
        });

        /*
        |--------------------------------------------------------------------------
        | 2. TABEL EVENT MAQRAS (Lokasi Menu: Event > Bank Maqra)
        |--------------------------------------------------------------------------
        */
        Schema::create('event_maqras', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('event_group_id')->constrained('event_groups')->cascadeOnDelete();
            $table->integer('surah_number'); 
            $table->string('surah_name');
            $table->integer('start_ayah');
            $table->integer('end_ayah');
            $table->integer('juz')->nullable();
            $table->integer('page')->nullable();
            
            $table->boolean('is_available')->default(true);
            
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | 3. TABEL EVENT QUESTION PACKAGES (Lokasi Menu: Event > Bank Soal)
        |--------------------------------------------------------------------------
        */
        Schema::create('event_question_packages', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('event_group_id')->constrained('event_groups')->cascadeOnDelete();
            
            $table->string('package_number', 50); // Contoh: "Paket A", "Paket 01"
            
            // Disimpan sebagai JSON jika 1 paket berisi beberapa sub-soal
            $table->json('question_content'); 
            
            $table->boolean('is_available')->default(true);
            
            $table->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | 4. TABEL EVENT MAQRA DRAWINGS (Lokasi Menu: Peserta > Daftar Ulang & Penilaian)
        |--------------------------------------------------------------------------
        | Mengikat data ke rounds (Babak) dan peserta, bukan ke jadwal spesifik, 
        | agar bisa dicabut saat Daftar Ulang.
        */
        Schema::create('event_maqra_drawings', function (Blueprint $table) {
            $table->id();
            
            // Mengikat pencabutan ke babak (Penyisihan / Final) agar valid saat daftar ulang
            $table->foreignId('round_id')->constrained('rounds')->cascadeOnDelete();
            
            // Relasi ke peserta individu atau regu (untuk Fahmil)
            $table->foreignId('event_participant_id')->nullable()->constrained('event_participants')->cascadeOnDelete();
            $table->foreignId('event_team_id')->nullable()->constrained('event_teams')->cascadeOnDelete();
            
            $table->enum('exam_type', ['tilawah', 'tartil', 'tahfiz', 'tafsir', 'fahmil']);
            
            // Maqra atau Paket Soal (salah satu terisi, atau dua-duanya untuk sistem ganda)
            $table->foreignId('event_maqra_id')->nullable()->constrained('event_maqras')->nullOnDelete();
            $table->foreignId('event_question_package_id')->nullable()->constrained('event_question_packages')->nullOnDelete();
            
            // Waktu Pencabutan & Estimasi Tampil (Waktu estimasi bisa diupdate saat jadwal dibuat)
            $table->timestamp('drawn_at')->useCurrent();
            $table->timestamp('estimated_performance_time')->nullable();
            
            // Petugas/Panitera yang mengawasi pencabutan
            $table->foreignId('drawn_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->enum('status', ['drawn', 'performing', 'finished', 'cancelled'])->default('drawn');
            
            $table->timestamps();
            
            // Memastikan 1 peserta/tim hanya mencabut 1 kali untuk tipe ujian yang sama per Babak
            $table->unique(
                ['round_id', 'event_participant_id', 'exam_type'], 
                'uq_drawings_round_participant_exam'
            );
            $table->unique(
                ['round_id', 'event_team_id', 'exam_type'], 
                'uq_drawings_round_team_exam'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_maqra_drawings');
        Schema::dropIfExists('event_question_packages');
        Schema::dropIfExists('event_maqras');
        
        Schema::table('event_groups', function (Blueprint $table) {
            $table->dropColumn(['has_maqra_drawing', 'preparation_time_minutes', 'is_double_drawing']);
        });
    }
};