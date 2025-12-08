<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VideoImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_import_video_from_youtube()
    {
        Http::fake([
            'www.googleapis.com/*' => Http::response([
                'items' => [
                    [
                        'id' => 'lJaHSbygvTM',
                        'snippet' => [
                            'title' => 'web developer',
                            'description' => '概要欄です。',
                            'publishedAt' => '2025-12-08T12:00:00Z'
                        ],
                    ]
                ]
            ], 200),
        ]);

        $response = $this->postJson('/api/videos/import', [
            'video_id' =>  'lJaHSbygyTM',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('videos', [
            'title' => 'web developer',
            'description' => '概要欄です。',
        ]);
    }
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
