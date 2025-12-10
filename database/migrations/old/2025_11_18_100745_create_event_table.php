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
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            $table->string('event_key', 100)->unique();
            $table->string('nama_event');
            $table->string('nama_aplikasi');
            $table->string('lokasi_event')->nullable();
            $table->string('tagline')->nullable();
            $table->string('token_hasil_penilaian')->nullable();

            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->date('tanggal_batas_umur')->nullable();

            // Logo event
            $table->string('logo_event')->nullable();
            $table->string('logo_sponsor_1')->nullable();
            $table->string('logo_sponsor_2')->nullable();
            $table->string('logo_sponsor_3')->nullable();

            $table->enum('tingkat_event', [
                'nasional','provinsi','kabupaten_kota','kecamatan'
            ])->default('kabupaten_kota');

            $table->foreignId('province_id')->nullable()->constrained('provinces');
            $table->foreignId('regency_id')->nullable()->constrained('regencies');
            $table->foreignId('district_id')->nullable()->constrained('districts');

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });


    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
