<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 開発環境用のデフォルトカテゴリー
        // ユーザーに紐付ける必要があるため、最初のユーザーを取得するか、作成して紐付ける
        $user = User::first() ?? User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password')
        ]);

        $categories = [
            '雑談',
            'ゲーム',
            '歌枠',
            'ASMR',
            'コラボ',
            '告知',
            'マシュマロ',
            '記念配信'
        ];

        foreach($categories as $name) {
            Category::firstOrCreate([
                'name' => $name,
                'user_id' => $user->id
            ]);
        }
    }
}
