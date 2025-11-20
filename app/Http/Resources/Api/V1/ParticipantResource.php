<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'   => $this->id,

            'event_id'                   => $this->event_id,
            'event_competition_branch_id'=> $this->event_competition_branch_id,

            'nik'          => $this->nik,
            'full_name'    => $this->full_name,
            'phone_number' => $this->phone_number,

            'place_of_birth' => $this->place_of_birth,
            'date_of_birth'  => $this->date_of_birth?->format('Y-m-d'),
            'gender'         => $this->gender,

            'province_id' => $this->province_id,
            'regency_id'  => $this->regency_id,
            'district_id' => $this->district_id,
            'village_id'  => $this->village_id,

            'address'   => $this->address,
            'education' => $this->education,

            'bank_account_number' => $this->bank_account_number,
            'bank_account_name'   => $this->bank_account_name,
            'bank_name'           => $this->bank_name,

            'photo_url'       => $this->photo_url,
            'id_card_url'     => $this->id_card_url,
            'family_card_url' => $this->family_card_url,
            'bank_book_url'   => $this->bank_book_url,
            'certificate_url' => $this->certificate_url,
            'other_url'       => $this->other_url,

            'tanggal_terbit_ktp' => $this->tanggal_terbit_ktp?->format('Y-m-d'),
            'tanggal_terbit_kk'  => $this->tanggal_terbit_kk?->format('Y-m-d'),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
