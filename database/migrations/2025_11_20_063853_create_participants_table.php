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
        Schema::create('participants', function (Blueprint $table) {
            $table->id();

            // Relasi ke event dan master lomba
            $table->foreignId('event_id')->constrained('events');
            $table->foreignId('event_competition_branch_id')->constrained('event_competition_branches');

            // Identitas dasar
            $table->string('nik', 30)->index();
            $table->string('full_name', 150);
            $table->string('phone_number', 30)->nullable();

            // TTL + umur
            $table->string('place_of_birth', 100);
            $table->date('date_of_birth');
            $table->enum('gender', ['MALE', 'FEMALE']);
            $table->integer('age_year');
            $table->integer('age_month');
            $table->integer('age_day');

            // Wilayah
            $table->foreignId('province_id')->constrained('provinces');
            $table->foreignId('regency_id')->constrained('regencies');
            $table->foreignId('district_id')->constrained('districts');
            $table->foreignId('village_id')->nullable()->constrained('villages');

            // Alamat & pendidikan
            $table->text('address');
            $table->enum('education', [
                'SD', 'SMP', 'SMA', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'
            ])->default('SMA');

            // Data rekening
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_account_name', 150)->nullable();
            $table->enum('bank_name', [
                // BANK BUMN
                'BRI', 'BNI', 'MANDIRI', 'BTN',

                // BANK SYARIAH
                'BSI', 'BRI SYARIAH', 'BNI SYARIAH', 'MANDIRI SYARIAH',

                // BANK SWASTA NASIONAL
                'BCA', 'CIMB NIAGA', 'PERMATA', 'PANIN', 'OCBC NISP',
                'DANAMON', 'MEGA', 'SINARMAS', 'BUKOPIN', 'MAYBANK', 'BTPN', 'J TRUST BANK',

                // BANK PEMBANGUNAN DAERAH (BPD)
                'BANK DKI', 'BANK BJB', 'BANK BJB SYARIAH', 'BANK JATENG', 'BANK JATIM',
                'BANK SUMUT', 'BANK NAGARI', 'BANK RIAU KEPRI', 'BANK SUMSEL BABEL',
                'BANK LAMPUNG', 'BANK KALSEL', 'BANK KALBAR', 'BANK KALTIMTARA',
                'BANK SULSEL BAR', 'BANK SULTRA', 'BANK SULUTGO', 'BANK NTB SYARIAH',
                'BANK NTT', 'BANK PAPUA', 'BANK MALUKU MALUT',
            ])
            ->nullable();

            // Upload File URL
            $table->string('photo_url')->nullable();
            $table->string('id_card_url')->nullable();
            $table->string('family_card_url')->nullable();
            $table->string('bank_book_url')->nullable();
            $table->string('certificate_url')->nullable();
            $table->string('other_url')->nullable();

            // Tanggal terbit dokumen
            $table->date('tanggal_terbit_ktp')->nullable();
            $table->date('tanggal_terbit_kk')->nullable();

            // ======================
            // STATUS PENDAFTARAN
            // ======================
            $table->enum('status_pendaftaran', [
                'bankdata',     // default: masih di Bank Data
                'proses',       // menunggu diverifikasi
                'diterima',     // final diterima
                'perbaiki',     // diminta revisi
                'mundur',       // mengundurkan diri
                'tolak',        // ditolak
            ])->default('bankdata');

            $table->text('registration_notes')->nullable();

            $table->foreignId('moved_by')     // siapa yang memindahkan ke PROSES
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('verified_by')  // siapa verifikatornya
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('verified_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['event_id', 'nik']);
        });



    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participants');

    }
};
