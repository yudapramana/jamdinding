<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class __JudgeUserController extends Controller
{
    private function judgeRoleId(): int
    {
        $role = Role::where('name', 'DEWAN_HAKIM')->first();
        abort_if(!$role, 422, 'Role DEWAN_HAKIM tidak ditemukan.');
        return (int) $role->id;
    }

    private function ensureIsJudge(User $user): void
    {
        $judgeRoleId = $this->judgeRoleId();
        $isJudge = $user->roles()->where('roles.id', $judgeRoleId)->exists();
        abort_if(!$isJudge, 403, 'User ini bukan DEWAN_HAKIM.');
    }

    /**
     * GET /api/v1/events/{event}/judges
     * params: search, per_page, is_active (0/1/'')
     */
    public function index(Request $request, Event $event)
    {
        $perPage = (int) $request->get('per_page', 10);
        $search  = trim((string) $request->get('search', ''));
        $isActive = $request->get('is_active', ''); // '' | '0' | '1'

        $judgeRoleId = $this->judgeRoleId();

        $q = User::query()
            ->where('event_id', $event->id)
            ->whereHas('roles', fn($r) => $r->where('roles.id', $judgeRoleId))
            ->select(['id','name','username','email','is_active','can_multiple_role','event_id'])
            ->orderBy('name');

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($isActive !== '' && in_array((string)$isActive, ['0','1'], true)) {
            $q->where('is_active', (int)$isActive);
        }

        return response()->json($q->paginate($perPage));
    }

    /**
     * POST /api/v1/events/{event}/judges
     * body: name, username, email, password(optional), is_active, can_multiple_role
     */
    public function store(Request $request, Event $event)
    {
        $judgeRoleId = $this->judgeRoleId();

        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'username' => ['required','string','max:255', 'unique:users,username'],
            'email' => ['required','email','max:255', 'unique:users,email'],
            'password' => ['nullable','string','min:6'],
            'is_active' => ['nullable','boolean'],
            'can_multiple_role' => ['nullable','boolean'],
        ]);

        $plainPassword = $data['password'] ?? null;

        $user = DB::transaction(function () use ($data, $event, $plainPassword, $judgeRoleId) {
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($plainPassword ?: $data['username']), // default aman: username
                'event_id' => $event->id,
                'is_active' => (bool)($data['is_active'] ?? true),
                'can_multiple_role' => (bool)($data['can_multiple_role'] ?? true),
            ]);

            $user->roles()->syncWithoutDetaching([$judgeRoleId]);

            return $user;
        });

        return response()->json([
            'message' => 'Dewan hakim berhasil ditambahkan.',
            'data' => $user->only(['id','name','username','email','is_active','can_multiple_role','event_id']),
        ], 201);
    }

    /**
     * PUT /api/v1/judges/{user}
     * body: name, username, email, password(optional), is_active, can_multiple_role, event_id(optional)
     */
    public function update(Request $request, User $user)
    {
        $this->ensureIsJudge($user);

        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'username' => ['required','string','max:255', Rule::unique('users','username')->ignore($user->id)],
            'email' => ['required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
            'password' => ['nullable','string','min:6'],
            'is_active' => ['nullable','boolean'],
            'can_multiple_role' => ['nullable','boolean'],
            'event_id' => ['nullable','integer', Rule::exists('events','id')],
        ]);

        DB::transaction(function () use ($user, $data) {
            $user->name = $data['name'];
            $user->username = $data['username'];
            $user->email = $data['email'];

            if (array_key_exists('is_active', $data)) {
                $user->is_active = (bool) $data['is_active'];
            }
            if (array_key_exists('can_multiple_role', $data)) {
                $user->can_multiple_role = (bool) $data['can_multiple_role'];
            }
            if (!empty($data['event_id'])) {
                $user->event_id = (int) $data['event_id'];
            }

            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            $user->save();
        });

        return response()->json([
            'message' => 'Dewan hakim berhasil diperbarui.',
            'data' => $user->only(['id','name','username','email','is_active','can_multiple_role','event_id']),
        ]);
    }

    /**
     * PATCH /api/v1/judges/{user}/toggle-active
     * body: is_active (boolean) optional
     */
    public function toggleActive(Request $request, User $user)
    {
        $this->ensureIsJudge($user);

        $data = $request->validate([
            'is_active' => ['nullable','boolean'],
        ]);

        $user->is_active = array_key_exists('is_active', $data)
            ? (bool) $data['is_active']
            : ! (bool) $user->is_active;

        $user->save();

        return response()->json([
            'message' => 'Status user diperbarui.',
            'data' => $user->only(['id','is_active']),
        ]);
    }

    /**
     * DELETE /api/v1/judges/{user}
     */
    public function destroy(User $user)
    {
        $this->ensureIsJudge($user);

        DB::transaction(function () use ($user) {
            // lepas role DEWAN_HAKIM dari pivot (biar aman kalau user multi-role)
            $judgeRoleId = $this->judgeRoleId();
            $user->roles()->detach([$judgeRoleId]);

            // kalau mau benar-benar hapus user:
            $user->delete();
        });

        return response()->json(['message' => 'User dewan hakim berhasil dihapus.']);
    }
}
