<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PermissionRole;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class PermissionRoleController extends Controller
{

    public function sync(Request $request, Role $role)
    {
        $data = $request->validate([
            'permission_ids'   => ['required','array'],
            'permission_ids.*' => ['integer'],
        ]);

        $ids = collect($data['permission_ids'])
            ->filter(fn($v) => is_numeric($v))
            ->map(fn($v) => (int)$v)
            ->unique()
            ->values()
            ->all();

        // pastikan semua id valid
        $validCount = Permission::whereIn('id', $ids)->count();
        if ($validCount !== count($ids)) {
            return response()->json([
                'message' => 'Ada permission_id yang tidak valid.',
            ], 422);
        }

        DB::transaction(function () use ($role, $ids) {
            // default pivot Laravel: permission_role
            $role->permissions()->sync($ids);
        });

        return response()->json([
            'message' => 'Permissions role berhasil disinkronkan.',
            'data' => [
                'role_id' => $role->id,
                'permission_ids' => $ids,
            ],
        ]);
    }


    public function index(Request $request)
    {
        $search   = $request->get('search');
        $roleId   = $request->get('role_id');
        $permId   = $request->get('permission_id');
        $perPage  = (int) $request->get('per_page', 10);

        $query = PermissionRole::query()
            ->with(['role', 'permission'])
            ->orderBy('role_id')
            ->orderBy('permission_id');

        if ($roleId) {
            $query->where('role_id', $roleId);
        }

        if ($permId) {
            $query->where('permission_id', $permId);
        }

        if ($search) {
            $query->whereHas('role', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            })->orWhereHas('permission', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $data = $query->paginate($perPage);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'role_id'       => ['required', 'exists:roles,id'],
            'permission_id' => [
                'required',
                'exists:permissions,id',
                Rule::unique('permission_role')->where(function ($q) use ($request) {
                    return $q->where('role_id', $request->role_id);
                }),
            ],
        ], [
            'permission_id.unique' => 'Permission ini sudah terpasang di role tersebut.',
        ]);

        $pivot = PermissionRole::create($validated)->load(['role', 'permission']);

        return response()->json([
            'success' => true,
            'message' => 'Permission berhasil ditambahkan ke role.',
            'data'    => $pivot,
        ], 201);
    }

    public function show(PermissionRole $permissionRole)
    {
        return response()->json(
            $permissionRole->load(['role', 'permission'])
        );
    }

    public function update(Request $request, PermissionRole $permissionRole)
    {
        $validated = $request->validate([
            'role_id'       => ['required', 'exists:roles,id'],
            'permission_id' => [
                'required',
                'exists:permissions,id',
                Rule::unique('permission_role')
                    ->where(function ($q) use ($request) {
                        return $q->where('role_id', $request->role_id);
                    })
                    ->ignore($permissionRole->id),
            ],
        ], [
            'permission_id.unique' => 'Permission ini sudah terpasang di role tersebut.',
        ]);

        $permissionRole->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Relasi permission-role berhasil diperbarui.',
            'data'    => $permissionRole->load(['role', 'permission']),
        ]);
    }

    public function destroy(PermissionRole $permissionRole)
    {
        $permissionRole->delete();

        return response()->json([
            'success' => true,
            'message' => 'Relasi permission-role berhasil dihapus.',
        ]);
    }
}
