# Kobutsu Ledger System

Next.js + WordPress + Docker の古物台帳システム雛形です。

## 起動

```bash
docker compose up --build
```

- Next.js: http://localhost:3000
- WordPress: http://localhost:8081

初回起動後、WordPress の初期設定を済ませてから管理画面で `Kobutsu Ledger API` プラグインを有効化してください。

## 構成

- `frontend/`: Next.js App Router の画面
- `wordpress/plugins/kobutsu-ledger-api/`: 古物台帳用 REST API プラグイン
- `docker-compose.yml`: MySQL、WordPress、Next.js の開発環境

## REST API

プラグイン有効化後、次のエンドポイントが使えます。

- `GET /wp-json/kobutsu/v1/items`
- `POST /wp-json/kobutsu/v1/items`
- `GET /wp-json/kobutsu/v1/items/{id}`

現時点では開発用に認証を緩めています。本番化する前に WordPress nonce、権限、監査ログ、バックアップ、古物営業法に合わせた入力必須項目の確認を追加してください。
