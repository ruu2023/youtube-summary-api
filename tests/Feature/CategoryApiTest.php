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
     * カテゴリーを作成できる（キーワード含む）
     */
    public function test_it_can_create_category()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/categories', [
            'name' => 'Vlog',
            'keywords' => ['daily', 'life']
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Vlog'])
            ->assertJson([
                'data' => [
                    'keywords' => ['daily', 'life']
                ]
            ]);
        
        $this->assertDatabaseHas('categories', ['name' => 'Vlog']);
    }

    /**
     * カテゴリー詳細を取得できる
     */
    public function test_it_can_show_category()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'name' => 'ShowTest']);

        $response = $this->actingAs($user)->getJson("/api/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'ShowTest']);
    }

    /**
     * カテゴリーを更新できる
     */
    public function test_it_can_update_category()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'name' => 'OldName']);

        $response = $this->actingAs($user)->putJson("/api/categories/{$category->id}", [
            'name' => 'NewName',
            'keywords' => ['updated']
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'NewName']);
            
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'NewName']);
    }

    /**
     * カテゴリーを削除できる
     */
    public function test_it_can_delete_category()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    /**
     * 他人のカテゴリーは操作できない
     */
    public function test_it_cant_access_others_category()
    {
        $me = User::factory()->create();
        $others = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $others->id]);

        // show
        $this->actingAs($me)->getJson("/api/categories/{$category->id}")->assertStatus(403);
        // update
        $this->actingAs($me)->putJson("/api/categories/{$category->id}", ['name' => 'hack'])->assertStatus(403);
        // delete
        $this->actingAs($me)->deleteJson("/api/categories/{$category->id}")->assertStatus(403);
    }

    /**
     * カテゴリー作成のバリデーション（ユニーク制約はユーザー単位）
     */
    public function test_validation_for_create_category()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        Category::factory()->create(['name' => 'Existing', 'user_id' => $user1->id]);

        // Same user cannot create duplicate
        $response = $this->actingAs($user1)->postJson('/api/categories', ['name' => 'Existing']);
        $response->assertStatus(422)->assertJsonValidationErrors(['name']);

        // Other user CAN create same name
        $response = $this->actingAs($user2)->postJson('/api/categories', ['name' => 'Existing']);
        $response->assertStatus(201);
    }
}
