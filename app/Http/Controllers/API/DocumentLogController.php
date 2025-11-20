<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\VervalLog;

class DocumentLogController extends Controller
{
    public function show($id)
    {
        $logs = VervalLog::where('id_document', $id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log) {
                return [
                    'status' => $log->verval_status,
                    'verifier_name' => optional($log->verifier)->name ?? '—',
                    'verified_at' => $log->created_at ? $log->created_at->format('Y-m-d H:i') : '—',
                    'notes' => $log->verif_notes,
                ];
            });

        return response()->json(['data' => $logs]);
    }
}
