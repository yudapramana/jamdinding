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
        Schema::create('participant_verifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('participant_id')->constrained('participants');
            $table->foreignId('event_id')
                ->nullable()
                ->constrained('events')
                ->nullOnDelete();
            $table->foreignId('event_participant_id')
                ->nullable()
                ->constrained('event_participants')
                ->nullOnDelete();
            $table->foreignId('verified_by')->constrained('users');

            // status verifikasi sesi ini
            $table->enum('status', ['verified', 'rejected'])->default('verified');

            // ================
            // DOKUMEN DICEK?
            // ================
            // mapping ke kolom file di participants:
            // photo_url, id_card_url, family_card_url, bank_book_url, certificate_url, other_url
            $table->boolean('checked_photo')->default(false);          // photo_url
            $table->boolean('checked_id_card')->default(false);        // id_card_url (KTP)
            $table->boolean('checked_family_card')->default(false);    // family_card_url (KK)
            $table->boolean('checked_bank_book')->default(false);      // bank_book_url
            $table->boolean('checked_certificate')->default(false);    // certificate_url
            $table->boolean('checked_other')->default(false);          // other_url

            // =========================
            // KELOMPOK DATA DICEK?
            // =========================
            // sesuai struktur participants:
            // nik, full_name, phone_number, place_of_birth, date_of_birth, gender,
            // province_id, regency_id, district_id, village_id, address,
            // education,
            // bank_account_number, bank_account_name, bank_name,
            // tanggal_terbit_ktp, tanggal_terbit_kk
            $table->boolean('checked_identity')->default(false);       // nik, full_name, place_of_birth, date_of_birth, gender
            $table->boolean('checked_contact')->default(false);        // phone_number
            $table->boolean('checked_domicile')->default(false);       // province_id, regency_id, district_id, village_id, address
            $table->boolean('checked_education')->default(false);      // education
            $table->boolean('checked_bank_account')->default(false);   // bank_account_*, bank_name
            $table->boolean('checked_document_dates')->default(false); // tanggal_terbit_ktp, tanggal_terbit_kk

            // ======================================
            // DETAIL HASIL CEK PER FIELD (FLEKSIBEL)
            // ======================================
            $table->json('field_matches')->nullable();
            /**
             * contoh isi field_matches (diselaraskan dengan kolom participants):
             * {
             *   "identity": {
             *     "nik": true,
             *     "full_name": true,
             *     "place_of_birth": true,
             *     "date_of_birth": true,
             *     "gender": true
             *   },
             *   "contact": {
             *     "phone_number": true
             *   },
             *   "domicile": {
             *     "province_id": true,
             *     "regency_id": true,
             *     "district_id": true,
             *     "village_id": true,
             *     "address": true
             *   },
             *   "education": {
             *     "education": true
             *   },
             *   "bank_account": {
             *     "bank_account_number": true,
             *     "bank_account_name": true,
             *     "bank_name": true
             *   },
             *   "document_dates": {
             *     "tanggal_terbit_ktp": true,
             *     "tanggal_terbit_kk": false
             *   },
             *   "documents": {
             *     "photo_url": true,
             *     "id_card_url": true,
             *     "family_card_url": true,
             *     "bank_book_url": true,
             *     "certificate_url": false,
             *     "other_url": false
             *   }
             * }
             */

            // catatan verifikator untuk sesi ini
            $table->text('notes')->nullable();

            $table->timestamps();
            
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participant_verifications');
    }
};
