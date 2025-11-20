<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrgsResource;
use App\Models\DocType;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasterController extends Controller
{
    public function index()
    {
        $type = request()->query('type');
        $id = request()->query('id');

        $data = [];

        switch ($type) {
            case 'orgs':
                $data = OrgsResource::collection(Organization::all());
                break;

            case 'users':
                $data = User::where('organization_id', $id)->get()->map(function ($item) {

                    return [
                        'id' => $item->id,
                        'text' => $item->name
                    ];
                });
                break;

            case 'doctypes':

                if(request()->has('user_id')) {
                    $user_id = request()->query('user_id');
                    $user = User::find($user_id);
                } else {
                    $user = Auth::user();
                }
                $employee = $user->employee;
                $employmentStatus = $employee->employment_status;

                $data = DocType::where('status', $employmentStatus)->get()->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'text' => $item->type_name,
                        'label' => $item->label,
                        'mandatory' => $item->mandatory,
                        'multiple' => $item->multiple,
                    ];
                });
                break;

            case 'workunits':
                $data = WorkUnit::get()->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'text' => $item->unit_name
                    ];
                });

                break;

            default:
                # code...
                break;
        }

        return response()->json([
            'data' => $data
        ]);
    }

}
