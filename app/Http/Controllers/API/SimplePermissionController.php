<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;

class SimplePermissionController extends Controller
{
    public function index()
    {
        return Permission::orderBy('name')->get(['id', 'name', 'slug']);
    }
}
