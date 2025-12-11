<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class __ParticipantController extends Controller
{
    public function index(Request $request)
    {
        $search  = $request->get('search');
        $perPage = (int) $request->get('per_page', 10);

        $query = Participant::query()->orderBy('full_name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function searchByNik(Request $request)
    {
        $nik = $request->get('nik');

        if (!$nik) {
            return response()->json([], 200);
        }

        $participant = Participant::where('nik', $nik)->first();

        if (!$participant) {
            return response()->json([], 200);
        }

        return response()->json($participant);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nik'            => ['required', 'string', 'max:30', 'unique:participants,nik'],
            'full_name'      => ['required', 'string', 'max:150'],
            'phone_number'   => ['nullable', 'string', 'max:30'],
            'place_of_birth' => ['required', 'string', 'max:100'],
            'date_of_birth'  => ['required', 'date'],
            'gender'         => ['required', Rule::in(['MALE', 'FEMALE'])],
            'education'      => ['nullable', Rule::in([
                'SD','SMP','SMA','D1','D2','D3','D4','S1','S2','S3'
            ])],
            'address'        => ['nullable', 'string'],
        ]);

        $participant = Participant::create($validated);

        return response()->json($participant, 201);
    }

    public function show(Participant $participant)
    {
        return response()->json($participant);
    }

    public function update(Request $request, Participant $participant)
    {
        $validated = $request->validate([
            'nik'            => ['required', 'string', 'max:30', Rule::unique('participants', 'nik')->ignore($participant->id)],
            'full_name'      => ['required', 'string', 'max:150'],
            'phone_number'   => ['nullable', 'string', 'max:30'],
            'place_of_birth' => ['required', 'string', 'max:100'],
            'date_of_birth'  => ['required', 'date'],
            'gender'         => ['required', Rule::in(['MALE', 'FEMALE'])],
            'education'      => ['nullable', Rule::in([
                'SD','SMP','SMA','D1','D2','D3','D4','S1','S2','S3'
            ])],
            'address'        => ['nullable', 'string'],
        ]);

        $participant->update($validated);

        return response()->json($participant);
    }

    public function destroy(Participant $participant)
    {
        $participant->delete();

        return response()->json(['message' => 'Participant deleted.']);
    }
}
