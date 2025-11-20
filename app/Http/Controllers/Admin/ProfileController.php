<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Fortify\UpdateUserPassword;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;


class ProfileController extends Controller
{
    public function index(Request $request)
    {
        // return $request->user()->only(['id', 'name', 'email', 'role', 'avatar', 'nama_pemeriksa', 'nip_pemeriksa', 'jabatan', 'org_name', 'org_id', 'print_layout', 'username']);
        $user = $request->user();
        $user->load('employee.work_unit');
        $user->load('role');
        return $user;
    }

    public function docsUpdateState(Request $request)
    {
        // return $request->user()->only(['docs_update_state']);
        $user = $request->user();
        return [
            'docs_update_state' => $user->docs_update_state,
            'docs_progress_state' => $user->employee->docs_progress_state,
            'progress_dokumen' => $user->employee->progress_dokumen
        ];

    }

    public function update(Request $request)
    {
        $employee = $request->user()->employee;
        $employeeId = $employee->id;
        $validated = $request->validate([
            'nip' => 'sometimes|required|string|size:18|unique:employees,nip,' . $employeeId,
            'full_name' => 'sometimes|required|string|max:100',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:M,F',
            'phone_number' => 'nullable|string|max:15',
            'email' => 'nullable|email|unique:employees,email,' . $employeeId . '|max:100',
            'job_title' => 'sometimes|required|string|max:255',
            'id_work_unit' => 'sometimes|required|integer|exists:work_units,id',
            'employment_status' => 'sometimes|required|in:PNS,PPPK',
            'tmt_pangkat' => 'nullable|date',
            'tmt_jabatan' => 'nullable|date',
            'document' => 'nullable|file|mimes:pdf,jpg,png|max:2048'
        ]);

        $employee->update($validated);   
        return response()->json(['success' => true]);
    }

    public function uploadImage(Request $request)
    {

        if ($request->has('profile_picture')) {
            // $previousPath = $request->user()->getRawOriginal('avatar');

            // $link = Storage::put('/photos', $request->file('profile_picture'));

            // $request->user()->update(['avatar' => $link]);

            // Storage::delete($previousPath);

            $request->user()->update(['avatar' => $request->profile_picture]);

            return response()->json(['message' => 'Profile picture uploaded successfully!']);
        }
    }

    public function changePassword(Request $request, UpdateUserPassword $updater)
    {
        $updater->update(
            auth()->user(),
            [
                'current_password' => $request->currentPassword,
                'password' => $request->password,
                'password_confirmation' => $request->passwordConfirmation,
            ]
        );

        return response()->json(['message' => 'Password changed successfully!']);
    }
}