<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportVideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_import_video_and_auto_categorize()
    {
        $user = User::factory()->create();
        
        // Define category with keywords
        $category = Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Game',
            'keywords' => ['Minecraft', 'Apex']
        ]);

        // Mock Youtube API
        Http::fake([
            'googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'title' => 'Playing Minecraft with friends',
                            'description' => 'This is a fun video.',
                            'publishedAt' => '2025-01-01T12:00:00Z',
                            'resourceId' => ['videoId' => 'testVideoId']
                        ]
                    ]
                ]
            ], 200)
        ]);

        $response = $this->actingAs($user)->postJson('/api/videos/import', [
            'video_id' => 'testVideoId'
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('videos', [
            'video_id' => 'testVideoId',
            'title' => 'Playing Minecraft with friends',
            'category_id' => $category->id // Should be auto assigned
        ]);
    }

    public function test_it_leaves_category_null_if_no_match()
    {
        $user = User::factory()->create();
        
        Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Game',
            'keywords' => ['Apex']
        ]);

        // Mock Youtube API
        Http::fake([
            'googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'title' => 'Cooking Vlog',
                            'description' => 'Making pasta',
                            'publishedAt' => '2025-01-01T12:00:00Z',
                            'resourceId' => ['videoId' => 'cookingId']
                        ]
                    ]
                ]
            ], 200)
        ]);

        $response = $this->actingAs($user)->postJson('/api/videos/import', [
            'video_id' => 'cookingId'
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('videos', [
            'video_id' => 'cookingId',
            'category_id' => null
        ]);
    }
}
