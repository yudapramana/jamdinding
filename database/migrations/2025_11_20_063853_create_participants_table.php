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
            $table->foreignId('event_competition_branch_id')->constrained('event_competition_branches'); // cabang lomba

            // Identitas dasar
            $table->string('nik', 30)->index();       // NIK / Passport number
            $table->string('full_name', 150);         // participant name
            $table->string('phone_number', 30)->nullable();

            // Tempat & tanggal lahir
            $table->string('place_of_birth', 100);
            $table->date('date_of_birth');
            $table->enum('gender', ['MALE', 'FEMALE']);

            // Wilayah domisili
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
            $table->string('bank_name', 50)->nullable();

            // Uploaded Files (store path/url)
            $table->string('photo_url')->nullable();
            $table->string('id_card_url')->nullable();       // KTP
            $table->string('family_card_url')->nullable();   // KK
            $table->string('bank_book_url')->nullable();     // Buku Tabungan
            $table->string('certificate_url')->nullable();   // Piagam Penghargaan
            $table->string('other_url')->nullable();         // Berkas Lain

            // Tanggal terbit dokumen
            $table->date('tanggal_terbit_ktp')->nullable();
            $table->date('tanggal_terbit_kk')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // satu NIK hanya sekali per event
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
