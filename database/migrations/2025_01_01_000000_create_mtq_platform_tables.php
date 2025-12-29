<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        /**
         * MASTER DASAR
         */

        // 01. branches
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->nullable();
            $table->string('name'); // Hafalan Al Qur'an, Fahm Al Qur'an, dll
            $table->boolean('is_team')->default(false);
            $table->unsignedInteger('order_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 02. groups
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->nullable();
            $table->string('name'); // Dewasa, Remaja, 10 Juz, 20 Juz, 30 Juz
            $table->unsignedInteger('order_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 03. categories
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->nullable();
            $table->string('name'); // Putra, Putri
            $table->unsignedInteger('order_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 04. list_fields
        Schema::create('list_fields', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->nullable();
            $table->string('name'); // Lagu, Suara, Tajwid, Adab, Fashahah, dll
            $table->text('description')->nullable();
            $table->unsignedInteger('order_number')->nullable();
            $table->timestamps();
        });

        // 05. events (sinkron dengan schema lama: nama_event, lokasi_event)
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            $table->string('app_name');
            $table->string('event_key', 100)->unique();
            $table->string('event_name');
            $table->string('event_year');
            $table->string('event_location')->nullable();
            $table->string('event_tagline')->nullable();
            $table->string('assessment_token')->nullable();

            $table->date('start_date');
            $table->date('end_date');
            $table->date('age_limit_date')->nullable();

            // Logo event
            $table->string('logo_event')->nullable();
            $table->string('logo_sponsor_1')->nullable();
            $table->string('logo_sponsor_2')->nullable();
            $table->string('logo_sponsor_3')->nullable();

            $table->enum('event_level', [
                'national','province','regency','district'
            ])->default('regency');

            $table->foreignId('province_id')->nullable()->constrained('provinces');
            $table->foreignId('regency_id')->nullable()->constrained('regencies');
            $table->foreignId('district_id')->nullable()->constrained('districts');

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        // 06. stages
        Schema::create('stages', function (Blueprint $table) {
            $table->id();
            // nama tahapan (Persiapan, Pendaftaran, Verifikasi, dsb)
            $table->string('name');
            // hari tahapan (Berapa lama dilaksanakan tahapan))
            $table->integer('days');
            // urutan default tahapan (1–10)
            $table->unsignedInteger('order_number')->default(1);
            // deskripsi tambahan jika diperlukan
            $table->text('description')->nullable();
            // status aktif (supaya bisa nonaktifkan tahapan tertentu)
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        // 07. event_stages
        Schema::create('event_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained('stages')->cascadeOnDelete();
            $table->unsignedInteger('order_number')->nullable();
            // nama tahapan bisa dioverride per event
            $table->string('name');
            // tanggal pelaksanaan
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            // keterangan tambahan
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // unique: satu stage hanya sekali per event
            $table->unique(['event_id', 'stage_id'], 'uq_event_stages_event_stage');
        });

        // 08. master_branches
        Schema::create('master_branches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('branch_name'); // denormalisasi
            $table->string('full_name');   // "Hafalan Al Qur'an"

            $table->unsignedInteger('order_number')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        // 09. master_groups
        Schema::create('master_groups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();

            $table->string('branch_name');
            $table->string('group_name');
            $table->string('full_name'); // "Hafalan Al Qur'an - 10 Juz"

            $table->integer('max_age')->default(0);

            $table->boolean('is_team')->default(false);
            $table->unsignedInteger('order_number')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // kombinasi master unik per branch+group
            $table->unique(['branch_id', 'group_id'], 'uq_master_groups_branch_group');
        });

        // 10. master_categories
        Schema::create('master_categories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();

            $table->string('branch_name');
            $table->string('group_name');
            $table->string('category_name');
            $table->string('full_name'); // "Hafalan - 10 Juz - Putra"

            $table->unsignedInteger('order_number')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                ['branch_id', 'group_id', 'category_id'],
                'uq_master_categories_branch_group_cat'
            );
        });

        // 11. master_field_components
        Schema::create('master_field_components', function (Blueprint $table) {
            $table->id();

            $table->foreignId('master_group_id')->constrained('master_groups')->cascadeOnDelete();
            $table->foreignId('field_id')->constrained('list_fields')->cascadeOnDelete();

            $table->string('master_group_name');
            $table->string('field_name');

            // pattern dari master_branch_field_components lama
            $table->unsignedInteger('default_weight')->nullable();     // bobot %
            $table->unsignedInteger('default_max_score')->nullable();  // max nilai per hakim
            $table->unsignedInteger('default_order')->nullable();      // urutan tampil
            $table->boolean('is_default')->default(false);             // digunakan sebagai template default

            $table->timestamps();

            $table->unique(
                ['master_group_id', 'field_id'],
                'uq_master_field_components_group_field'
            );
        });

        // 12. rounds
        Schema::create('rounds', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Babak Penyisihan, Semifinal, Final
            $table->unsignedInteger('order_number')->nullable();
            $table->timestamps();
        });

        /**
         * MASTER EVENT PER EVENT
         */

        // 13. event_branches
        Schema::create('event_branches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->string('branch_name');
            $table->string('full_name');

            $table->enum('status', ['inactive', 'active'])->default('active');
            $table->unsignedInteger('order_number')->nullable();

            $table->timestamps();

            $table->unique(['event_id', 'branch_id'], 'uq_event_branches_event_branch');
        });

        // 14. event_groups
        Schema::create('event_groups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();

            $table->string('branch_name');
            $table->string('group_name');
            $table->string('full_name');

            $table->integer('max_age')->default(0);

            $table->enum('status', ['inactive', 'active'])->default('active');
            $table->boolean('is_team')->default(false);
            $table->boolean('use_custom_judges')->default(false);
            $table->unsignedInteger('order_number')->nullable();

            $table->timestamps();

            $table->unique(
                ['event_id', 'branch_id', 'group_id'],
                'uq_event_groups_event_branch_group'
            );
        });

        // 15. event_categories
        Schema::create('event_categories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();

            $table->string('branch_name');
            $table->string('group_name');
            $table->string('category_name');
            $table->string('full_name');

            $table->enum('status', ['inactive', 'active'])->default('active');
            $table->unsignedInteger('order_number')->nullable();

            $table->timestamps();

            $table->unique(
                ['event_id', 'branch_id', 'group_id', 'category_id'],
                'uq_event_categories_event_bgc'
            );
        });

        // 16. event_field_components
        Schema::create('event_field_components', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_group_id')->constrained('event_groups')->cascadeOnDelete();
            $table->foreignId('field_id')->constrained('list_fields')->cascadeOnDelete();

            $table->string('event_group_name');
            $table->string('field_name');

            $table->unsignedInteger('weight')->nullable();    // bobot %
            $table->unsignedInteger('max_score')->nullable(); // max skor
            $table->unsignedInteger('order_number')->nullable();

            $table->timestamps();

            $table->unique(
                ['event_group_id', 'field_id'],
                'uq_event_field_components_group_field'
            );
        });



        /**
         * PESERTA
         */

        // 17. participants (bank data kafilah, pakai referensi wilayah)
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

            // Pendidikan
            $table->enum('education', [
                'SD','SMP','SMA','D1','D2','D3','D4','S1','S2','S3'
            ])->default('SMA');

            // Alamat Lengkap
            $table->text('address')->nullable();

            // referensi wilayah
            $table->foreignId('province_id')->nullable()->constrained('provinces')->nullOnDelete();
            $table->foreignId('regency_id')->nullable()->constrained('regencies')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('village_id')->nullable()->constrained('villages')->nullOnDelete();

            // fallback teks manual (jaga2 mismatch data wilayah)
            $table->string('province_name')->nullable();
            $table->string('regency_name')->nullable();
            $table->string('district_name')->nullable();
            $table->string('village_name')->nullable();

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

            // dokumen peserta
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

            $table->index(['province_id', 'regency_id']);
        });

        // 18.a
        Schema::create('event_teams', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('event_branch_id')->constrained('event_branches')->cascadeOnDelete();
            $table->foreignId('event_group_id')->constrained('event_groups')->cascadeOnDelete();
            $table->foreignId('event_category_id')->constrained('event_categories')->cascadeOnDelete();

            // Identitas tim
            $table->string('team_name')->nullable();
            $table->string('contingent')->nullable();

            // Nomor peserta tim
            $table->string('branch_code', 20)->nullable();     // FH-G
            $table->unsignedInteger('branch_sequence')->nullable();
            $table->string('participant_number', 30)->nullable(); // FH-G.01


            $table->enum('reregistration_status', ['not_yet','verified','rejected'])->default('not_yet');
            $table->text('reregistration_notes')->nullable();
            $table->timestamp('reregistered_at')->nullable();
            $table->foreignId('reregistered_by')->nullable()->constrained('users')->nullOnDelete();



            $table->timestamps();

            $table->unique(
                ['event_id','event_group_id','branch_sequence'],
                'uq_event_teams_event_group_seq'
            );
        });

        // 18.b event_participants
        Schema::create('event_participants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('participants')->cascadeOnDelete();

            // Cabang Lomba
            $table->foreignId('event_branch_id')->constrained('event_branches')->cascadeOnDelete();
            $table->foreignId('event_group_id')->constrained('event_groups')->cascadeOnDelete();
            $table->foreignId('event_category_id')->constrained('event_categories')->cascadeOnDelete();

             $table->foreignId('event_team_id')
            ->nullable()
            ->constrained('event_teams')
            ->nullOnDelete();

            // Umur dihitung per event
            $table->integer('age_year');
            $table->integer('age_month');
            $table->integer('age_day');

            $table->string('contingent')->nullable(); // kab/kota/instansi

            // Pendaftaran 
            $table->enum('registration_status', [
                            'bank_data',        // data awal dari bankdata
                            'process',          // sedang diproses
                            'verified',         // sudah diverifikasi
                            'need_revision',    // perlu perbaikan
                            'rejected',         // ditolak
                            'disqualified'      // didiskualifikasi
                        ])->default('bank_data');
            $table->text('registration_notes')->nullable();
            $table->timestamp('register_at')->nullable();

            $table->foreignId('moved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('verified_at')->nullable();

            // Daftar Ulang
            $table->enum('reregistration_status', [
                'not_yet',          // peserta belum hadir daftar ulang
                'verified',         // peserta lolos tahap daftar ulang
                'rejected',           // peserta tidak lolos daftar ulang
            ])->default('not_yet');

            // Metadata tambahan
            $table->timestamp('reregistered_at')->nullable(); // kapan peserta diverifikasi ulang

            $table->foreignId('reregistered_by')              // petugas yang melakukan verifikasi ulang
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('reregistration_notes')->nullable(); // catatan kekurangan / alasan gagal

            // Nomor Peserta
            $table->string('branch_code', 20)->nullable()->comment('Snapshot kode cabang, contoh FH.01');
            $table->unsignedTinyInteger('branch_sequence')->nullable()->comment('Urutan peserta per cabang (1-99)');
            $table->string('participant_number', 30)->nullable()->comment('Nomor peserta final, contoh FH.01.11');

           


            $table->timestamps();
            $table->softDeletes();

            // satu peserta hanya boleh 1 entry unique per event (kalau mau dibatasi)
            $table->unique(['event_id', 'participant_id'], 'uq_event_participants_event_peserta');
            // Dalam 1 event, 1 cabang, Tidak boleh ada urutan sama
            $table->unique([
                'event_id',
                'event_branch_id',
                'event_group_id',
                'branch_sequence'
            ], 'uq_event_branch_group_sequence');
        });

        


        // 19. participant_verifications
        Schema::create('participant_verifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('participant_id')->constrained('participants')->cascadeOnDelete();

            $table->foreignId('event_id')
                ->nullable()
                ->constrained('events')
                ->nullOnDelete();

            $table->foreignId('event_participant_id')
                ->nullable()
                ->constrained('event_participants')
                ->nullOnDelete();

            $table->foreignId('verified_by')->constrained('users');

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

            $table->text('notes')->nullable();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();
        });

        

        /**
         * KOMPETISI & PENILAIAN
         */

        // 20. event_competitions
        Schema::create('event_competitions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('event_group_id')->constrained('event_groups')->cascadeOnDelete();
            $table->foreignId('round_id')->constrained('rounds')->cascadeOnDelete();

            $table->string('full_name'); // "MTQ 2027 - Tilawah Dewasa Putra - Final"

            $table->enum('status', ['draft', 'ongoing', 'finished', 'cancelled'])->default('draft');
            $table->boolean('is_team')->default(false);

            $table->timestamp('scheduled_at')->nullable();
            $table->string('venue')->nullable();

            $table->timestamps();

            // kombinasi unik per event+group+round
            $table->unique(
                ['event_id', 'event_group_id', 'round_id'],
                'uq_event_competitions_event_group_round'
            );
        });

        // 21. event_scoresheets
        Schema::create('event_scoresheets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_competition_id')->constrained('event_competitions')->cascadeOnDelete();
            $table->foreignId('event_group_id')->constrained('event_groups')->cascadeOnDelete();
            $table->foreignId('event_category_id')->nullable()->constrained('event_categories')->nullOnDelete();

            $table->foreignId('event_participant_id')->constrained('event_participants')->cascadeOnDelete();
            $table->foreignId('event_team_id')
                ->nullable()
                ->constrained('event_teams')
                ->cascadeOnDelete();
            $table->foreignId('judge_id')->constrained('users'); // hakim

            $table->decimal('total_score', 8, 2)->default(0);
            $table->unsignedInteger('rank_in_round')->nullable();

            $table->enum('status', ['draft', 'submitted', 'locked'])->default('draft');

            $table->timestamps();

            // satu hakim menilai satu peserta satu kali per competition
            $table->unique(
                ['event_competition_id', 'event_participant_id', 'judge_id'],
                'uq_scoresheets_competition_participant_judge'
            );

            $table->unique(
                ['event_competition_id','event_team_id','judge_id'], 
                'uq_scoresheets_competition_team_judge'
            );
        });

        // 22. event_score_items
        Schema::create('event_score_items', function (Blueprint $table) {
            $table->id();

            // NOTE: di rancangan tertulis event_score_sheets_id,
            // di sini dipakai event_scoresheet_id (lebih konsisten dgn nama tabel).
            $table->foreignId('event_scoresheet_id')->constrained('event_scoresheets')->cascadeOnDelete();

            // optional: kalau mau link ke komponen bernilai
            $table->foreignId('event_field_component_id')->nullable()->constrained('event_field_components')->nullOnDelete();

            $table->decimal('score', 6, 2)->default(0);
            $table->decimal('max_score', 6, 2)->default(0);
            $table->unsignedInteger('weight')->nullable(); // %
            $table->decimal('weighted_score', 8, 2)->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['event_scoresheet_id']);
        });

        /**
         * HAKIM & MEDALI
         */

        // 22. event_group_judges
        // Schema::create('event_group_judges', function (Blueprint $table) {
        //     $table->id();

        //     $table->foreignId('event_group_id')->constrained('event_groups')->cascadeOnDelete();
        //     $table->foreignId('user_id')->constrained('users');

        //     $table->boolean('is_chief')->default(false); // ketua majelis?

        //     $table->timestamps();

        //     $table->unique(
        //         ['event_group_id', 'user_id'],
        //         'uq_event_group_judges_group_user'
        //     );
        // });

        // 
        // 23. event_branch_judges
        Schema::create('event_branch_judges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_branch_id')->constrained('event_branches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->boolean('is_chief')->default(false);
            $table->timestamps();

            $table->unique(['event_branch_id','user_id'], 'uq_event_branch_judges_branch_user');
        });

        // 24. event_group_judges
        Schema::create('event_group_judges', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_group_id')->constrained('event_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');

            $table->boolean('is_chief')->default(false);

            $table->timestamps();

            $table->unique(['event_group_id', 'user_id'], 'uq_event_group_judges_group_user');
        });


        // 25. Medal Rules
        Schema::create('medal_rules', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('order_number');
            // 1,2,3,4,5,6

            $table->string('medal_code');
            // champion_1, champion_2, champion_3,
            // runner_up_1, runner_up_2, runner_up_3

            $table->string('medal_name');
            // Juara 1, Juara 2, Juara 3,
            // Harapan 1, Harapan 2, Harapan 3

            $table->unsignedInteger('point');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['order_number'], 'uq_medal_rules_order');
            $table->unique(['medal_code'], 'uq_medal_rules_code');
        });

        // 26. Medal Rules
        Schema::create('event_medal_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->unsignedInteger('order_number');
            // 1,2,3,4,5,6

            $table->string('medal_code');
            // champion_1, runner_up_2, dst

            $table->string('medal_name');
            // Juara 1, Harapan 2, dst

            $table->unsignedInteger('point');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                ['event_id', 'order_number'],
                'uq_event_medal_rules_event_order'
            );
        });

        // 27. medal_standings
        Schema::create('medal_standings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_category_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedInteger('order_number');

            $table->foreignId('event_medal_rule_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('medal_rule_id')->nullable()->constrained()->restrictOnDelete();

            // ✅ REFERENSI UTAMA (RELATIONAL)
            $table->string('region_type')->nullable(); // province|regency|district|village
            $table->unsignedBigInteger('region_id')->nullable();

            // ✅ SNAPSHOT UNTUK HISTORY & PDF
            $table->string('region_name')->nullable(); // "Provinsi Sumatera Barat"

            $table->timestamps();

            $table->unique(
                ['event_id','event_group_id','event_category_id','order_number'],
                'uq_medal_standings_event_group_cat_order'
            );

            $table->index(['region_type','region_id']);
        });



        // 28. event_contingents
        Schema::create('event_contingents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            // 🔑 polymorphic region
            $table->string('region_type'); // province | regency | district | village
            $table->unsignedBigInteger('region_id');

            $table->unsignedInteger('total_participant')->default(0);
            $table->unsignedInteger('total_point')->default(0);

            $table->timestamps();

            $table->unique(
                ['event_id', 'region_type', 'region_id'],
                'uq_event_contingents_event_region'
            );

            $table->index(['region_type', 'region_id']);
        });


        // 29. Event Continget Medals
        Schema::create('event_contingent_medals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_contingent_id')
                ->constrained('event_contingents')
                ->cascadeOnDelete();

            $table->unsignedInteger('order_number');

            $table->string('medal_code');
            // champion_1, runner_up_1, dst

            $table->string('medal_name');
            // Juara 1, Harapan 1, dst

            $table->unsignedInteger('medal_count')->default(0);

            $table->timestamps();

            $table->unique(
                ['event_contingent_id', 'order_number'],
                'uq_event_contingent_medals_contingent_order'
            );
        });

        // 30. Event Snapshots
        Schema::create('event_snapshots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type'); 
            // leaderboard | ranking | medal

            $table->json('payload'); // FULL RESULT
            $table->timestamp('published_at');

            $table->timestamps();

            $table->index(['event_id', 'type']);
        });



    }

    public function down(): void
    {
        // urutan kebalikan untuk menghindari FK error
        Schema::dropIfExists('event_contingents');
        Schema::dropIfExists('medal_standings');
        Schema::dropIfExists('event_group_judges');
        Schema::dropIfExists('event_score_items');
        Schema::dropIfExists('event_scoresheets');
        Schema::dropIfExists('event_competitions');
        Schema::dropIfExists('event_field_components');
        Schema::dropIfExists('event_categories');
        Schema::dropIfExists('event_groups');
        Schema::dropIfExists('event_branches');
        Schema::dropIfExists('participant_verifications');
        Schema::dropIfExists('event_participants');
        Schema::dropIfExists('participants');
        Schema::dropIfExists('rounds');
        Schema::dropIfExists('master_field_components');
        Schema::dropIfExists('master_categories');
        Schema::dropIfExists('master_groups');
        Schema::dropIfExists('master_branches');
        Schema::dropIfExists('event_stages');
        Schema::dropIfExists('stages');
        Schema::dropIfExists('events');
        Schema::dropIfExists('list_fields');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('villages');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('regencies');
        Schema::dropIfExists('provinces');
    }
};
