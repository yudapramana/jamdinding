<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_competition_branches', function (Blueprint $table) {
            $table->id();

            // Relasi ke event
            $table->foreignId('event_id')
                ->constrained()
                ->onDelete('cascade');

            // Relasi opsional untuk mengetahui data berasal dari master branch mana
            $table->unsignedBigInteger('master_competition_branch_id')->nullable();

            // kode unik cabang versi event
            $table->string('code');

            // relasi ke group dan category versi master (opsional)
            $table->unsignedInteger('master_competition_group_id')->nullable();
            $table->unsignedInteger('master_competition_category_id')->nullable();

            // Tipe
            $table->enum('type', ['Putra', 'Putri']);

            // Format
            $table->enum('format', ['individu', 'grup']);

            // Nama gabungan (group + category + type)
            $table->string('name');

            // Maksimal Umur
            $table->integer('max_age')->default(0);

            $table->unsignedInteger('order_number')->default(1);

            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // INDEX (optional tapi sangat berguna)
            $table->index(['event_id']);
            $table->index(['master_competition_branch_id']);
            $table->unique(['event_id', 'code']); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_competition_branches');
    }
};
