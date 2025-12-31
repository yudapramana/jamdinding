<?php

namespace Database\Factories;

use App\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParticipantFactory extends Factory
{
    protected $model = Participant::class;

    public function definition(): array
    {
        return [
            // Identitas dasar
            'nik'              => (string) fake()->numerify('32##############'), // TANPA unique(),
            'full_name'        => $this->faker->name,
            'phone_number'     => $this->faker->phoneNumber,

            // TTL
            'place_of_birth'   => $this->faker->city,
            'date_of_birth'    => $this->faker->dateTimeBetween('-20 years', '-7 years')->format('Y-m-d'),
            'gender'           => $this->faker->randomElement(['MALE', 'FEMALE']),

            // Pendidikan
            'education'        => $this->faker->randomElement([
                'SD','SMP','SMA','D1','D2','D3','D4','S1','S2','S3'
            ]),

            // Alamat
            'address'          => $this->faker->address,

            // Relasi wilayah (nullable → aman untuk test)
            'province_id'      => null,
            'regency_id'       => null,
            'district_id'      => null,
            'village_id'       => null,

            // Fallback teks wilayah
            'province_name'    => $this->faker->state,
            'regency_name'     => $this->faker->city,
            'district_name'    => $this->faker->citySuffix,
            'village_name'     => $this->faker->streetName,

            // Rekening
            'bank_account_number' => $this->faker->bankAccountNumber,
            'bank_account_name'   => $this->faker->name,
            'bank_name'           => $this->faker->randomElement([
                'BRI','BNI','MANDIRI','BTN','BSI','BCA','BANK NAGARI'
            ]),

            // Dokumen (default kosong)
            'photo_url'        => null,
            'id_card_url'      => null,
            'family_card_url'  => null,
            'bank_book_url'    => null,
            'certificate_url'  => null,
            'other_url'        => null,

            // Tanggal terbit
            'tanggal_terbit_ktp' => $this->faker->dateTimeBetween('-10 years', '-1 years')->format('Y-m-d'),
            'tanggal_terbit_kk'  => $this->faker->dateTimeBetween('-10 years', '-1 years')->format('Y-m-d'),

            'created_at'       => now(),
            'updated_at'       => now(),
        ];
    }

    /**
     * State: lampiran lengkap (≥80%)
     */
    public function withCompleteAttachments(): static
    {
        return $this->state(fn () => [
            'photo_url'       => 'photos/test.jpg',
            'id_card_url'     => 'docs/ktp.pdf',
            'family_card_url' => 'docs/kk.pdf',
            'bank_book_url'   => 'docs/tabungan.pdf',
        ]);
    }
}
