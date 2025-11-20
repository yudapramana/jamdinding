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

            // key unik untuk disimpan di cookie / session / localStorage
            $table->string('event_key', 100)->unique();

            // data utama event (dari form)
            $table->string('nama_event');
            $table->string('nama_aplikasi');
            $table->string('lokasi_event')->nullable();
            $table->string('tagline')->nullable();
            $table->string('token_hasil_penilaian')->nullable();

            // tanggal pelaksanaan (range)
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');

            // batas umur peserta
            $table->date('tanggal_batas_umur')->nullable();

            // path/logo (simpan nama file / url)
            $table->string('logo_event')->nullable();
            $table->string('logo_sponsor_1')->nullable();
            $table->string('logo_sponsor_2')->nullable();
            $table->string('logo_sponsor_3')->nullable();

            // tingkatan event MTQ
            $table->enum('tingkat_event', [
                'nasional',
                'provinsi',
                'kabupaten_kota',
                'kecamatan',
            ])->default('kabupaten_kota');

            // 🔹 Referensi wilayah (opsional, tergantung tingkat_event)
            // sesuaikan nama tabel kalau beda: 'provinces', 'regencies', 'districts'
            $table->foreignId('province_id')
                ->nullable()
                ->constrained('provinces');

            $table->foreignId('regency_id')
                ->nullable()
                ->constrained('regencies');

            $table->foreignId('district_id')
                ->nullable()
                ->constrained('districts');

            // status event (bisa ada banyak event; beberapa aktif sekaligus)
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
