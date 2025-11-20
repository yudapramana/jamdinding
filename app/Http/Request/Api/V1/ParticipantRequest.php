<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Sesuaikan dengan policy / gate kalau ada
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('participant'); // untuk update

        return [
            'event_id'                   => ['required', 'exists:events,id'],
            'event_competition_branch_id'=> ['required', 'exists:event_competition_branches,id'],

            'nik' => [
                'required',
                'string',
                'max:30',
                // unik per event
                'unique:participants,nik,' . $id . ',id,event_id,' . $this->input('event_id'),
            ],

            'full_name'    => ['required', 'string', 'max:150'],
            'phone_number' => ['required', 'string', 'max:30'],

            'place_of_birth' => ['required', 'string', 'max:100'],
            'date_of_birth'  => ['required', 'date'],
            'gender'         => ['required', 'in:MALE,FEMALE'],

            'province_id' => ['required', 'exists:provinces,id'],
            'regency_id'  => ['required', 'exists:regencies,id'],
            'district_id' => ['required', 'exists:districts,id'],
            'village_id'  => ['nullable', 'exists:villages,id'],

            'address'   => ['required', 'string'],
            'education' => ['required', 'in:SD,SMP,SMA,D1,D2,D3,D4,S1,S2,S3'],

            'bank_account_number' => ['required', 'string', 'max:50'],
            'bank_account_name'   => ['required', 'string', 'max:150'],
            'bank_name'           => ['required', 'string', 'max:50'],

            // file: di sini diasumsikan sudah di-upload ke storage dan hanya kirim URL/path
            'photo_url'        => ['nullable', 'string', 'max:255'],
            'id_card_url'      => ['nullable', 'string', 'max:255'],
            'family_card_url'  => ['nullable', 'string', 'max:255'],
            'bank_book_url'    => ['nullable', 'string', 'max:255'],
            'certificate_url'  => ['nullable', 'string', 'max:255'],
            'other_url'        => ['nullable', 'string', 'max:255'],

            'tanggal_terbit_ktp' => ['nullable', 'date'],
            'tanggal_terbit_kk'  => ['nullable', 'date'],
        ];
    }
}
