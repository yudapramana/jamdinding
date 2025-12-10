<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventBranch;
use App\Models\Event;
use App\Models\Branch;
use App\Models\MasterBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class __EventBranchController extends Controller
{
    public function index(Request $request)
    {
        $eventId = $request->get('event_id');
        if (!$eventId) {
            return response()->json([
                'message' => 'event_id is required.',
            ], 422);
        }

        $search  = $request->get('search');
        $perPage = (int) ($request->get('per_page') ?? 10);

        $query = EventBranch::where('event_id', $eventId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('branch_name', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%");
            });
        }

        $query->orderByRaw('COALESCE(order_number, 9999)')
              ->orderBy('branch_name');

        $branches = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $branches,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'event_id'     => ['required', 'exists:events,id'],
            'branch_id'    => ['required', 'exists:branches,id'],
            'branch_name'  => ['nullable', 'string', 'max:255'],
            'full_name'    => ['nullable', 'string', 'max:255'],
            'status'       => ['nullable', 'in:inactive,active'],
            'order_number' => ['nullable', 'integer', 'min:1'],
        ]);

        $event  = Event::findOrFail($data['event_id']);
        $branch = Branch::findOrFail($data['branch_id']);

        // jika branch_name / full_name kosong → isi default
        $branchName = $data['branch_name'] ?: $branch->name;
        $fullName   = $data['full_name'] ?: $branchName;

        // pastikan unik per event + branch
        $exists = EventBranch::where('event_id', $event->id)
            ->where('branch_id', $branch->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Cabang ini sudah terdaftar pada event tersebut.',
            ], 422);
        }

        $eventBranch = EventBranch::create([
            'event_id'     => $event->id,
            'branch_id'    => $branch->id,
            'branch_name'  => $branchName,
            'full_name'    => $fullName,
            'status'       => $data['status'] ?? 'active',
            'order_number' => $data['order_number'] ?? null,
        ]);

        return response()->json([
            'message' => 'Event branch created successfully.',
            'data'    => $eventBranch,
        ], 201);
    }

    public function update(Request $request, EventBranch $eventBranch)
    {
        $data = $request->validate([
            'event_id'     => ['nullable', 'exists:events,id'], // optional, biasanya tidak diubah
            'branch_id'    => ['nullable', 'exists:branches,id'],
            'branch_name'  => ['nullable', 'string', 'max:255'],
            'full_name'    => ['nullable', 'string', 'max:255'],
            'status'       => ['nullable', 'in:inactive,active'],
            'order_number' => ['nullable', 'integer', 'min:1'],
        ]);

        // jika branch_id diubah → validasi unik
        if (!empty($data['branch_id']) && $data['branch_id'] != $eventBranch->branch_id) {
            $exists = EventBranch::where('event_id', $eventBranch->event_id)
                ->where('branch_id', $data['branch_id'])
                ->where('id', '<>', $eventBranch->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Cabang ini sudah terdaftar pada event tersebut.',
                ], 422);
            }

            $branch = Branch::findOrFail($data['branch_id']);
            $eventBranch->branch_id = $branch->id;

            if (empty($data['branch_name'])) {
                $data['branch_name'] = $branch->name;
            }
            if (empty($data['full_name'])) {
                $data['full_name'] = $data['branch_name'];
            }
        }

        if (array_key_exists('branch_name', $data)) {
          $eventBranch->branch_name = $data['branch_name'] ?: $eventBranch->branch_name;
        }
        if (array_key_exists('full_name', $data)) {
          $eventBranch->full_name = $data['full_name'] ?: $eventBranch->branch_name;
        }
        if (array_key_exists('status', $data)) {
          $eventBranch->status = $data['status'];
        }
        if (array_key_exists('order_number', $data)) {
          $eventBranch->order_number = $data['order_number'];
        }

        // event_id jarang diubah, tapi kalau dikirim & beda, bisa diupdate
        if (!empty($data['event_id']) && $data['event_id'] != $eventBranch->event_id) {
            $eventBranch->event_id = $data['event_id'];
        }

        $eventBranch->save();

        return response()->json([
            'message' => 'Event branch updated successfully.',
            'data'    => $eventBranch,
        ]);
    }

    public function destroy(EventBranch $eventBranch)
    {
        $eventBranch->delete();

        return response()->json([
            'message' => 'Event branch deleted successfully.',
        ]);
    }

    /**
     * Generate event_branches berdasarkan master_branches.
     */
    public function generateFromTemplate(Request $request)
    {
        $data = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
        ]);

        $event = Event::findOrFail($data['event_id']);

        DB::beginTransaction();

        try {
            // ambil semua master branches aktif
            $masterBranches = MasterBranch::where('is_active', true)
                ->orderByRaw('COALESCE(order_number, 9999)')
                ->orderBy('full_name')
                ->get();

            if ($masterBranches->isEmpty()) {
                return response()->json([
                    'message' => 'Tidak ada master branches aktif untuk digenerate.',
                ], 422);
            }

            $createdCount = 0;

            foreach ($masterBranches as $mb) {
                // cek apakah event_branch untuk kombinasi event+branch sudah ada
                $exists = EventBranch::where('event_id', $event->id)
                    ->where('branch_id', $mb->branch_id)
                    ->exists();

                if ($exists) {
                    continue; // skip yang sudah ada
                }

                $branch = Branch::find($mb->branch_id);

                $branchName = $mb->branch_name ?: ($branch->name ?? 'Cabang');
                $fullName   = $mb->full_name ?: $branchName;

                EventBranch::create([
                    'event_id'     => $event->id,
                    'branch_id'    => $mb->branch_id,
                    'branch_name'  => $branchName,
                    'full_name'    => $fullName,
                    'status'       => 'active',
                    'order_number' => $mb->order_number,
                ]);

                $createdCount++;
            }

            DB::commit();

            return response()->json([
                'message' => 'Generate event branches dari template berhasil.',
                'created' => $createdCount,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Terjadi kesalahan saat generate cabang dari template.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
