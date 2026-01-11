<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MasterJudge;
use App\Models\EventJudge;
use App\Enums\RoleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\Event; // tambahkan di atas

class EventJudgeController extends Controller
{

    private function transformEventJudge(EventJudge $ej): array
    {
        return [
            'id'           => $ej->id,
            'judge_code'   => $ej->judge_code,
            'is_active'    => $ej->is_active,
            'master_judge' => $ej->masterJudge,
            'user'         => $ej->masterJudge->user,
        ];
    }

    /**
     * LIST Hakim per Event
     * GET /api/v1/events/{event}/judges
     */
    public function index(Request $request, int $eventId)
    {
        $search  = trim($request->string('search')->toString());
        $perPage = $request->integer('per_page', 25);

        $query = EventJudge::query()
            ->where('event_id', $eventId)
            ->with([
                'masterJudge:id,user_id,full_name,nik,specialization,certification_level,date_of_birth,gender,education,is_active,bank_account_number,bank_account_name,bank_name',
                'masterJudge.user:id,name,email,username',
            ])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->whereHas('masterJudge', function ($mq) use ($search) {
                        $mq->where('full_name', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%");
                    })
                    ->orWhereHas('masterJudge.user', function ($uq) use ($search) {
                        $uq->where('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                    });
                });
            })
            ->orderBy('sequence', 'DESC');

        // ✅ paginate langsung di DB
        $paginated = $query->paginate($perPage);

        // ✅ transform collection saja (tanpa rusak paginator)
        $paginated->getCollection()->transform(function (EventJudge $ej) {
            return [
                'id'           => $ej->id,
                'judge_code'   => $ej->judge_code,
                'is_active'    => $ej->is_active,
                'master_judge' => $ej->masterJudge,
                'user'         => $ej->masterJudge->user,
            ];
        });

        /**
         * ⚠️ PENTING
         * Return paginator LANGSUNG
         * agar frontend bisa akses:
         * data, current_page, per_page, total, from, to, last_page
         */
        return response()->json($paginated);
    }




    /**
     * CREATE / UPDATE
     * POST /api/v1/save-event-judges
     */

