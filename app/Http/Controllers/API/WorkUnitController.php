<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\WorkUnit;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Auth;
use GPBMetadata\Google\Api\Auth as ApiAuth;

class WorkUnitController extends Controller
{
    // public function fetch()
    // {
    //     return response()->json(
    //         WorkUnit::query()
    //             ->select('id', 'unit_name', 'unit_code', 'parent_unit')
    //             ->orderBy('unit_name')
    //             ->get()
    //     );
    // }

    /**
     * Return employees by work unit id.
     * GET /api/work-units/{id}/employees
     */
    public function fetchEmployee(Request $request, $id)
    {
        // Optional: simple pagination support ?page=&per_page=
        $perPage = (int) $request->get('per_page', 0);

        $query = Employee::query()
            ->select([
                'id',
                'nip',
                'full_name',
                // 'job_title',
                // 'employment_status',
                // 'gol_ruang',
                // 'email',
                // 'phone_number',
                'progress_dokumen',
                'id_work_unit',
            ])
            ->where('employment_category', 'ACTIVE')
            ->where('id_work_unit', $id)
            ->whereNull('deleted_at') // softDeletes filter
            ->orderByDesc('progress_dokumen')     // 1st key
            ->orderBy('nip', 'ASC');        // 2nd key

        if ($perPage > 0) {
            $paginator = $query->paginate($perPage);
            // Return only the data array for simplicity on frontend (adjust if you want meta)
            return response()->json([
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ]
            ]);
        }

        return response()->json($query->get());
    }


    public function fetch()
    {
        // Ambil tree sampai 3 level anak, mulai dari akar (parent_unit = null)
        $units = WorkUnit::with('children.children.children')
            ->whereNull('parent_unit')
            ->get();

        // Flatten ke list datar (pre-order) dengan urutan berdasarkan unit_code
        $flat = collect();

        $walk = function ($nodes) use (&$walk, &$flat) {
            // Urutkan nodes saat ini berdasarkan unit_code (natural, case-insensitive)
            $sorted = collect($nodes)->sortBy(
                fn ($x) => (string)($x->unit_code ?? ''),
                SORT_NATURAL | SORT_FLAG_CASE
            )->values();

            foreach ($sorted as $n) {
                $flat->push([
                    'id'          => $n->id,
                    'unit_name'   => $n->unit_name,
                    'unit_code'   => $n->unit_code,
                    'parent_unit' => $n->parent_unit,
                ]);

                if ($n->relationLoaded('children') && $n->children->isNotEmpty()) {
                    // Urutkan anak berdasarkan unit_code lalu telusuri
                    $children = $n->children->sortBy(
                        fn ($c) => (string)($c->unit_code ?? ''),
                        SORT_NATURAL | SORT_FLAG_CASE
                    )->values();

                    $walk($children);
                }
            }
        };

        $walk($units);

        // Hilangkan potensi duplikasi (jaga-jaga)
        $flat = $flat->unique('id')->values();

        return response()->json($flat);
    }

    
    public function index(Request $request)
    {
        $query = WorkUnit::query();

        if ($request->has('search_query')) {
            $search = $request->search_query;
            $query->where('unit_name', 'like', "%$search%")
                  ->orWhere('unit_code', 'like', "%$search%");
        }

        return $query->paginate(10);
    }

   

    public function show($id)
    {
        $unit = WorkUnit::findOrFail($id);
        return response()->json($unit);
    }

    

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        WorkUnit::whereIn('id', $ids)->delete();

        return response()->json(['message' => 'Selected units deleted']);
    }

    // GET
    public function tree()
    {
        $units = WorkUnit::with('children.children.children')->whereNull('parent_unit')->get();
        return response()->json($units);
    }

    public function monitor()
    {
        $units = WorkUnit::select('id', 'unit_name', 'unit_code')->get();
        return response()->json($units);
    }

    public function selfMonitor()
    {
        $user = Auth::user();
        $user->load('employee');
        $employee = $user->employee;

        $units = WorkUnit::select('id', 'unit_name', 'unit_code')->where('id', $employee->id_work_unit)->get();
        return response()->json($units);
    }

    // PUT
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'unit_name' => 'required|string',
            'unit_code' => 'required|string',
            'parent_unit' => 'nullable|exists:work_units,id',
        ]);

        $unit = WorkUnit::findOrFail($id);
        $unit->update($data);

        return response()->json(['message' => 'Updated']);
    }

    // POST
    public function store(Request $request)
    {
        $data = $request->validate([
            'unit_name' => 'required|string',
            'unit_code' => 'required|string',
            'parent_unit' => 'nullable|exists:work_units,id',
        ]);

        WorkUnit::create($data);
        return response()->json(['message' => 'Created']);
    }

    // DELETE
    public function destroy($id)
    {
        $unit = WorkUnit::findOrFail($id);
        $unit->delete();
        return response()->json(['message' => 'Deleted']);
    }


}
