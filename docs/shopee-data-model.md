# Shopee Data Model Notes

Shopee CSV をそのままテーブル設計の正解にせず、業務で安定して使う軸を先に固めるためのメモです。

## 方針

- CSV再現より業務モデルを優先する
- `仕入れ表` と `仕入れ元データ` をコアにする
- `supplier_sources` を Shopee 系業務の主な正本として扱う
- `orders` と `payments` は外部原票として後から接続できる形にする
- `ec_sales` は保存テーブルではなく集計ビューとして扱う
- `sales`、`sales_settlements`、将来の台帳系テーブルは補助同期先として扱う
- 将来の列追加に耐えられるよう、コア項目と補助項目を分ける

## コアとなる業務軸

Shopee 連携でも、まず安定して扱いたいのは次の2系統です。

### 1. purchases

仕入れの事実を持つ主表です。

- `sku`
- `orderNo`
- `purchaseDate`
- `supplierName`
- `itemName`
- `purchasePriceJpy`
- `category`
- `condition`
- `listedAt`
- `soldAt`
- `marketplace`

### 2. supplier_sources

販売補助、発送、海外送料、運用メモを持つ原票です。

- `sku`
- `orderNo`
- `account`
- `soldAt`
- `purchasedAt`
- `country`
- `saleAmount`
- `shippingActualYen`
- `shippingNote`
- `packer`
- `shippingService`
- `weightG`
- `appliedWeightG`
- `lengthCm`
- `widthCm`
- `heightCm`
- `firstMailAt`
- `receiptPrintedAt`

## 外部原票

次のデータは CSV の揺れが比較的大きいため、主表に直接混ぜず補助テーブルとして扱います。

### orders

- Shopee の注文状態、購入者、配送、販売額の原票
- 国や時期で列名が揺れる前提で正規化レイヤーを挟む

### payments

- Payout、各種手数料、返金、物流控除の原票
- `orderId` 単位で結合するが、返金やキャンセルの負値行を許容する

### exchange_rates

- 日付 x 通貨の換算レート
- 販売時点レートと出金時点レートを別用途で参照する

## ec_sales の位置づけ

`ec_sales` は入力元ではなく、次のデータから組み立てるビューとして扱います。

- `purchases`
- `supplier_sources`
- `orders`
- `payments`
- `exchange_rates`

初期段階では `purchases + supplier_sources` だけでも成立する中間ビューにし、後から `orders` と `payments` を結合して精算情報を拡張します。

## 正本と補助同期先

Shopee 系の入力では、正本と表示・補助テーブルを明確に分ける。

### 正本

- `supplier_sources`
  - Shopee 運用で日常的に入力・修正する基礎情報
  - `SKU`、`OrderNo`、販売日、商品名、販売額、送料、追跡番号、仕入れ先などを持つ
- `payments`
  - 出金、手数料、返金、Payout の原票
- `exchange_rates`
  - 日別通貨別の換算レート原票

### 補助同期先

- `sales`
  - `supplier_sources` から EC販売表示に必要な販売行を構成するための補助テーブル
- `sales_settlements`
  - `payments` と手動補正値を保持するための補助テーブル
- `items`
  - SKU 単位の商品マスタ
- `purchases`
  - 古物台帳や仕入れ管理で必要な仕入れ事実を保持する補助テーブル
- 将来的な古物台帳向けテーブル
  - `supplier_sources` や `purchases` から必要項目を同期する

### 基本原則

- まず正本へ保存し、その後に補助同期先を更新する
- 表示用テーブルや集計用テーブルへ直接入力して正本化しない
- 同じ意味の値を複数テーブルへ無条件に複製しない
- 補助同期先は「検索しやすくする」「ビューを作りやすくする」ために持つ
- どの項目がどこから同期されたか説明できる状態を保つ

## EC販売ビューの正本整理

実シート確認の結果、`EC販売` は「仕入れ表ベースの一覧」ではなく、かなりの列が `仕入れ元データ` を正本にした集計ビューとして扱うのが自然です。

### 現時点での優先ソース

- `SKU` から `販売日` までの基本列は `supplier_sources` を正本にする
- `出金日`、`Payout金額`、各種手数料は `payments` を正本にする
- `商品名` から `販売金額` までの販売主要列も `supplier_sources` を正本にする
- `国内送料` も `supplier_sources` を正本にする
- `販売時の為替` と `出金時の為替` は `exchange_rates` を参照して補完する
- `広告費(PL)`、追跡番号など一部運用列は手動補正項目として別管理する

### 列グループごとの扱い

#### 1. supplier_sources 由来

- `sku`
- `orderNo`
- `soldAt`
- `purchasedAt`
- `itemName`
- `purchasePriceJpy`
- `saleAmount`
- `domesticShippingFee`
- `domesticShippingTaxRefund`
- `purchaseTaxRefund`
- `domesticTrackingNo`
- `slsTrackingNo`
- `packer`
- `weightG`
- `lengthCm`
- `widthCm`
- `heightCm`
- `supplierName`

#### 2. payments 由来

