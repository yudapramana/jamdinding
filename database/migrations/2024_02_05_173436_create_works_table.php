<?php

use App\Enums\UnitType;
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
        Schema::create('works', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();

            $table->text('work_name');
            $table->text('work_detail')->nullable();

            $table->integer('volume');
            $table->tinyInteger('unit')->default(UnitType::KEGIATAN->value);
            $table->string('evidence')->default('');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('works');
    }
};
