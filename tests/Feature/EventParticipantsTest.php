<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\District;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Event;
use App\Models\EventBranch;
use App\Models\EventCategory;
use App\Models\EventGroup;
use App\Models\Participant;
use App\Models\EventParticipant;
use App\Models\Group;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;

class EventParticipantsTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsAdmin(Event $event)
    {
        $user = User::factory()->adminEvent()->create();

        $this->actingAs($user);

        // mock event context (biasanya via middleware / store)
        session(['event_id' => $event->id]);

        return $user;
    }

    /** @test */
    public function it_can_list_event_participants()
    {
        $event = Event::factory()->create();
        $this->actingAsAdmin($event);

        $participant = Participant::factory()->create();

        EventParticipant::factory()->create([
            'event_id' => $event->id,
            'participant_id' => $participant->id,
            'registration_status' => 'bank_data',
        ]);

        $response = $this->getJson("/api/v1/events/{$event->id}/participants");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'registration_status',
                        'participant' => [
                            'id',
                            'full_name',
                            'nik',
                        ],
                        'event_group',
                    ]
                ],
                'current_page',
                'per_page',
                'total',
            ]);
    }

    /** @test */
    public function it_can_create_event_participant_with_participant_data()
    {
        $event = Event::factory()->create();
        $this->actingAsAdmin($event);

        // =========================
        // WILAYAH (WAJIB ADA)
        // =========================
        $province = Province::factory()->create();
        $regency  = Regency::factory()->create(['province_id' => $province->id]);
        $district = District::factory()->create(['regency_id' => $regency->id]);
        $village  = Village::factory()->create(['district_id' => $district->id]);

        // =========================
        // CABANG → GOLONGAN → KATEGORI (KONSISTEN)
        // =========================
        $branchMaster = Branch::factory()->create();

        $branch = EventBranch::factory()->create([
            'event_id'  => $event->id,
            'branch_id' => $branchMaster->id,
        ]);

        $groupMaster = Group::factory()->create();

        $group = EventGroup::factory()->create([
            'event_id'  => $event->id,
            'branch_id' => $branchMaster->id,
            'group_id'  => $groupMaster->id,
        ]);

        $categoryMaster = Category::factory()->create();

        $eventCategory = EventCategory::factory()->create([
            'event_id'    => $event->id,
            'branch_id'   => $branchMaster->id,
            'group_id'    => $groupMaster->id,
            'category_id' => $categoryMaster->id,
        ]);

        // =========================
        // PAYLOAD
        // =========================
        $payload = [
            'participant' => json_encode([
                'nik' => '3201010101010001',
                'full_name' => 'Ahmad Fauzi',
                'phone_number' => '081234567890',
                'place_of_birth' => 'Padang',
                'date_of_birth' => '2010-01-01',
                'gender' => 'MALE',
                'education' => 'SMA',
                'address' => 'Jl. Contoh',

                'province_id' => $province->id,
                'regency_id'  => $regency->id,
                'district_id' => $district->id,
                'village_id'  => $village->id,

                'bank_account_number' => '1234567890',
                'bank_account_name' => 'Ahmad Fauzi',
                'bank_name' => 'BRI',

                'tanggal_terbit_ktp' => '2020-01-01',
                'tanggal_terbit_kk' => '2020-01-01',
            ]),

            'event_participant' => json_encode([
                'event_id' => $event->id,
                'event_category_id' => $eventCategory->id,
                'registration_status' => 'bank_data',
            ]),
        ];

        // =========================
        // EXECUTE
        // =========================
        $response = $this->postJson('/api/v1/save-event-participants', $payload);

        $response->assertCreated(); // ✅ 201

        // =========================
        // ASSERT DATABASE
        // =========================
        $this->assertDatabaseHas('participants', [
            'nik' => '3201010101010001',
            'full_name' => 'Ahmad Fauzi',
            'bank_name' => 'BRI',
        ]);

        $this->assertDatabaseHas('event_participants', [
            'event_id' => $event->id,
            'event_category_id' => $eventCategory->id,
            'registration_status' => 'bank_data',
        ]);
    }



    /** @test */
    public function it_can_bulk_register_event_participants()
    {
        $event = Event::factory()->create();
        $this->actingAsAdmin($event);

        $participants = EventParticipant::factory()->count(3)->create([
            'event_id' => $event->id,
            'registration_status' => 'bank_data',
        ]);

        $ids = $participants->pluck('id')->toArray();

        $response = $this->postJson('/api/v1/event-participants/bulk-register', [
            'ids' => $ids,
            'event_id' => $event->id,
            'registration_status' => 'process',
        ]);

        $response->assertStatus(200);

        foreach ($ids as $id) {
            $this->assertDatabaseHas('event_participants', [
                'id' => $id,
                'registration_status' => 'process',
            ]);
        }
    }

    /** @test */
    public function it_can_mutate_participant_region()
    {
        $event = Event::factory()->create();
        $this->actingAsAdmin($event);

        // ======== WILAYAH AWAL ========
        $province1 = Province::factory()->create();
        $regency1  = Regency::factory()->create(['province_id' => $province1->id]);
        $district1 = District::factory()->create(['regency_id' => $regency1->id]);

        // ======== WILAYAH TUJUAN MUTASI ========
        $province2 = Province::factory()->create();
        $regency2  = Regency::factory()->create(['province_id' => $province2->id]);
        $district2 = District::factory()->create(['regency_id' => $regency2->id]);

        $participant = Participant::factory()->create([
            'province_id' => $province1->id,
            'regency_id'  => $regency1->id,
            'district_id' => $district1->id,
        ]);

        EventParticipant::factory()->create([
            'event_id'       => $event->id,
            'participant_id' => $participant->id,
        ]);

        // ======== ACTION ========
        $response = $this->postJson(
            "/api/v1/event-participants/{$participant->id}/mutasi-wilayah",
            [
                'province_id' => $province2->id,
                'regency_id'  => $regency2->id,
                'district_id' => $district2->id,
            ]
        );

        // ======== ASSERT ========
        $response->assertOk();

        $this->assertDatabaseHas('participants', [
            'id'           => $participant->id,
            'province_id'  => $province2->id,
            'regency_id'   => $regency2->id,
            'district_id'  => $district2->id,
        ]);
    }




}
