<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

class PageController extends Controller
{
    public function landing() {
        if (Auth::check()) return redirect('/admin/dashboard');
        return view('admin.layouts.landing', ['events' => \App\Models\Event::where('is_active', true)->get()]);
    }
}
