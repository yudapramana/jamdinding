<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventMaqraDrawing extends Model
{
    use HasFactory;

    protected $table = 'event_maqra_drawings';

    protected $fillable = [
        'round_id',
        'event_participant_id',
        'event_team_id',
        'exam_type',
        'event_maqra_id',
        'event_question_package_id',
        'drawn_at',
        'estimated_performance_time',
        'drawn_by',
        'status',
    ];

    protected $casts = [
        // Cast otomatis ke instance Carbon datetime
        'drawn_at' => 'datetime',
        'estimated_performance_time' => 'datetime',
    ];

    /**
     * Relasi ke Babak (Round)
     */
    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    /**
     * Relasi ke Peserta Individu
     */
    public function eventParticipant(): BelongsTo
    {
        return $this->belongsTo(EventParticipant::class);
    }

    /**
     * Relasi ke Peserta Regu/Tim (Fahmil)
     */
    public function eventTeam(): BelongsTo
    {
        return $this->belongsTo(EventTeam::class);
    }

    /**
     * Relasi ke Bank Maqra (Surat/Ayat)
     */
    public function eventMaqra(): BelongsTo
    {
        return $this->belongsTo(EventMaqra::class);
    }

    /**
     * Relasi ke Bank Paket Soal
     */
    public function eventQuestionPackage(): BelongsTo
    {
        return $this->belongsTo(EventQuestionPackage::class);
    }

    /**
     * Relasi ke User/Petugas yang melakukan pencatatan undian
     */
    public function drawnBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'drawn_by');
    }
}