    public function store(Request $request)
    {

        $validated = $request->validate([
            'event_id' => ['required', 'exists:events,id'],

            // USER
            'user.name'     => ['required', 'string', 'max:150'],
            'user.email'    => [
                'required', 'email',
                Rule::unique('users', 'email')->ignore($request->input('user.id'))
            ],
            'user.username' => [
                'required', 'string',
                Rule::unique('users', 'username')->ignore($request->input('user.id'))
            ],

            // MASTER JUDGE
            'master_judge.nik' => [
                'required', 'digits:16',
                Rule::unique('master_judges', 'nik')
                    ->ignore($request->input('master_judge.id'))
            ],
            'master_judge.date_of_birth' => ['required', 'date'],
            'master_judge.gender'        => ['required', Rule::in(['MALE', 'FEMALE'])],
            'master_judge.specialization'       => ['nullable', 'string'],
            'master_judge.certification_level' => ['nullable', 'string'],
        ]);


        return DB::transaction(function () use ($validated, $request) {

            /* =========================
            * USER
            * ========================= */
            $userData = [
                'name'     => strtoupper($validated['user']['name']),
                'email'    => $validated['user']['email'],
                'username' => $validated['user']['username'],
                'event_id' => $validated['event_id'], // ✅ FIX: selalu set
            ];

            if ($request->filled('user.id')) {

                // UPDATE USER
                $user = User::findOrFail($request->input('user.id'));
                $user->update($userData);

            } else {

                // CREATE USER
                $user = User::create(array_merge($userData, [
                    'password'  => Hash::make('password123'),
                    'role_id'   => RoleType::DEWAN_HAKIM->value,
                    'is_active' => true,
                ]));
            }


            $user = User::updateOrCreate(
                ['id' => $request->input('user.id')],
                $request->filled('user.id')
                    ? $userData
                    : array_merge($userData, [
                        'password'  => Hash::make('password123'),
                        'role_id'   => RoleType::DEWAN_HAKIM->value,
                        'event_id'  => $validated['event_id'],
                        'is_active' => true,
                    ])
            );

            /* =========================
            * MASTER JUDGE
            * ========================= */
            $masterJudge = MasterJudge::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name'           => $user->name,
                    'nik'                 => $validated['master_judge']['nik'],
                    'date_of_birth'       => $validated['master_judge']['date_of_birth'],
                    'gender'              => $validated['master_judge']['gender'],
                    'specialization'      => $validated['master_judge']['specialization'] ?? null,
                    'certification_level' => $validated['master_judge']['certification_level'] ?? null,
                    'education'           => $request->input('master_judge.education', 'SMA'),
                    'bank_name'           => $request->input('master_judge.bank_name'),
                    'bank_account_number' => $request->input('master_judge.bank_account_number'),
                    'bank_account_name'   => $request->input('master_judge.bank_account_name'),
                    'is_active'           => $request->boolean('master_judge.is_active', true),
                ]
            );

            /* =========================
            * EVENT JUDGE
            * ========================= */
            $eventJudge = EventJudge::where([
                    'event_id' => $validated['event_id'],
                    'master_judge_id' => $masterJudge->id,
                ])
                ->first();

            if (!$eventJudge) {

                // 🔢 ambil sequence terakhir (FAST)
                $nextSeq = EventJudge::where('event_id', $validated['event_id'])
                    ->max('sequence') + 1;

                $event = Event::select('id', 'event_key')->findOrFail($validated['event_id']);

                $eventJudge = EventJudge::create([
                    'event_id'        => $event->id,
                    'master_judge_id' => $masterJudge->id,
                    'sequence'        => $nextSeq,
                    'judge_code'      => "{$event->event_key}-HJ-" . str_pad($nextSeq, 2, '0', STR_PAD_LEFT),
                    'is_active'       => $request->boolean('event_judge.is_active', true),
                ]);
            } else {
                $eventJudge->update([
                    'is_active' => $request->boolean('event_judge.is_active', true),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data hakim berhasil disimpan',
                'data'    => $this->transformEventJudge(
                    $eventJudge->load([
                        'masterJudge:id,user_id,full_name,nik,specialization,certification_level,date_of_birth,gender,education,is_active,bank_account_number,bank_account_name,bank_name',
                        'masterJudge.user:id,name,email,username',
                    ])
                ),
            ]);

        });
    }



    /**
     * DELETE Hakim dari Event
     * DELETE /api/v1/event-judges/{id}
     */
    public function destroy(int $id)
    {
        $eventJudge = EventJudge::findOrFail($id);
        $eventJudge->delete();

        return response()->json([
            'success' => true,
            'message' => 'Hakim berhasil dihapus dari event',
        ]);
    }

    public function checkNik(Request $request)
    {
        $request->validate([
            'nik'      => ['required', 'digits:16'],
            'event_id' => ['required', 'exists:events,id'],
        ]);

        $nik = $request->string('nik')->toString();
        $eventId = $request->integer('event_id');
        $currentMasterJudgeId = $request->integer('master_judge_id'); // ⬅️ NEW

        // Cari master judge berdasarkan NIK
        $masterJudge = MasterJudge::where('nik', $nik)->first();

        if (!$masterJudge) {
            return response()->json([
                'status' => 'available',
            ]);
        }

        // Cek apakah sudah terdaftar di event (KECUALI DIRI SENDIRI)
        $alreadyRegistered = EventJudge::where('event_id', $eventId)
            ->where('master_judge_id', '!=', $currentMasterJudgeId) // ⬅️ KRUSIAL
            ->where('master_judge_id', $masterJudge->id)
            ->exists();

        if ($alreadyRegistered) {
            return response()->json([
                'status' => 'already_registered',
            ]);
        }

        return response()->json([
            'status' => 'exists',
            'data' => [
                'master_judge' => $masterJudge,
                'user' => $masterJudge->user,
            ],
        ]);
    }

}
