<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_scoresheet_revert_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_competition_id')->index();
            $table->unsignedBigInteger('event_participant_id')->index();
            $table->unsignedBigInteger('user_id')->index();          // superadmin / admin_event pelaku
            $table->string('document_type', 30);                      // berita_acara | status_quo
            $table->string('document_number', 100);
            $table->date('document_date');
            $table->text('reason');
            $table->string('previous_status', 50);                    // submitted / locked
            $table->unsignedInteger('affected_sheets')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_scoresheet_revert_logs');
    }
};