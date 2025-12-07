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
            'title' => 'testVideo1',
            'user_id' => 1,
        ]);

        // fetch api
        $response = $this->getJson('/api/videos');

        // assert
        $response->assertStatus(200)
            ->assertJsonCount(2); // expect 2 records
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
