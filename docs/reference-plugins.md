# Reference Plugins

`docs/sample-data/` に追加された tools-hub の参考プラグインから、今回の古物台帳システムへ取り込む方針です。

## `shipping-rest-search-sample.php`

参考になる点:

- REST route を専用namespaceで切る: `/wp-json/shipping/v1/search`
- `page`, `per_page`, `orderby`, `order` を標準パラメータとして扱う
- `per_page` の上限を設ける
- 複数カテゴリをCSVまたは配列で受ける
- 数値条件は `*_max` として `<=` で検索する
- 互換パラメータを受ける: `child_category` -> `product_category`
- レスポンスを `{ data, meta }` にする
- `X-WP-Total`, `X-WP-TotalPages` ヘッダーを返す

古物台帳APIへの反映:

- `/wp-json/kobutsu/v1/items` は一覧専用として、次のパラメータを受ける
  - `page`
  - `per_page`
  - `q`
  - `sku`
  - `category`
  - `supplier`
  - `status`
  - `purchase_date_from`
  - `purchase_date_to`
  - `sale_date_from`
  - `sale_date_to`
  - `price_max`
  - `profit_min`
- レスポンス形式は `{ data, meta }` に寄せる
- Next.js側はこの `meta.total` を使って件数表示とページングを行う

## `muu-products-sample.php`

参考になる点:

- CPT + REST meta + taxonomy の設計が明確
- `register_post_meta` に `type`, `single`, `show_in_rest`, `auth_callback`, `sanitize_callback` を設定している
- 互換目的で `product_category` と `child_category` を両方扱っている
- 管理シートをtaxonomy、商品カテゴリをpost metaとして分けている
- メンテナンス用処理を `MUU_MAINT_MODE` で明示的に封じている

古物台帳APIへの反映:

- 今回はカスタムテーブル中心だが、設計思想は踏襲する
- 互換パラメータや旧CSV列名を受ける場合は、API境界で正規化する
- 入力値は必ず `sanitize_text_field`, `sanitize_key`, `absint` 相当で整える
- 管理・メンテナンス系APIは通常時に無効化できるフラグを設ける

## 方針

tools-hub はWordPress投稿/CPT寄り、古物台帳はカスタムテーブル寄りです。データ構造は違いますが、REST APIの使い勝手は合わせます。

優先して合わせるもの:

- route設計
- query parameter名
- pagination
- response shape
- frontendから扱いやすい `data/meta` 形式
- 互換パラメータの受け口

合わせないもの:

- 古物台帳データをCPTに戻すこと
- 大量明細をpost metaへ保存すること
- CSV原票や精算明細を投稿として扱うこと
