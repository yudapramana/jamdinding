<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_competition_branches', function (Blueprint $table) {
            $table->id();

            // kode golongan, contoh: ANAK, REMAJA, DEWASA
            $table->string('code')->unique();

            $table->unsignedInteger('master_competition_group_id');
            $table->unsignedInteger('master_competition_category_id');

            // Tipe
            $table->enum('type', ['Putra', 'Putri']);

            // Format
            $table->enum('format', ['individu', 'grup']);

            // nama digabung: Group, Category, Type
            $table->string('name');

            // Maksimal Umur
            $table->integer('max_age')->default(0);

            $table->unsignedInteger('order_number')->default(1);

            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('levels');
    }
};
