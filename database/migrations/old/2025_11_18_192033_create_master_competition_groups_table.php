<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_competition_groups', function (Blueprint $table) {
            $table->id();

            // kode unik cabang, contoh: TILAWAH, TAHFIZH_1JUZ
            $table->string('code')->unique();

            // nama cabang, contoh: Tilawah Al-Qur'an
            $table->string('name');

            // urutan tampil
            $table->unsignedInteger('order_number')->default(1);

            // deskripsi opsional
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
