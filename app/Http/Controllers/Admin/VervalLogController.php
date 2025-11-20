<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VervalLog;
use Illuminate\Http\Request;

class VervalLogController extends Controller
{
    public function index(Request $request)
    {
        $perPage   = (int) $request->input('per_page', 10);
        $search    = $request->input('search');
        $status    = $request->input('status');      // 'Uploaded','Approved',dst (single)
        $dateFrom  = $request->input('date_from');   // 'YYYY-MM-DD'
        $dateTo    = $request->input('date_to');     // 'YYYY-MM-DD'
        // default: ambil berdasarkan admin yang sedang login
        $verifierId = (int) $request->input('verified_by', auth()->id());

        // return $verifierId;

        $query = VervalLog::with([
                'verifier:id,name,email',
                'document' => function ($q) {
                    $q->with([
                        'employee:id,full_name,nip,id_work_unit',
                        'doctype:id,type_name' // aktifkan kalau ada tabel doc types
                    ]);
                }
            ])
            ->where('verified_by', $verifierId)
            ->when($status, function ($q, $status) {
                $q->where('verval_status', $status);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('verif_notes', 'like', "%{$search}%")
                       ->orWhereHas('document', function ($qd) use ($search) {
                           $qd->where('doc_number', 'like', "%{$search}%")
                              ->orWhere('file_name', 'like', "%{$search}%")
                              ->orWhere('parameter', 'like', "%{$search}%")
                              ->orWhereHas('employee', function ($qe) use ($search) {
                                  $qe->where('full_name', 'like', "%{$search}%")
                                     ->orWhere('nip', 'like', "%{$search}%");
                              });
                       });
                });
            })
            ->when($dateFrom, fn($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('created_at', '<=', $dateTo))
            // exclude Uploaded dan Reuploaded
            ->whereNotIn('verval_status', ['Uploaded', 'Reuploaded'])
            ->orderByDesc('created_at');

        $logs = $query->paginate($perPage);

        return response()->json($logs);
    }
}
