<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    
    public function __invoke(Request $request)
    {
        $role = '';
        $role = $request->user()->role;
        return view('admin.layouts.app',
            [
                'role' => $role
            ]);
    }

}
