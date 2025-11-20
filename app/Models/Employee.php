<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\DocType;
use App\Models\EmpDocument;

class Employee extends Model 
{

    protected $table = 'employees';
    public $timestamps = true;

    use SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $guarded = [];
    protected $appends = ['progress_dokumen'];
    

    public function work_unit()
    {
        return $this->belongsTo('App\Models\WorkUnit', 'id_work_unit', 'id');
    }

    public function workUnit()
    {
        return $this->belongsTo(WorkUnit::class, 'id_work_unit', 'id');
    }

    public function user()
    {
        return $this->hasOne('App\Models\User', 'id_employee');
    }

    public function getProgressDokumenAttribute()
    {
        // Jika flag update progress tidak aktif, kembalikan nilai dari kolom
        if (!$this->docs_progress_state) {
            return $this->attributes['progress_dokumen'] ?? 0;
        }

        // Ambil semua dokumen wajib sesuai status kepegawaian (PNS/PPPK)
        $mandatoryDocTypes = DocType::where('status', $this->employment_status)
                                    ->where('mandatory', true)
                                    ->pluck('id');

        $total = $mandatoryDocTypes->count();

        $uploaded = EmpDocument::where([
                        'id_employee' => $this->id,
                        'status' => 'Approved'
                    ])
                    ->whereIn('id_doc_type', $mandatoryDocTypes)
                    ->distinct()
                    ->count('id_doc_type');

        $progress = $total > 0 ? round(($uploaded / $total) * 100, 2) : 0;

        // Simpan hasil perhitungan ke kolom progress_dokumen
        $this->progress_dokumen = $progress;
        $this->docs_progress_state = false; // Reset flag
        $this->saveQuietly(); // Hindari trigger event/observer

        return $progress;
    }

}