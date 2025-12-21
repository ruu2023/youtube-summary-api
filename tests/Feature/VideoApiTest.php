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
    public function test_it_can_fetch_list_of_videos()
    {
        $me = User::factory()->create();
        $others = User::factory()->create();
        // create dummy
        Video::factory()->create([
            'user_id' => $me->id,
            'title' => 'MyVideo',
        ]);
        Video::factory()->create([
            'user_id' => $others->id,
            'title' => 'OthersVideo',
        ]);

        // fetch api
        $response = $this->actingAs($me)->getJson('/api/videos');

        // assert
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data') // expect 1 records
            ->assertJsonPath('data.0.title', 'MyVideo');
    }

    /**
     * 動画を登録できる
     */
    public function test_it_can_store_a_video_no_category()
    {
        $user = User::factory()->create();
        // data define
        $data = [
            'video_id' => 'testId',
            'title' => 'this is first post',
            'description' => 'dear coder, i\'ve started learning tdd',
            'published_at' => '2025-12-07 12:00:00'
        ];

        $response = $this->actingAs($user)->postJson('/api/videos', $data);

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
            'user_id' => $user->id,
            'video_id' => 'newVideo1',
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
     * 他の人の動画を更新できない
     */
    public function test_it_cant_update_others_video()
    {
        $me = User::factory()->create();
        $others = User::factory()->create();
        $category = Category::factory()->create();
        $video = Video::factory()->create([
            'user_id' => $me->id,
            'title' => 'タイトル',
            'category_id' => $category->id
        ]);
        
        $response = $this->actingAs($others)->putJson("/api/videos/{$video->id}", [
            'title' => 'タイトル変更'
        ]);

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('videos', [
            'id' => $video->id,
            'title' => 'タイトル',
            'category_id' => $category->id
        ]);
    }

    /**
     * 存在しないカテゴリーidを指定するとエラーになる。(store)
     */
    public function test_it_cant_store_video_with_invalid_category_id()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/videos', [
            'video_id' => 'error_video',
            'title' => 'エラーテスト',
            'published_at' => now()->toDateString(),
            'category_id' => 9999
        ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors(['category_id']);
    }

    /**
     * 存在しないカテゴリーidを指定するとエラーになる。(update)
     */
    public function test_it_cant_update_video_with_invalid_category_id()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $video = Video::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id
        ]);

        $response = $this->actingAs($user)->putJson("/api/videos/{$video->id}", [
            'title' => 'エラーテスト',
            'published_at' => now()->toDateString(),
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
        $user = User::factory()->create();

        // create
        $video = Video::factory()->create([
            'user_id' => $user->id,
            'video_id' => 'testId',
            'title' => 'test title'
        ]);

        // fetch a video
        $response = $this->actingAs($user)->getJson("/api/videos/{$video->id}");

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
        $user = User::factory()->create();
        $video = Video::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson("/api/videos/{$video->id}"); 
        $response->assertStatus(204);

        $this->assertDatabaseMissing('videos', ['id' => $video->id]);
    }


    /**
     * 他の人の動画を削除できない
     */
    public function test_it_cant_delete_others_video()
    {
        $me = User::factory()->create();
        $others = User::factory()->create();
        $category = Category::factory()->create();
        $video = Video::factory()->create([
            'user_id' => $me->id,
            'title' => 'タイトル',
            'category_id' => $category->id
        ]);
        
        $response = $this->actingAs($others)->deleteJson("/api/videos/{$video->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('videos', [
            'id' => $video->id,
            'title' => 'タイトル',
            'category_id' => $category->id
        ]);
    }

    /**
     * 検索機能
     * 指定の言葉がタイトルか概要欄に含まれている
     */
    public function test_it_can_search_videos_by_keyword()
    {
        $user = User::factory()->create();
        // define
        Video::factory()->create([
            'user_id' => $user->id,
            'video_id' => 'testId1',
            'title' => 'minecraft seed change',
            'description' => 'playing minecraft'
        ]);
        Video::factory()->create([
            'user_id' => $user->id,
            'video_id' => 'testId2',
            'title' => 'apex legends',
            'description' => 'playing apex'
        ]);

        $response = $this->actingAs($user)->getJson('/api/videos?q=mine');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'minecraft seed change'])
            ->assertJsonMissing(['title' => 'apex legends']);
    }
}
