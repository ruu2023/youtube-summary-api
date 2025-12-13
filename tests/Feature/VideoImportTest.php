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

    public function test_it_can_import_video_from_youtube_by_cannel_id_designated_date_between()
    {
        Http::fake([
            // get channel detail
            'www.googleapis.com/youtube/v3/channels*' => Http::response([
                'items' => [
                    [
                        'contentDetails' => [
                            'relatedPlaylists' => [
                                'uploads' => 'UU_DUMMY_PLAYLIST_ID'
                            ]
                        ]
                    ]
                ]
            ], 200),

            // get video list
            'www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'title' => 'test video',
                            'description' => '概要欄です。',
                            'publishedAt' => '2025-01-15T12:00:00Z',
                            'resourceId' => [
                                'videoId' => 'TEST_VIDEO_001'
                            ]

                        ]
                    ]
                ],
                'nextPageToken' => null
            ], 200),
        ]);

        $response = $this->postJson('/api/videos/import/channel', [
            'channel_id' => '@miyanoyami',
            'from' => '2025-01-01',
            'to' => '2025-01-31'
        ]);

        // assert
        $response->assertStatus(200)
                ->assertJson(['count' => 1]);

        $this->assertDatabaseHas('videos', [
            'video_id' => 'TEST_VIDEO_001',
            'title' => 'test video',
            'published_at' => '2025-01-15 12:00:00',
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
