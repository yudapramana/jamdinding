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
        // 1. Tambahkan round_id ke event_maqras
        Schema::table('event_maqras', function (Blueprint $table) {
            $table->foreignId('round_id')
                  ->after('event_group_id')
                  ->constrained('rounds')
                  ->cascadeOnDelete();
        });

        // 2. Tambahkan round_id ke event_question_packages
        Schema::table('event_question_packages', function (Blueprint $table) {
            $table->foreignId('round_id')
                  ->after('event_group_id')
                  ->constrained('rounds')
                  ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Hapus relasi dan kolom dari event_question_packages
        Schema::table('event_question_packages', function (Blueprint $table) {
            $table->dropForeign(['round_id']);
            $table->dropColumn('round_id');
        });

        // 2. Hapus relasi dan kolom dari event_maqras
        Schema::table('event_maqras', function (Blueprint $table) {
            $table->dropForeign(['round_id']);
            $table->dropColumn('round_id');
        });
    }
};