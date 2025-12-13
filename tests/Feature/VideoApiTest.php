<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Video;

class VideoApiTest extends TestCase
{
    use RefreshDatabase; // refresh db each test
    /**
     * 動画一覧をJSONで取得できる
     */
    public function it_can_fetch_list_of_videos()
    {
        // create dummy
        Video::factory()->create([
            'title' => 'testVideo1',
            'video_id' => 'testId1',
            'user_id' => 1,
        ]);
        Video::factory()->create([
            'title' => 'testVideo2',
            'video_id' => 'testId2',
            'user_id' => 1,
        ]);

        // fetch api
        $response = $this->getJson('/api/videos');

        // assert
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data'); // expect 2 records
    }

    /**
     * 動画を登録できる
     */
    public function test_it_can_store_a_video()
    {
        // data define
        $data = [
            'video_id' => 'testId',
            'user_id' => 1,
            'title' => 'this is first post',
            'description' => 'dear coder, i\'ve started learning tdd',
            'published_at' => '2025-12-07 12:00:00'
        ];

        $response = $this->postJson('/api/videos', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'this is first post']);

        $this->assertDatabaseHas('videos', [
            'title' => 'this is first post'
        ]);
    }

    /**
     * 動画の詳細を取得できる
     */
    public function test_it_can_fetch_single_video()
    {
        // create
        $video = Video::factory()->create([
            'video_id' => 'testId',
            'title' => 'test title'
        ]);

        // fetch a video
        $response = $this->getJson("/api/videos/{$video->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $video->id,
                    'title' => 'test title'
                ]
                ]);
    }

    /**
     * 動画を削除できる
     */
    public function test_it_can_delete_a_video()
    {
        $video = Video::factory()->create();

        $response = $this->deleteJson("/api/videos/{$video->id}"); 
        $response->assertStatus(204);

        $this->assertDatabaseMissing('videos', ['id' => $video->id]);
    }

    /**
     * 検索機能
     * 指定の言葉がタイトル化概要欄に含まれている
     */
    public function test_it_can_search_videos_by_keyword()
    {
        // define
        Video::factory()->create([
            'video_id' => 'testId1',
            'title' => 'minecraft seed change',
            'description' => 'playing minecraft'
        ]);
        Video::factory()->create([
            'video_id' => 'testId2',
            'title' => 'apex legends',
            'description' => 'playing apex'
        ]);

        $response = $this->getJson('/api/videos?q=mine');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'minecraft seed change'])
            ->assertJsonMissing(['title' => 'apex legends']);
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
