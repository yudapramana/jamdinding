<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use App\Models\Event;

class PublicEventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 🔥 PENTING: bersihkan cache sebelum setiap test
        Cache::flush();
    }

    /** @test */
    public function it_returns_active_public_events()
    {
        // ARRANGE
        Event::factory()->create([
            'event_name' => 'MTQ Kabupaten 2025',
            'event_key'  => 'mtq-kab-2025',
            'is_active'  => true,
        ]);

        Event::factory()->create([
            'event_name' => 'MTQ Provinsi 2024',
            'event_key'  => 'mtq-prov-2024',
            'is_active'  => true,
        ]);

        // ACT
        $response = $this->getJson('/api/v1/public-events?limit=6');

        // ASSERT
        $response
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'event_name' => 'MTQ Kabupaten 2025',
            ])
            ->assertJsonFragment([
                'event_name' => 'MTQ Provinsi 2024',
            ]);
    }

    /** @test */
    public function it_does_not_return_inactive_events()
    {
        // ARRANGE
        Event::factory()->create([
            'event_name' => 'MTQ Aktif',
            'event_key'  => 'mtq-aktif',
            'is_active'  => true,
        ]);

        Event::factory()->create([
            'event_name' => 'MTQ Tidak Aktif',
            'event_key'  => 'mtq-nonaktif',
            'is_active'  => false,
        ]);

        // ACT
        $response = $this->getJson('/api/v1/public-events');

        // ASSERT
        $response
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing([
                'event_name' => 'MTQ Tidak Aktif',
            ]);
    }

    /** @test */
    public function it_returns_empty_array_if_no_active_events()
    {
        // ARRANGE
        Event::factory()->create([
            'event_name' => 'MTQ Lama',
            'event_key'  => 'mtq-lama',
            'is_active'  => false,
        ]);

        // ACT
        $response = $this->getJson('/api/v1/public-events');

        // ASSERT
        $response
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [],
            ]);
    }

    /** @test */
    public function public_event_response_has_required_structure_for_landing_page()
    {
        // ARRANGE
        Event::factory()->create([
            'event_name'     => 'MTQ Nasional',
            'event_key'      => 'mtq-nasional',
            'app_name'       => 'e-MTQ',
            'event_location' => 'Jakarta',
            'event_tagline'  => 'MTQ Digital Nasional',
            'start_date'     => '2025-05-01',
            'end_date'       => '2025-05-07',
            'is_active'      => true,
        ]);

        // ACT
        $response = $this->getJson('/api/v1/public-events');

        // ASSERT
        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'event_key',
                        'event_name',
                        'app_name',
                        'event_location',
                        'event_tagline',
                        'start_date',
                        'end_date',
                        'logo_event',
                        'created_at',
                        'updated_at',
                    ]
                ],
            ]);
    }
}
