# Database

`docs/sample-data/` の参考資料から作った初期データベース設計です。

## Tables

### `wp_kobutsu_suppliers`

仕入先マスタです。メルカリ、楽天市場、ショップ名などを集約します。

主な項目: `supplier_name`, `supplier_code`, `channel`, `identification_method`, `address`, `contact`, `notes`

### `wp_kobutsu_supplier_sources`

仕入れ管理の原票テーブルです。`supplier_master_sample.csv` / 仕入れ元データの列を保持します。古物台帳への反映前データとして独立して保存し、フォームの簡易入力と詳細入力は同じテーブルを使います。

`sku` は一意キーです。同じSKUで登録した場合は新規行を追加せず、既存の仕入れ管理原票を更新します。別行として追加したい場合はSKUを変える必要があります。

主な項目: `sku`, `order_no`, `account_name`, `sold_at`, `acquired_at`, `buyer_country`, `sale_amount`, `sale_currency`, `purchase_price_jpy`, `shipping_cost_jpy`, `packer`, `shipping_site`, `actual_weight_g`, `dimensional_weight_g`, `package_length_cm`, `package_width_cm`, `package_height_cm`, `item_name`, `supplier_name_raw`

### `wp_kobutsu_items`

SKU単位の商品マスタです。1 SKU = 1台帳対象を基本にします。

主な項目: `sku`, `item_name`, `category`, `quantity`, `condition_label`, `accessories`, `description`, `photo_url`, `status`

### `wp_kobutsu_purchases`

古物台帳の「受入れ」です。仕入れ管理の原票は `wp_kobutsu_supplier_sources` に保存し、古物台帳へ反映する段階でこのテーブルに入れます。

主な項目: `item_id`, `supplier_id`, `purchase_date`, `transaction_type`, `purchase_price_jpy`, `seller_identification`, `seller_address`, `seller_name`, `seller_age`, `seller_occupation`

### `wp_kobutsu_sales`

古物台帳の「払出し」と販売・配送管理です。

主な項目: `item_id`, `marketplace`, `account_name`, `order_no`, `sale_date`, `sale_amount`, `sale_currency`, `buyer_country`, `buyer_id`, `buyer_name`, `buyer_city`, `buyer_state`, `buyer_postal_code`, `buyer_address1`, `tracking_no`

### `wp_kobutsu_sales_settlements`

EC販売表の損益計算部分です。

主な項目: `order_no`, `payout_date`, `total_fees`, `ad_fee`, `ebay_fee`, `payout_amount`, `payout_currency`, `sale_exchange_rate`, `payout_exchange_rate`, `received_amount_jpy`, `overseas_shipping_jpy`, `profit_jpy`, `profit_rate`

### `wp_kobutsu_payment_transactions`

ECモールの支払明細を原票として保存します。Shopee payments CSV は `transaction_type = shopee_payment` として取り込み、主要列に加えて `raw_payload` に元行を保持します。CSVの列が多く変化しやすいため、初期段階では計算へ直結せず、原票保存と一覧確認を優先します。

### `wp_kobutsu_exchange_rates`

日付、通貨ペア、取得元ごとの換算レートです。みずほ、ExchangeRate-API、手入力補正を同じ日付・通貨ペアで併存できるように、`rate_date`, `base_currency`, `quote_currency`, `source` の組み合わせを一意にします。

既存の円換算用途との互換性のため、`currency_code` と `rate_jpy` も保持します。新規処理では `base_currency`, `quote_currency`, `rate` を優先して参照します。手入力で固定したレートは `is_manual_override` を立て、自動取得では上書きしない運用にします。

### `wp_kobutsu_import_batches`

CSV取込履歴です。どのCSVをいつ何行取り込んだか、何行エラーになったかを残します。
Shopee payments CSV では `source_name = shopee_payments` として、保存件数、スキップ件数、スキップ内訳を `notes` に保持します。

## API

- `GET /wp-json/kobutsu/v1/schema`: テーブル概要
- `GET /wp-json/kobutsu/v1/supplier-sources`: 仕入れ管理原票の一覧
- `POST /wp-json/kobutsu/v1/supplier-sources`: 仕入れ管理原票の登録。同じSKUは更新扱い
- `GET /wp-json/kobutsu/v1/items`: 商品、仕入、販売を結合した一覧
- `GET /wp-json/kobutsu/v1/items/{id}`: 1件取得
- `POST /wp-json/kobutsu/v1/items`: 1件登録
- `GET /wp-json/kobutsu/v1/ec-sales`: EC販売の合成ビュー
- `POST /wp-json/kobutsu/v1/ec-sales/{id}`: EC販売の販売・精算補助列を更新
- `GET /wp-json/kobutsu/v1/payments`: Shopee ペイメント原票と取り込み履歴
- `GET /wp-json/kobutsu/v1/exchange-rates`: 保存済み為替レートと取得状況

次の段階ではCSVインポートAPIを追加し、サンプルCSVの先頭数行にある注釈行をスキップして、実ヘッダー行から読み込めるようにします。
