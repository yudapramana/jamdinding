<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_competition_categories', function (Blueprint $table) {
            $table->id();

            // kode kategori, contoh: PUTRA, PUTRI, REGULER
            $table->string('code')->unique();

            // nama kategori, contoh: Putra, Putri, Reguler
            $table->string('name');

            $table->unsignedInteger('order_number')->default(1);

            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
