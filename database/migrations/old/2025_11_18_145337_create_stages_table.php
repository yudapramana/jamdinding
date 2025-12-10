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
        Schema::create('stages', function (Blueprint $table) {
            $table->id();

            // nama tahapan (Persiapan, Pendaftaran, Verifikasi, dsb)
            $table->string('name');

            // hari tahapan (Berapa lama dilaksanakan tahapan))
            $table->integer('days');

            // urutan default tahapan (1–10)
            $table->unsignedInteger('order_number')->default(1);

            // deskripsi tambahan jika diperlukan
            $table->text('description')->nullable();

            // status aktif (supaya bisa nonaktifkan tahapan tertentu)
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stages');
    }

    
    
};
