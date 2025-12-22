# YouTube Summary API

このプロジェクトは、YouTube Data APIを活用して動画情報を管理・収集するためのRESTful APIです。
Laravel 11で構築されており、動画情報のCRUD操作、YouTubeからの動画情報の単一インポート、およびチャンネル指定による一括インポート機能を備えています。

## 機能一覧

- **認証**: Sanctumを使用したAPIトークン認証
- **動画管理**:
  - 動画一覧の取得（検索、ソート機能付き）
  - 動画情報の詳細表示
  - 動画情報の更新
  - 動画情報の削除
- **YouTube インポート機能**:
  - **動画インポート**: YouTube動画IDを指定して、動画タイトル、説明、公開日などを自動取得して保存します。
  - **チャンネルインポート**: チャンネルIDと期間（開始日・終了日）を指定して、その期間に公開された動画を一括でインポートします。API使用量を抑える最適化（PlaylistItems使用・日付フィルタリング）が施されています。

## 必要要件

- PHP 8.2 以上
- Laravel 11.x
- Composer

## セットアップ

1. リポジトリのクローン
   ```bash
   git clone <repository-url>
   cd youtube-summary-api
   ```

2. 依存関係のインストール
   ```bash
   composer install
   ```

3. 環境変数の設定
   `.env.example` をコピーして `.env` を作成し、必要な設定を行ってください。特にデータベース接続とYouTube Data APIキーの設定が必要です。
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   **`.env` の設定項目 (追加):**
   ```ini
   YOUTUBE_API_KEY=your_youtube_api_key_here
   ```
   ※ `config/services.php` に以下のような設定が既に存在するか、追加する必要があります。
   ```php
   'youtube' => [
       'key' => env('YOUTUBE_API_KEY'),
   ],
   ```

4. データベースのマイグレーション
   ```bash
   php artisan migrate
   ```

## 認証機能 (Authentication)

このAPIはLaravel Sanctumを使用したトークン認証を採用しています。「Route [login] not defined」エラーを回避し、APIとして正しく認証を行うためには、以下の手順でアクセストークンを取得してください。

Postmanなどでテストする際は、Headersに `Accept: application/json` を指定することを推奨します。

### 1. ユーザー登録
まずはユーザー登録を行い、アクセストークンを取得します。

- **Endpoint**: `POST /api/register`
- **Body**:
  ```json
  {
    "name": "Test User",
    "email": "test@example.com",
    "password": "password",
    "password_confirmation": "password"
  }
  ```
- **Response**: アクセストークンが含まれます。

### 2. ログイン
登録済みのユーザーでログインし、新しいアクセストークンを取得します。

- **Endpoint**: `POST /api/login`
- **Body**:
  ```json
  {
    "email": "test@example.com",
    "password": "password"
  }
  ```
- **Response**: アクセストークンが含まれます。

### 3. 認証付きリクエスト
取得したトークンをヘッダーにセットしてAPIを利用します。

- **Header**: `Authorization: Bearer <your_access_token>`

### 初期データ投入

標準的なカテゴリー（雑談、ゲーム、等）を初期データとして登録できます。

```bash
php artisan db:seed
```

## API エンドポイント

全てのAPIリクエストには `Authorization: Bearer <token>` ヘッダーが必要です（認証関連のエンドポイントを除く）。

### 認証 (Auth)

| メソッド | パス | 説明 | パラメータ |
| --- | --- | --- | --- |
| POST | `/api/register` | ユーザー登録 | `name`, `email`, `password`, `password_confirmation` |
| POST | `/api/login` | ログイン | `email`, `password` |
| POST | `/api/logout` | ログアウト (トークン破棄) | - |

### 動画リソース

| メソッド | パス | 説明 |
| --- | --- | --- |
| GET | `/api/videos` | 動画一覧を取得。フィルタリングとページネーション対応。<br>パラメータ:<br>- `q`: 検索キーワード (タイトル/説明)<br>- `category_id`: カテゴリーIDで絞り込み<br>- `limit`: 1ページあたりの件数 (デフォルト20, 最大100)<br>- `page`: ページネーション時のページ番号を指定 |
| POST | `/api/videos` | 新しい動画を手動で登録 |
| GET | `/api/videos/{video}` | 特定の動画の詳細を取得 |
| PUT | `/api/videos/{video}` | 動画情報を更新 |
| DELETE | `/api/videos/{video}` | 動画を削除 |

### カテゴリーリソース

| メソッド | パス | 説明 |
| --- | --- | --- |
| GET | `/api/categories` | カテゴリー一覧を取得 (ユーザーごと) |
| POST | `/api/categories` | カテゴリーを作成 |

### インポート

| メソッド | パス | 説明 | パラメータ |
| --- | --- | --- | --- |
| POST | `/api/videos/import` | YouTube動画IDから動画をインポート | `video_id` (必須), `category_id` |
| POST | `/api/videos/import/channel` | チャンネルから指定期間の動画を一括インポート | `channel_id` (必須, '@'ハンドル対応), `from` (必須: YYYY-MM-DD), `to` (必須: YYYY-MM-DD), `category_id` |

### ユーザー

| メソッド | パス | 説明 |
| --- | --- | --- |
| GET | `/api/user` | ログインユーザー情報を取得 |

## ライセンス

このプロジェクトは [MITライセンス](LICENSE) の元で公開されています。
