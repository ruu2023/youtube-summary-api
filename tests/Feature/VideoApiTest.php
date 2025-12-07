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
     * @test
     * 動画一覧をJSONで取得できる
     */
    public function it_can_fetch_list_of_videos()
    {
        // create dummy
        Video::factory()->create([
            'title' => 'testVideo1',
            'user_id' => 1,
        ]);
        Video::factory()->create([
            'title' => 'testVideo2',
            'user_id' => 1,
        ]);

        // fetch api
        $response = $this->getJson('/api/videos');

        // assert
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data'); // expect 2 records
    }

    /**
     * @test
     * 動画を登録できる
     */
    public function test_it_can_store_a_video()
    {
        // data define
        $data = [
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
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
