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
        Schema::create('assessment_fields', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code')->nullable();          // 'TAJWID', 'LAGU', 'SUARA' (optional)
            $table->string('field_name');                // 'Tajwid', 'Lagu', 'Fashahah', dll
            $table->text('description')->nullable();     // penjelasan kriteria
            $table->string('default_unit')->nullable();  // 'point', 'nilai', 'rank', dll (opsional)
            $table->timestamps();
        });

        Schema::create('master_branch_field_components', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('master_competition_branch_id');   // FK
            $table->unsignedBigInteger('assessment_field_id');            // FK

            $table->unsignedInteger('default_weight')->nullable();        // bobot %
            $table->unsignedInteger('default_max_score')->nullable();     // max nilai per hakim
            $table->unsignedInteger('default_order')->nullable();         // urutan tampil

            $table->boolean('is_default')->default(false);                // digunakan sebagai template default

            $table->timestamps();

            // Foreign keys
            $table->foreign('master_competition_branch_id', 'fk_mbfc_branch')
                  ->references('id')
                  ->on('master_competition_branches')
                  ->onDelete('cascade');

            $table->foreign('assessment_field_id', 'fk_mbfc_assessment')
                  ->references('id')
                  ->on('assessment_fields')
                  ->onDelete('cascade');
        });

        Schema::create('event_branch_field_components', function (Blueprint $table) {
            $table->id();

            // event_competition_branch_id FK (manual name)
            $table->unsignedBigInteger('event_competition_branch_id');
            $table->foreign('event_competition_branch_id', 'fk_ebfc_event_branch')
                ->references('id')
                ->on('event_competition_branches')
                ->onDelete('cascade');

            // assessment_field_id FK (manual name)
            $table->unsignedBigInteger('assessment_field_id');
            $table->foreign('assessment_field_id', 'fk_ebfc_assessment_field')
                ->references('id')
                ->on('assessment_fields')
                ->onDelete('cascade');

            // master_branch_field_component_id FK (manual name)
            $table->unsignedBigInteger('master_branch_field_component_id')->nullable();
            $table->foreign(
                'master_branch_field_component_id',
                'fk_ebfc_master_comp'
            )
                ->references('id')
                ->on('master_branch_field_components')
                ->onDelete('set null');

            // Properti komponen
            $table->unsignedInteger('weight')->nullable();     // bobot event
            $table->unsignedInteger('max_score')->nullable();  // maksimal skor
            $table->unsignedInteger('order')->default(1);      // urutan tampil

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Index
            $table->index('event_competition_branch_id', 'idx_ebfc_event_branch');
            $table->index('assessment_field_id', 'idx_ebfc_assessment_field');
        });

        Schema::create('rounds', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);           // Penyisihan, Semifinal, Final
            $table->string('slug', 50)->unique(); // penyisihan, semifinal, final
            $table->unsignedTinyInteger('order_no')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('event_competitions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->foreignId('event_competition_branch_id')
                ->constrained('event_competition_branches')
                ->cascadeOnDelete();

            $table->foreignId('round_id')
                ->constrained('rounds')
                ->restrictOnDelete();

            $table->string('name', 150)->nullable();

            // 0 = perorangan, 1 = regu
            $table->boolean('is_team')->default(false);
            $table->unsignedTinyInteger('team_size_min')->nullable();
            $table->unsignedTinyInteger('team_size_max')->nullable();

            $table->dateTime('schedule_start')->nullable();
            $table->dateTime('schedule_end')->nullable();
            $table->string('venue', 150)->nullable();

            $table->enum('status', ['draft','aktif','selesai','dibatalkan'])
                ->default('draft');

            $table->timestamps();
        });

        Schema::create('event_assessment_headers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->foreignId('event_competition_id')
                ->constrained('event_competitions')
                ->cascadeOnDelete();

            $table->foreignId('event_competition_branch_id')
                ->constrained('event_competition_branches')
                ->cascadeOnDelete();

            // asumsi sudah punya tabel event_participants
            $table->foreignId('event_participant_id')
                ->constrained('event_participants')
                ->cascadeOnDelete();

            // hakim (user)
            $table->foreignId('judge_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->decimal('total_score', 8, 2)->nullable();
            $table->unsignedInteger('rank_in_round')->nullable();
            $table->enum('status', ['draft','submitted','validated'])->default('draft');
            $table->text('notes')->nullable();
            $table->dateTime('judged_at')->nullable();

            $table->timestamps();
        });

        Schema::create('event_assessment_scores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_assessment_header_id')
                ->constrained('event_assessment_headers')
                ->cascadeOnDelete();

            $table->foreignId('event_branch_field_component_id')
                ->constrained('event_branch_field_components')
                ->cascadeOnDelete();

            $table->decimal('score', 6, 2);
            $table->decimal('max_score', 6, 2)->nullable();   // snapshot
            $table->decimal('weight', 5, 2)->nullable();       // snapshot
            $table->decimal('weighted_score', 8, 2)->nullable();
            $table->string('notes', 255)->nullable();

            $table->timestamps();
        });

        Schema::create('event_branch_judges', function (Blueprint $table) {
            $table->id();

            // FK -> event_competition_branches
            $table->foreignId('event_competition_branch_id')
                ->constrained()
                ->onDelete('cascade');

            // FK -> users
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // Additional fields
            $table->string('role_in_panel', 50)->nullable();  // ketua, anggota, dsb.
            $table->unsignedTinyInteger('order_no')->nullable(); 
            $table->boolean('is_chief')->default(false);       // ketua majelis

            $table->timestamps();

            // Unique: satu user tidak boleh jadi hakim dua kali dalam satu branch
            $table->unique(['event_competition_branch_id', 'user_id'], 'ux_ebj_branch_user');
        });



    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_fields');
        Schema::dropIfExists('master_branch_field_components');
        Schema::dropIfExists('event_branch_field_components');
        Schema::dropIfExists('rounds');
        Schema::dropIfExists('event_competitions');
        Schema::dropIfExists('event_assessment_headers');
        Schema::dropIfExists('event_assessment_scores');
        Schema::dropIfExists('event_branch_judges');
    }
};
