<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * カテゴリー絞り込み検索ができる
     */
    public function test_it_can_search_videos_by_category()
    {
        $user = User::factory()->create();
        
        // 2つカテゴリーを作る
        $gameCategory = Category::factory()->create(['name' => 'ゲーム']);
        $chatCategory = Category::factory()->create(['name' => '雑談']);

        // game video
        $gameVideo = Video::factory()->create([
            'user_id' => $user->id,
            'category_id' => $gameCategory->id,
            'title' => 'ゲーム動画',
        ]);

        $chatVideo = Video::factory()->create([
            'user_id' => $user->id,
            'category_id' => $chatCategory->id,
            'title' => '雑談動画',
        ]);

        // ゲームカテゴリーを指定して検索
        $response = $this->actingAs($user)
            ->getJson("/api/videos?category_id={$gameCategory->id}");

        // アサート
        $response->assertStatus(200);

        // ゲーム動画が含まれている
        $response->assertJsonFragment([
            'id' => $gameVideo->id,
            'title' => 'ゲーム動画'
        ]);

        // 雑談動画が含まれて「いない」
        $response->assertJsonMissing([
            'id' => $chatVideo->id,
            'title' => '雑談動画'
        ]);
    }

    /**
     * ページネーションできる
     */
    public function test_it_can_search_videos_by_page()
    {
        $user = User::factory()->create();

        // 15本の動画を作成
        Video::factory()->count(15)->create(['user_id' => $user->id]);

        // 5件でリクエスト
        $response = $this->actingAs($user)->getJson('/api/videos?limit=5');

        $response->assertStatus(200);

        // 5件だけ返ってくる
        $response->assertJsonCount(5, 'data');

        // ページネーションの情報が含まれている
        $response->assertJsonStructure([
            'data',
            'meta' => [
                'current_page',
                'per_page',
                'total'
            ],
            'links',
        ]);
    }
}
