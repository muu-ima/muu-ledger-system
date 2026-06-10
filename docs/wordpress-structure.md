# WordPress Folder Rules

`wordpress/` 配下の責務分離ルールをまとめます。  
このプロジェクトでは、WordPress を「業務ロジックを持つバックエンド」として使うため、1ファイル肥大化を避けて、役割単位で PHP ファイルを分けます。

## 基本方針

- テーマは薄く保つ
- プラグインに業務ロジックを集約する
- `require_once` で明示的に読み込む
- 役割単位で分けるが、細かく分けすぎない
- 関数名は必ず `kobutsu_ledger_` 接頭辞を付ける

## テーマのルール

対象: `wordpress/themes/kobutsu-ledger-shell`

- テーマは Next.js フロントを表示するシェルに留める
- REST API、DB、同期処理、保存処理はテーマに置かない
- テーマ側に置いてよいもの:
  - iframe 表示
  - シェル用の最小限の URL 解決
  - 見た目に必要な最小限のテンプレート

現在の想定ファイル:

- `index.php`
  - フロントシェルの表示
- `functions.php`
  - シェル URL の解決など最小限の補助
- `style.css`
  - テーマ定義

## プラグインのルール

対象: `wordpress/plugins/kobutsu-ledger-api`

- プラグイン本体 `kobutsu-ledger-api.php` は「読み込みと起動」だけに寄せる
- 重い処理、管理画面、SQL、更新処理は `admin-*` や `includes/` に分離する
- WordPress のフック登録は `includes/bootstrap.php` に集約する

### 置き場所のルール

- `kobutsu-ledger-api.php`
  - プラグインヘッダ
  - `require_once`
  - 定数
  - bootstrap 呼び出し
- `admin-*.php`
  - 各管理画面機能の入口ファイル
  - 機能ごとの include 読み込み
  - 薄い公開関数
- `includes/bootstrap.php`
  - `register_activation_hook`
  - `add_action`
  - `add_filter`
- `includes/admin-menu.php`
  - 管理画面メニュー登録
- `includes/database.php`
  - テーブル作成
  - DB バージョン管理
  - テーブル名 helper
- `includes/rest.php`
  - REST route 登録
  - permission callback
  - route args
- `includes/helpers.php`
  - 汎用 helper
- `includes/sync/...`
  - テーブル間同期

## admin ファイル分割ルール

管理画面が大きくなったら、次の粒度で分けます。

- `view`
  - 一覧表示
  - 詳細フォーム
  - 画面内 CSS
- `actions`
  - 保存
  - 更新
  - 削除
  - POST ハンドラ
  - REST update 本体
- `query`
  - 一覧取得
  - 単票取得
  - SELECT SQL
- `helpers`
  - format
  - payload helper
  - 計算補助

## 今の EC販売 の分離例

`admin-ec-sales.php` は入口だけに寄せ、実装を以下へ分離しています。

- `includes/ec-sales-admin-view.php`
  - 一覧表示、詳細フォーム、CSS
- `includes/ec-sales-admin-actions.php`
  - quick update / save / payload update / delete
- `includes/ec-sales-admin-query.php`
  - 一覧取得、単票取得、SELECT SQL
- `includes/ec-sales-admin-helpers.php`
  - format、payload helper、`days_to_sell` 補完

## 今の 仕入元データ の分離例

`admin-supplier-sources.php` も入口だけに寄せ、実装を以下へ分離しています。

- `includes/supplier-sources-admin-view.php`
  - 一覧表示
- `includes/supplier-sources-admin-actions.php`
  - create / update / delete / POST handler
- `includes/supplier-sources-admin-query.php`
  - 一覧取得、単票取得、SELECT SQL
- `includes/supplier-sources-admin-helpers.php`
  - format、表示用 helper

## 現在のフォルダ構成

現時点では、`wordpress/` 配下は次の構成です。

```text
wordpress/
├── plugins/
│   └── kobutsu-ledger-api/
│       ├── kobutsu-ledger-api.php
│       ├── admin-ec-sales.php
│       ├── admin-launch-settings.php
│       ├── admin-ledger.php
│       ├── admin-supplier-sources.php
│       └── includes/
│           ├── admin-menu.php
│           ├── bootstrap.php
│           ├── database.php
│           ├── helpers.php
│           ├── ledger-rest-crud.php
│           ├── rest.php
│           ├── ec-sales-admin-actions.php
│           ├── ec-sales-admin-helpers.php
│           ├── ec-sales-admin-query.php
│           ├── ec-sales-admin-view.php
│           ├── supplier-sources-admin-actions.php
│           ├── supplier-sources-admin-helpers.php
│           ├── supplier-sources-admin-query.php
│           └── supplier-sources-admin-view.php
└── themes/
    └── kobutsu-ledger-shell/
        ├── functions.php
        ├── index.php
        └── style.css
```

## 分けるときの判断基準

- まず 1 ファイル 300 行超が見えたら分離を検討する
- UI と保存処理が同居し始めたら `view` と `actions` を分ける
- 同じ SQL を複数箇所で使うなら `query` に寄せる
- 複数の更新処理で共通利用する整形は `helpers` に寄せる

## やらないこと

- テーマに保存処理を書く
- 無接頭辞のグローバル関数を増やす
- 1関数ごとに過剰分割する
- 読み込み順に依存するのに、依存関係を書かずに増やす

## 追加時のチェック

新しい WordPress 側ファイルを追加したら、最低限これを確認します。

- `require_once` の位置が妥当か
- 依存関数の読み込み順が壊れていないか
- `php -l` が通るか
- 責務名とファイル名が一致しているか

## 今後の進め方

- 同期処理は `includes/sync/` にまとめる
- `admin-ledger.php` は必要になったら `view / actions / query` に分ける
- `admin-launch-settings.php` は設定項目が増えたら `view` と `settings helper` を分ける
- 将来的に増える管理画面も同じ命名規則にそろえる
