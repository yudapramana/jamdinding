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
        Schema::create('participants', function (Blueprint $table) {
            $table->id();

            // Identitas dasar (bank data)
            $table->string('nik', 30)->unique();
            $table->string('full_name', 150);
            $table->string('phone_number', 30)->nullable();

            // TTL
            $table->string('place_of_birth', 100);
            $table->date('date_of_birth');
            $table->enum('gender', ['MALE', 'FEMALE']);

            // Domisili
            $table->foreignId('province_id')->constrained('provinces');
            $table->foreignId('regency_id')->constrained('regencies');
            $table->foreignId('district_id')->constrained('districts');
            $table->foreignId('village_id')->nullable()->constrained('villages');

            $table->text('address')->nullable();

            // Pendidikan
            $table->enum('education', [
                'SD','SMP','SMA','D1','D2','D3','D4','S1','S2','S3'
            ])->default('SMA');

            // Rekening
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_account_name', 150)->nullable();
            $table->enum('bank_name', [
                'BRI','BNI','MANDIRI','BTN','BSI','BRI SYARIAH','BNI SYARIAH','MANDIRI SYARIAH',
                'BCA','CIMB NIAGA','PERMATA','PANIN','OCBC NISP',
                'DANAMON','MEGA','SINARMAS','BUKOPIN','MAYBANK','BTPN','J TRUST BANK',
                'BANK DKI','BANK BJB','BANK BJB SYARIAH','BANK JATENG','BANK JATIM',
                'BANK SUMUT','BANK NAGARI','BANK RIAU KEPRI','BANK SUMSEL BABEL',
                'BANK LAMPUNG','BANK KALSEL','BANK KALBAR','BANK KALTIMTARA',
                'BANK SULSEL BAR','BANK SULTRA','BANK SULUTGO','BANK NTB SYARIAH',
                'BANK NTT','BANK PAPUA','BANK MALUKU MALUT'
            ])->nullable();

            // Upload File (bank data)
            $table->string('photo_url')->nullable();
            $table->string('id_card_url')->nullable();
            $table->string('family_card_url')->nullable();
            $table->string('bank_book_url')->nullable();
            $table->string('certificate_url')->nullable();
            $table->string('other_url')->nullable();

            // Tanggal terbit KK/KTP
            $table->date('tanggal_terbit_ktp')->nullable();
            $table->date('tanggal_terbit_kk')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participants');

    }
};
