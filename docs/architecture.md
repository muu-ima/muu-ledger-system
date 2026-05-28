# Architecture

## 方針

WordPress を台帳データと管理権限のバックエンド、Next.js を日常入力に使うフロントエンドとして分けています。

## サービス

- MySQL: WordPress データベース
- WordPress: REST API と管理画面
- Next.js: 古物台帳 UI

## UI方針

既存運用はGoogleスプレッドシートの横長台帳です。Next.jsの主画面も、カード型ダッシュボードではなく、固定ヘッダー付きの表形式UIを中心にします。

詳細は `docs/ui-reference.md` を参照してください。

## データモデル初期案

サンプルCSVをもとに、WordPress のカスタムテーブルとして次のテーブルを作ります。

- `wp_kobutsu_suppliers`: 仕入先マスタ
- `wp_kobutsu_items`: SKU単位の商品
- `wp_kobutsu_purchases`: 受入れ、仕入、取引相手方
- `wp_kobutsu_sales`: 払出し、販売、買主、配送
- `wp_kobutsu_sales_settlements`: eBay販売の精算、手数料、損益
- `wp_kobutsu_payment_transactions`: eBay/Payoneerの入金・手数料原明細
- `wp_kobutsu_exchange_rates`: 日別通貨別の円換算レート
- `wp_kobutsu_import_batches`: CSV取込履歴

## CSV対応

| CSV | 主な取込先 | 用途 |
| --- | --- | --- |
| `supplier_master_sample.csv` | `suppliers`, `sales` | SKU、注文番号、販売日、送料、梱包、発送サイト、仕入先 |
| `purchases_sample.csv` | `items`, `purchases`, `sales` | 仕入日、仕入先、品目、商品名、付属品、状態、出品日、販売日 |
| `ledger_sample.csv` | `items`, `purchases`, `sales` | 古物台帳の受入れ・払出し、本人確認、相手方、買主住所 |
| `ec_sales_sample.csv` | `sales_settlements` | 販売額、手数料、為替、受取額、送料、還付、損益 |
| `sales_payments_sample.csv` | `payment_transactions` | eBay/Payoneerの入金、手数料、Payout、トランザクション原票 |
| `exchange_rates_sample.csv` | `exchange_rates` | みずほ銀行ヒストリカルデータ由来の日別為替 |

## 古物台帳として重要な項目

- 受入れ: 仕入年月日、区別、品目、商品名、数量、代価、仕入先、本人確認、相手方情報
- 払出し: 販売年月日、区別、代価、販売先、取引ID、国名、buyer ID、氏名、住所
- 運用: 元CSV、取込日時、エラー行、後から修正した履歴

運用時は都道府県公安委員会や顧問専門家の確認に合わせて、必須項目と保存期間を固めてください。
