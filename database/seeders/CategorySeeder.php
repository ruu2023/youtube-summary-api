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
            '雑談' => ['雑談', '喋る', '話す', '報告', 'お知らせ'],
            'ゲーム' => ['Game', 'ゲーム', 'PLAY', '実況', '配信', 'APEX', 'Minecraft', 'Pokemon', 'ポケモン'],
            '歌枠' => ['SINGING', '歌枠', '歌う', 'KARAOKE' , 'カラオケ'],
            'ASMR' => ['ASMR', 'バイノーラル', '囁き', 'KU-100'],
            'コラボ' => ['コラボ', 'COLLAB', 'GUEST'],
            '告知' => ['告知', '重大', '発表', 'お知らせ'],
            'マシュマロ' => ['マシュマロ', 'Marshmallow', '質問'],
            '記念配信' => ['記念', '周年', '万人', 'Birthday', '誕生日']
        ];

        foreach($categories as $name => $keywords) {
            Category::updateOrCreate(
                [
                    'name' => $name,
                    'user_id' => $user->id
                ],
                [
                    'keywords' => $keywords
                ]
            );
        }
    }
}
