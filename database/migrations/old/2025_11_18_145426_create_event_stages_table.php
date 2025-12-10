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
        Schema::create('event_stages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('stage_id')->nullable()->constrained('stages')->onDelete('set null');

            $table->unsignedInteger('order_number')->default(1);

            // nama tahapan bisa dioverride per event
            $table->string('name');

            // tanggal pelaksanaan
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // keterangan tambahan
            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_stages');
    }
};
