<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
     * 動画を登録できる（カテゴリーあり）
     */
    public function test_it_can_store_a_video_with_category()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'ゲーム']);

        // request
        $response = $this->actingAs($user)->postJson('/api/videos', [
            'video_id' => 'newVideo1',
            'title' => '新しい動画',
            'description' => '概要欄です。',
            'published_at' => '2025-01-01 10:00:00',
            'category_id' => $category->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('videos', [
            'video_id' => 'newVideo1',
            'title' => '新しい動画',
            'category_id' => $category->id

        ]);
    }

    /**
     * 動画の情報を更新できる
     */
    public function test_it_can_update_video()
    {
        $user = User::factory()->create();
        $oldCategory = Category::factory()->create(['name' => '雑談']);
        $video = Video::factory()->create([
            'user_id' => $user->id,
            'category_id' => $oldCategory->id
        ]);

        $newCategory = Category::factory()->create(['name' => '歌枠']);

        // request
        $response = $this->actingAs($user)->putJson("/api/videos/{$video->id}", [
            'title' => 'タイトル変更',
            'category_id' => $newCategory->id
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('videos', [
            'id' => $video->id,
            'title' => 'タイトル変更',
            'category_id' => $newCategory->id
        ]);
    }

    /**
     * 存在しないカテゴリーidを指定するとエラーになる。
     */
    public function test_it_cant_store_video_with_invalid_category_id()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/videos', [
            'video_id' => 'error_video',
            'user_id' => $user->id,
            'title' => 'エラーテスト',
            'published_at' => now(),
            'category_id' => 9999
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors(['category_id']);
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
     * 指定の言葉がタイトルか概要欄に含まれている
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
