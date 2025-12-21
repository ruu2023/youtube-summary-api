<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VideoImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_import_video_from_youtube_by_id()
    {
        $targetId = 'lJaHSbygvTM'; // IDを統一

        Http::fake([
            'www.googleapis.com/*' => Http::response([
                'items' => [
                    [
                        'id' => $targetId,
                        'snippet' => [
                            'title' => 'web developer',
                            'description' => '概要欄です。',
                            'publishedAt' => now()->toDateString()
                        ],
                    ]
                ]
            ], 200),
        ]);

        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->postJson('/api/videos/import', [
            'video_id' => $targetId,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('videos', [
            'user_id'  => $user->id, // 正しく自分の所有になっているか
            'video_id' => $targetId,
            'title'    => 'web developer',
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

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/videos/import/channel', [
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
     * video が見つからない時に 404 を返却する
     */
    public function test_it_returns_404_when_video_not_found_on_youtube()
    {
        Http::fake(['www.googleapis.com/*' => Http::response(['items' => []], 200)]);

        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/videos/import', ['video_id' => 'invalid-id']);

        $response->assertStatus(404);
    }
    
    /**
     * すでに登録済みの動画を import した場合、重複せずに更新される。
     */
    public function test_it_updates_existing_video_instead_of_duplicate()
    {
        $user = User::factory()->create();
        $targetId = 'lJaHSbygvTM';

        Video::factory()->create([
            'user_id' => $user->id,
            'video_id' => $targetId,
            'title' => '古いタイトル'
        ]);
        
        Http::fake([
            'www.googleapis.com/*' => Http::response([
                'items' => [[
                    'id' => $targetId,
                    'snippet' => [
                        'title' => '更新された新しいタイトル',
                        'description' => '説明も更新',
                        'publishedAt' => now()->toDateString()
                    ],
                ]]
                ], 200),
            ]);
        
        $response = $this->actingAs($user)->postJson("/api/videos/import", [
            'video_id' => $targetId
        ]);

        $response->assertStatus(201);

        $this->assertEquals(1, Video::where('video_id', $targetId)->count());

        $this->assertDatabaseHas('videos', [
            'video_id' => $targetId,
            'title' => '更新された新しいタイトル'
        ]);
    }
    
    /**
     * 異なるユーザーであれば、同じ動画を重複してインポートできる。
     */
    public function test_different_users_can_import_same_video()
    {
        $me = User::factory()->create();
        $others = User::factory()->create();
        $targetId = 'shared_video_id';
        
        Http::fake([
                'www.googleapis.com/*' => Http::response([
                    'items' => [['snippet' => [
                        'title' => '共通の動画タイトル',
                        'description' => '説明',
                        'publishedAt' => now()->toIso8601String()
                    ]]]
                ], 200),
            ]);
        
        // import
        $this->actingAs($me)->postJson("/api/videos/import", ['video_id' => $targetId]);
        $this->actingAs($others)->postJson("/api/videos/import", ['video_id' => $targetId]);

        $this->assertEquals(2, Video::where('video_id', $targetId)->count());
        
        $this->assertDatabaseHas('videos', ['video_id' => $targetId, 'user_id' => $me->id]);
        $this->assertDatabaseHas('videos', ['video_id' => $targetId, 'user_id' => $others->id]);
    }
}