- `payoutAt`
- `payoutAmount`
- `totalFees`
- `commissionFee`
- `serviceFee`
- `transactionFee`
- `refundAmount`
- `releasedAmount`

#### 3. exchange_rates 由来

- `saleExchangeRate`
- `payoutExchangeRate`

#### 4. 手動・補助列

- `adFee`
- `combinedShippingFlag`
- `settlementNote`
- 補助的な運用メモ類

#### 5. 計算列

- `saleAmountJpy`
- `receivedAmountJpy`
- `profitJpy`
- `profitRate`
- `daysToSell`

### 実装上の含意

- `ec_sales` を保存テーブルにせず、`supplier_sources + payments + exchange_rates + manual_overrides` から生成する
- `purchases` は仕入れ管理UIや古物台帳連携の主表として残すが、`EC販売` の全列の正本にはしない
- `EC販売` の行更新で触る項目は、将来的に「どの正本テーブルへ書き戻すか」を項目ごとに分ける
- `売れるまでの日数` は `販売日 - 出品日` を基本計算値にしつつ、必要に応じて手動上書き値を保存できるようにする

### supplier_sources から EC販売 への反映ルール

`EC販売` が `仕入れ元データ` の影響を強く受けるため、`supplier_sources` 保存後に `EC販売` 側へ反映される構造を前提にする。

#### 推奨パターン

- 画面入力はまず `supplier_sources` に保存する
- 保存後、`sku` または `orderNo` をキーに `sales` を upsert する
- `payments` と `exchange_rates` があれば `sales_settlements` を再計算または補完する
- `EC販売` API は `sales + sales_settlements + supplier_sources` の合成結果を返す

#### 書き戻し先の考え方

- `supplier_sources` 正本の項目
  - `sku`
  - `orderNo`
  - `soldAt`
  - `purchasedAt`
  - `itemName`
  - `purchasePriceJpy`
  - `saleAmount`
  - `domesticShippingFee`
  - `domesticTrackingNo`
  - `slsTrackingNo`
- `payments` 正本の項目
  - `payoutAt`
  - `payoutAmount`
  - `totalFees`
  - 各種手数料
- `manual_overrides` 正本の項目
  - `daysToSellOverride`
  - `daysToSellOverrideNote`
  - `adFee`
  - 補助メモ

#### POST の考え方

- `POST /supplier-sources`
  - `supplier_sources` を保存するための入口
  - 必要に応じて `sales` の対応行を更新または新規作成する
- `POST /ec-sales/{saleId}`
  - `payments` 由来項目や手動補正項目を更新する入口
  - `supplier_sources` 正本の列を直接ここで更新しない構成も検討する

#### 注意点

- `EC販売` へ supplier由来の値を重複保存しすぎると、`supplier_sources` と `sales` と `sales_settlements` の三重管理になりやすい
- そのため、`EC販売テーブルへPOSTして複製する` より、`supplier_sources` 保存を起点に `EC販売ビューを再構成する` 方が整合性を保ちやすい
- どうしても補助保存が必要な列だけを `sales` または `manual_overrides` に持たせる

## supplier_sources から 古物台帳 への反映ルール

古物台帳も最終的には `supplier_sources` の影響を強く受ける前提で設計する。

### 反映の考え方

- `supplier_sources` 保存後、必要に応じて `items` と `purchases` を upsert する
- 古物台帳に必要な販売側情報があれば `sales` も同期する
- 古物台帳画面は、正本を直接編集するのではなく、同期済みテーブルを通じて表示する

### 期待する効果

- Shopee 運用で入力した内容が EC販売 と古物台帳の両方へ一貫して反映される
- 仕入れ・販売・精算の導線を 1 つの正本起点で説明できる
- 後から CSVインポートや一括同期を実装するときも、同期先が明確になる

## 正規化の優先順位

最初に正規化する対象は次の通りです。

1. join キー
   `sku`, `orderNo`, `orderId`
2. 日付
   `2026/3/9`, `2026-03-09`, `3/9`, `#N/A` などの揺れ
3. 通貨付き金額
   `¥6,550`, `₱3,361.00`, `SGD72.11`, `0.00E+00`
4. 業務状態
   `Cancelled`, `Shipping`, `Completed`, `有在庫`, `仕入れ無`, `キャンセル`
5. 国・通貨コード
   `フィリピン`, `シンガポール`, `PH`, `SG`, `PHP`, `SGD`

## 拡張しやすくするための考え方

- コア項目は少数に絞って固定する
- CSV依存が強い項目は補助項目として持つ
- 原票型と画面表示型を分ける
- `rawStatus`, `note`, `importMeta` のような受け皿を用意して、未知列や運用メモを逃がせるようにする
- いきなり完成形を作らず、中間ビューを許容する

## 実装の進め方

1. `frontend/types` にコア業務型、原票型、集計ビュー型を置く
2. `frontend/lib` に金額、日付、状態の正規化関数を置く
3. `purchases` と `supplier_sources` を優先して扱う
4. `orders` と `payments` は後から join できるように追加する
5. `ec_sales` はビュー生成関数として組み立てる
