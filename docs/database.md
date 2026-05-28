# Database

`docs/sample-data/` の参考資料から作った初期データベース設計です。

## Tables

### `wp_kobutsu_suppliers`

仕入先マスタです。メルカリ、楽天市場、ショップ名などを集約します。

主な項目: `supplier_name`, `supplier_code`, `channel`, `identification_method`, `address`, `contact`, `notes`

### `wp_kobutsu_items`

SKU単位の商品マスタです。1 SKU = 1台帳対象を基本にします。

主な項目: `sku`, `item_name`, `category`, `quantity`, `condition_label`, `accessories`, `description`, `photo_url`, `status`

### `wp_kobutsu_purchases`

古物台帳の「受入れ」と仕入管理です。

主な項目: `item_id`, `supplier_id`, `purchase_date`, `transaction_type`, `purchase_price_jpy`, `seller_identification`, `seller_address`, `seller_name`, `seller_age`, `seller_occupation`

### `wp_kobutsu_sales`

古物台帳の「払出し」と販売・配送管理です。

主な項目: `item_id`, `marketplace`, `account_name`, `order_no`, `sale_date`, `sale_amount`, `sale_currency`, `buyer_country`, `buyer_id`, `buyer_name`, `buyer_city`, `buyer_state`, `buyer_postal_code`, `buyer_address1`, `tracking_no`

### `wp_kobutsu_sales_settlements`

EC販売表の損益計算部分です。

主な項目: `order_no`, `payout_date`, `total_fees`, `ad_fee`, `ebay_fee`, `payout_amount`, `sale_exchange_rate`, `payout_exchange_rate`, `received_amount_jpy`, `overseas_shipping_jpy`, `profit_jpy`, `profit_rate`

### `wp_kobutsu_payment_transactions`

eBay/Payoneerの支払明細を原票として保存します。CSVの列が多く変化しやすいため、主要列に加えて `raw_payload` も持ちます。

### `wp_kobutsu_exchange_rates`

日付と通貨ごとの円換算レートです。`rate_date` と `currency_code` の組み合わせを一意にします。

### `wp_kobutsu_import_batches`

CSV取込履歴です。どのCSVをいつ何行取り込んだか、何行エラーになったかを残します。

## API

- `GET /wp-json/kobutsu/v1/schema`: テーブル概要
- `GET /wp-json/kobutsu/v1/items`: 商品、仕入、販売を結合した一覧
- `GET /wp-json/kobutsu/v1/items/{id}`: 1件取得
- `POST /wp-json/kobutsu/v1/items`: 1件登録

次の段階ではCSVインポートAPIを追加し、サンプルCSVの先頭数行にある注釈行をスキップして、実ヘッダー行から読み込めるようにします。
