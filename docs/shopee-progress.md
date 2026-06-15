# Shopee Work Progress

Shopee 系データモデルと管理画面実装の進捗メモです。  
方針そのものは [shopee-data-model.md](./shopee-data-model.md) に寄せ、こちらは「どこまで作ったか」を残します。

## 現在の前提

- ブランチ: `feature/shopee-data-model`
- ベースの考え方:
  - `supplier_sources` を Shopee 系業務の正本にする
  - `items`、`purchases`、`sales` は補助同期先にする
  - `EC販売` は保存テーブルではなく合成ビューとして扱う

## 実装済み

### 1. ドキュメントと型

- `docs/shopee-data-model.md`
  - Shopee 系の正本、補助同期先、EC販売の位置づけを整理
- `frontend/types/ecSales.ts`
  - EC販売、Shopee 注文、支払、為替、仕入れ関連の型を追加
- `frontend/types/supplier.ts`
  - `suppliers.csv` をベースにした `supplier_sources` 用の型を拡張

### 2. EC販売

- `frontend/lib/ecSales.ts`
  - 正規化と集計ビュー生成の下地を追加
- `frontend/app/components/EcSalesWorkspace.tsx`
  - EC販売タブ、集計サブタブ、検索、ステータスタブ、行内更新を追加
- `frontend/app/components/ec-sales/EcSalesTable.tsx`
  - 行ごとの `更新` ボタンで保存できる UI を追加
- `wordpress/plugins/kobutsu-ledger-api/admin-ec-sales.php`
  - `purchase_date ?? acquired_at_raw` の表示フォールバック
  - `days_to_sell` の計算フォールバック
- REST API
  - `GET /kobutsu/v1/ec-sales`
  - `POST /kobutsu/v1/ec-sales/{saleId}`

### 3. 仕入れ元データ

- `wordpress/plugins/kobutsu-ledger-api/kobutsu-ledger-api.php`
  - `supplier_sources` 保存 API を追加
  - `source_row_no` は手入力ではなく自動採番
  - `purchased_flag`、`size_memo` など Shopee シート寄りの列を追加
  - 通貨パーサで `JPY`、`USD`、`PHP`、`SGD`、`AUD`、`CAD`、`GBP`、`EUR`、`BRL` を許容
- `frontend/app/components/supplier-management/form/SupplierSourceFormSections.tsx`
  - 新規仕入れフォームを `suppliers.csv` ベースに寄せた
  - `true/false` 系はチェックボックス化
  - `No.` はフォーム入力から外した
- `acquiredAt`
  - `日付入力` と `有在庫` を分けて扱う
  - ISO日付は `acquired_at`、それ以外は `acquired_at_raw` に保持

### 4. 補助同期

- `supplier_sources` 保存後に `items`、`purchases`、`sales` を upsert
- `EC販売` 側では `supplier_sources` の影響を強く受ける構造に調整
- `古物台帳` も最終的に `supplier_sources` の影響を強く受ける前提で設計中

### 5. 仕入れ表への反映 UI

フォームに手動列を増やしすぎないため、`仕入れ表への反映` を UI テーブル編集へ寄せています。

- `frontend/app/components/supplier-management/tables/PurchaseProjectionTable.tsx`
  - 行ごとの `更新` ボタンを追加
  - 手動列のインライン編集を追加
- `frontend/app/components/supplier-management/hooks/usePurchaseProjectionRows.ts`
  - `supplier_sources` と `items` を SKU ベースで合成
  - 更新後の再反映を担当
- `wordpress/plugins/kobutsu-ledger-api/kobutsu-ledger-api.php`
  - `POST /kobutsu/v1/items/{id}` 相当の更新 REST を追加

現時点でテーブル編集対象にしている主な手動列:

- `品目`
- `付属品`
- `状態`
- `備考`
- `写真`
- `販売先`

### 6. 為替レート

販売時/出金時の為替補完に向けて、`exchange_rates` を取得元つきの原票として扱う形に整理しました。

- `wp_kobutsu_exchange_rates`
  - `rate_date`, `base_currency`, `quote_currency`, `source` の組み合わせで保存
  - `manual`, `exchangerate_api`, 将来の `mizuho` などを併存できる
  - 既存互換の `currency_code`, `rate_jpy` は残しつつ、新規処理では `base_currency`, `quote_currency`, `rate` を優先
- WordPress 管理画面
  - ExchangeRate-API key の保存
  - 当日分の手動取得
  - WP-Cron による日次自動取得
  - 手入力レートの保存
  - 手入力固定行を自動取得で上書きしない運用
- EC販売補完
  - `sale_exchange_rate` は販売日 + 販売通貨から空欄時に補完
  - `payout_exchange_rate` は出金日 + 出金通貨がある場合だけ空欄時に補完
  - `sale_amount_jpy` は販売額と販売時為替から自動計算
  - `received_amount_jpy` は Payoneer/Shopee 明細が入るまで過度に自動計算しない
- Web UI
  - `為替レート` 画面で保存済みレートを表示
  - `レート一覧` / `取得状況` のタブを追加
  - 取得元フィルタ、検索、ページネーションを追加
  - 適用優先順位、最新レート日、保存済み件数、取得元内訳を表示

適用優先順位は `manual > exchangerate_api > その他` とします。

### 7. ペイメント原票

Shopee payments CSV は、まず原票として取り込み保存し、損益計算への接続は後段に分ける方針です。

- WordPress 管理画面
  - Shopee payments CSV 取り込み
  - ヘッダー行検出
  - 金額表記の正規化
  - 重複スキップ
  - スキップ理由の内訳表示
  - `import_batches` への取り込み履歴保存
- Web UI
  - `ペイメント` 画面で原票と取り込み履歴を表示
  - タブ、フィルタ、検索、ページネーションを追加

## 運用上の整理

### 入力の主な流れ

1. `新規仕入れ` フォームで `supplier_sources` を保存する
2. 保存時に `items`、`purchases`、`sales` へ補助同期する
3. `仕入れ表への反映` では、手動列だけを UI テーブル上で修正して行更新する
4. `EC販売` は合成ビューとして参照し、必要な一部列のみ保存 API で更新する

### 現時点の判断

- `EC販売` テーブルへ直接 POST して元データを複製する方式は採らない
- `supplier_sources` を起点に依存テーブルを同期する
- `売れるまでの日数` は計算ベースを保ちつつ、将来的に手動補正値を持てる余地を残す

## 次の候補

- `出品日` の正本をどこに置くか決めて保存対応する
- `仕入れ表` の未対応手動列をどこまで UI テーブル編集に寄せるか決める
- 実データ投入を進めて round-trip を確認する
- `payments` の手数料、Payout、出金日を `sales_settlements` へ段階的に補完する
- 過去分の為替レート補完取得を追加する
- `received_amount_jpy`、Payout、損益計算への接続を進める
