<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * カテゴリー一覧を取得できる
     */
    public function test_it_can_fetch_list_of_categories()
    {
        $user = User::factory()->create();
        Category::factory()->create(['name' => 'Game', 'user_id' => $user->id]);
        Category::factory()->create(['name' => 'Music', 'user_id' => $user->id]);
        // Other user's category (should not be seen)
        Category::factory()->create(['name' => 'Other', 'user_id' => User::factory()->create()->id]);

        $response = $this->actingAs($user)->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['name' => 'Game'])
            ->assertJsonFragment(['name' => 'Music'])
            ->assertJsonMissing(['name' => 'Other']);
    }

    /**
     * カテゴリーを作成できる
     */
    public function test_it_can_create_category()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/categories', [
            'name' => 'Vlog'
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Vlog']);
        
        $this->assertDatabaseHas('categories', ['name' => 'Vlog']);
    }

    /**
     * カテゴリー作成のバリデーション
     */
    public function test_validation_for_create_category()
    {
        $user = User::factory()->create();
        Category::factory()->create(['name' => 'Existing', 'user_id' => $user->id]);

        // require
        $response = $this->actingAs($user)->postJson('/api/categories', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['name']);

        // unique
        $response = $this->actingAs($user)->postJson('/api/categories', ['name' => 'Existing']);
        $response->assertStatus(422)->assertJsonValidationErrors(['name']);
    }
}
