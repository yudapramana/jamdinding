<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Role;

class SimpleRoleController extends Controller
{
    public function index()
    {
        return Role::orderBy('name')->get(['id', 'name', 'slug']);
    }
}
