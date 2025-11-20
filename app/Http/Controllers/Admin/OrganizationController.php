<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $searchQuery = $request->search_query;
        $orgs = Organization::query()
                ->when(request('search_query'), function ($q) use ($searchQuery) {
                    return $q->where('name', 'like', "%{$searchQuery}%");
                })
                ->latest()
                ->paginate(setting('pagination_limit'));

        return $orgs;
    }

    public function store()
    {

        request()->validate([
            'name' => 'required',
            'email' => 'required|unique:users,email',
        ]);

        return Organization::create([
            'name' => request('name'),
            'email' => request('email'),
        ]);

    }

    public function update(Organization $org)
    {

        request()->validate([
            'name' => 'required',
            'email' => 'required|unique:users,email,' . $org->id,
        ]);

        $org->update([
            'name' => request('name'),
            'email' => request('email'),
        ]);

        return $org;
    }

    public function destroy(Organization $org)
    {
        $org->delete();
        return response()->noContent();
    }

    public function bulkDelete()
    {
        Organization::whereIn('id', request('ids'))->delete();

        return response()->json(['message' => 'Organization deleted successfully']);
    }

    public function fetch(Request $request)
    {
        $orgs = Organization::all();
        return $orgs;
    }
}
