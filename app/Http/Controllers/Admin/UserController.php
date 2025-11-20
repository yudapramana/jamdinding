<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Enums\RoleType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::withAggregate('employee', 'id_work_unit')
            ->with(['employee.workUnit'])
            // Pencarian
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($q) use ($search) {
                            $q->where('full_name', 'like', "%{$search}%")
                                ->orWhere('nip', 'like', "%{$search}%")
                                ->orWhereHas('workUnit', function ($q) use ($search) {
                                    $q->where('unit_name', 'like', "%{$search}%");
                                });
                        });
                });
            })
            // Filter unit kerja
            ->when($request->work_unit_id, function ($query, $workUnitId) {
                $query->whereHas('employee', function ($q) use ($workUnitId) {
                    $q->where('id_work_unit', $workUnitId);
                });
            })
             // Exclude role (gunakan kolom users.role yang enum/int value)
            ->when($request->filled('exclude_role'), function ($query) use ($request) {
                $raw = strtoupper($request->input('exclude_role')); // contoh: "USER"

                try {
                    // Ambil enum dari nama case (USER -> RoleType::USER)
                    $roleEnum = RoleType::from(constant(RoleType::class . '::' . $raw)->value);
                    $query->where('role', '!=', $roleEnum->value);
                } catch (\Throwable $e) {
                    // Jika nama tidak valid, bisa diabaikan atau lempar exception
                }
            })
            // ->orderBy('employee_id_work_unit', 'asc')
            ->orderBy('role', 'asc') // sort by tanggal lahir
            // ->orderBy('username', 'asc') // sort by tanggal lahir
            ->paginate(setting('pagination_limit'));

        return response()->json($users);
    }

    // public function index(Request $request)
    // {
    //     $users = User::withAggregate('employee','id_work_unit')->with([
    //         'employee.workUnit'
    //     ])
    //     ->when($request->search, function ($query, $search) {
    //         $query->where(function ($q) use ($search) {
    //             $q->where('name', 'like', "%{$search}%")
    //             ->orWhere('email', 'like', "%{$search}%")
    //             ->orWhereHas('employee', function ($q) use ($search) {
    //                 $q->where('full_name', 'like', "%{$search}%")
    //                     ->orWhere('nip', 'like', "%{$search}%")
    //                     ->orWhereHas('workUnit', function ($q) use ($search) {
    //                         $q->where('unit_name', 'like', "%{$search}%");
    //                     });
    //             });
    //         });
    //     })
    //     ->orderBy('employee_id_work_unit', 'asc')
    //     ->paginate(10);

    //     return response()->json($users);
    // }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email|unique:employees,email',
            'nip' => 'required|unique:employees,nip',
            'password' => 'required|string|min:6',
        ]);

        $employee = Employee::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'nip' => $request->nip,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'job_title' => $request->job_title,
            'id_work_unit' => $request->id_work_unit,
            'employment_status' => $request->employment_status,
            'tmt_jabatan' => $request->tmt_jabatan,
            'tmt_pangkat' => $request->tmt_pangkat,
        ]);

        $user = User::create([
            'name' => $request->full_name,
            'email' => $request->email,
            'username' => $request->nip,
            'password' => Hash::make($request->password),
            'id_employee' => $employee->id,
        ]);

        return response()->json(['message' => 'User created', 'data' => $user], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::with('employee')->findOrFail($id);

        $user->update([
            'name' => $request->full_name,
            'email' => $request->email,
            'username' => $request->nip,
        ]);

        if ($request->password) {
            $user->password = Hash::make($request->password);
            $user->save();
        }

        $user->employee()->update([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'nip' => $request->nip,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'job_title' => $request->job_title,
            'id_work_unit' => $request->id_work_unit,
            'employment_status' => $request->employment_status,
            'employment_category' => $request->employment_category,
            'phone_number' => $request->phone_number,
            'tmt_jabatan' => $request->tmt_jabatan,
            'tmt_pangkat' => $request->tmt_pangkat,
        ]);

        return response()->json(['message' => 'User updated']);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $employee = $user->employee;

        $user->delete();
        if ($employee) {
            $employee->delete();
        }

        return response()->json(['message' => 'User deleted']);
    }

    // public function changeRole(User $user)
    // {

    //     $user->update([
    //         'role' => request('role'),
    //     ]);

    //     return response()->json(['success' => true]);
    // }

    public function bulkDelete()
    {
        User::whereIn('id', request('ids'))->delete();

        return response()->json(['message' => 'Users deleted successfully']);
    }

    public function fetch()
    {

        return auth()->user()->id;

    }

    public function changeRole(Request $request, User $user)
    {
        $request->validate([
            'role' => ['required'], // bisa string nama role atau integer value enum
        ]);

        $targetEnum = $this->resolveRoleEnum($request->input('role'));
        if (!$targetEnum) {
            throw ValidationException::withMessages([
                'role' => ['Role tidak valid. Gunakan USER/ADMIN/REVIEWER/SUPERADMIN atau value enum yang sesuai.']
            ]);
        }

        // Ambil id role di tabel roles (pivot) sesuai nama enum
        $targetRoleId = Role::where('name', $targetEnum->name)->value('id');
        if (!$targetRoleId) {
            throw ValidationException::withMessages([
                'role' => ['Role target tidak ditemukan pada tabel roles. Pastikan seeding roles sudah benar.']
            ]);
        }

        $userRoleId = Role::where('name', 'USER')->value('id'); // role dasar
        if (!$userRoleId) {
            throw ValidationException::withMessages([
                'role' => ['Role USER tidak ditemukan pada tabel roles.']
            ]);
        }

        // return $targetEnum->value;

        DB::transaction(function () use ($user, $targetEnum, $targetRoleId, $userRoleId) {
            // Update kolom utama di users
            $user->update([
                'role' => $targetEnum->value,
            ]);

            if ($targetEnum === RoleType::USER) {
                // Turun ke USER → hapus semua elevated role, sisakan USER saja
                $user->update([
                    'can_multiple_role' => false,
                ]);
                $user->roles()->sync([$userRoleId]);
            } else {
                // Naik / set ke elevated role → tambahkan role target + pastikan USER ikut
                 $user->update([
                    'can_multiple_role' => true,
                ]);
                $idsToAttach = array_unique([$targetRoleId, $userRoleId]);
                $user->roles()->sync($idsToAttach);
            }
        });

        // Kembalikan state terbaru
        $user->load('roles:id,name');

        return response()->json([
            'success' => true,
            'data' => [
                'id'                 => $user->id,
                'role'               => $targetEnum->name,
                'role_value'         => $targetEnum->value,
                'can_multiple_role'  => $user->can_multiple_role,
                'roles'              => $user->roles->pluck('name'),
            ],
        ]);
    }

    /**
     * Terima input role berupa string nama atau integer enum value,
     * kembalikan RoleType|NULL.
     */
    private function resolveRoleEnum($input): ?RoleType
    {
        // Numeric → langsung ke enum value
        if (is_numeric($input)) {
            return RoleType::tryFrom((int) $input);
        }

        // String → cocokkan ke nama enum (case-insensitive)
        $name = strtoupper(trim((string) $input));
        foreach (RoleType::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }
        return null;
    }

}
