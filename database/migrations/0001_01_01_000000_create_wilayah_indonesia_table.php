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
        // 1. PROVINCES
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();                 // big integer auto increment
            $table->string('name');       // nama provinsi
            $table->timestamps();
        });

        // 2. REGENCIES (KABUPATEN / KOTA)
        Schema::create('regencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')
                ->constrained('provinces')
                ->onDelete('cascade');    // jika provinsi dihapus, kab/kota ikut terhapus
            $table->string('name');       // nama kabupaten/kota
            $table->timestamps();
        });

        // 3. DISTRICTS (KECAMATAN)
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regency_id')
                ->constrained('regencies')
                ->onDelete('cascade');
            $table->string('name');       // nama kecamatan
            $table->timestamps();
        });

        // 4. VILLAGES (DESA / KELURAHAN)
        Schema::create('villages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')
                ->constrained('districts')
                ->onDelete('cascade');
            $table->string('name');       // nama desa/kelurahan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // DROP dengan urutan terbalik untuk jaga FK
        Schema::dropIfExists('villages');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('regencies');
        Schema::dropIfExists('provinces');
    }
};
