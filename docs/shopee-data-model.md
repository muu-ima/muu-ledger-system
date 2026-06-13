# Shopee Data Model Notes

Shopee CSV をそのままテーブル設計の正解にせず、業務で安定して使う軸を先に固めるためのメモです。

## 方針

- CSV再現より業務モデルを優先する
- 既存シートは入力、原票、計算、手動補正が混在しているため、そのまま画面やDBへ写さない
- `仕入れ表` と `仕入れ元データ` をコアにする
- `supplier_sources` を Shopee 系業務の主な正本として扱う
- `orders` と `payments` は外部原票として後から接続できる形にする
- `ec_sales` は保存テーブルではなく集計ビューとして扱う
- `sales`、`sales_settlements`、将来の台帳系テーブルは補助同期先として扱う
- 将来の列追加に耐えられるよう、コア項目と補助項目を分ける

## 段階的な整理方針

既存の `EC販売` シートは業務内容そのものが複雑というより、複数の役割が1枚に混ざっていることで複雑化している。実装では次の順番で、確認できる単位ごとに進める。

1. `exchange_rates` で販売時/出金時の為替を補完する
2. `sale_amount_jpy` は販売額と販売時為替から自動計算する
3. `received_amount_jpy` は Payoneer/Shopee 明細が入るまで過度に自動計算しない
4. `payments` はまず原票として取り込み保存する
5. その後に手数料、Payout、損益計算へ接続する

## 実装状況

2026-06-10 時点では、方針のうち次の部分まで実装が進んでいます。

- `supplier_sources` を Shopee 系入力の正本として保存する流れ
- `supplier_sources` 保存後に `items`、`purchases`、`sales` を補助同期する流れ
- `EC販売` を `sales + sales_settlements + supplier_sources` の合成ビューとして返す流れ
- `仕入れ管理` では `仕入れ元データ` を新規フォームで保存し、`仕入れ表への反映` は UI テーブル上で手動列を更新する流れ

また、`仕入れ表への反映` 側の手動列は、フォームに無理に寄せずテーブル上で行単位更新する方針に寄せています。

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

#### Shopee payments CSV の扱い

`docs/shopee-sample/payments.csv` は Shopee の支払・精算明細として扱う。

- 1行目は集計行のため、明細ヘッダーとしては使わない
- 2行目の `Order ID` から始まる行をヘッダーとして扱う
- 3行目以降を注文単位の精算明細として取り込む
- 将来の列追加や先頭行の揺れに備え、固定行番号ではなく `Order ID` を含むヘッダー行を検出する

主に使う列:

- `Order ID`
- `Username (Buyer)`
- `Order Creation Date`
- `Buyer Payment Method`
- `Payout Completed Date支払い完了日`
- `Original Product Price`
- `Refund Amount`
- `Shipping Fee Rebate From Shopee Shopeeの配送料還元について`
- `3rd Party Logistics - Defined Shipping Fee3PL（サードパーティ・ロジスティクス） - 固定配送料`
- `Commission fee手数料`
- `Service Feeサービス料`
- `Transaction Fee取引手数料`
- `Total Released Amount (₱)払出済総額（₱補助金や融資などの文脈で、実際に支払われた金額の合計を指します＝N列のペイアウト`
- `Cash refund to buyer amount購入者への現金返金額`

考察:

- Shopee payments CSV は、既存の `EC販売` シートより原票としての役割が明確で、表崩れしにくい
- 金額列には `-₱48.00`、`₱0.00`、`-5.27E+04` のような表記揺れがあるため、取り込み時に通貨記号、カンマ、指数表記を正規化する
- `Total Released Amount` は出金・払出額の基礎値として使えるが、すぐに `received_amount_jpy` へ直結せず、まず原票保存を優先する
- `Commission fee`、`Service Fee`、`Transaction Fee` は損益計算へ接続できるが、初期段階では保存だけ行い、後続ステップで `sales_settlements` に補完する
- 返金やキャンセルは負値行を許容し、通常販売行と同じ `orderId` で結合できるようにする

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

### 現在の EC販売 UI 更新方針

2026-06 時点の UI では、`EC販売` 一覧から見える列をそのまま全部更新可能にはしない。

理由:

- `EC販売` は合成ビューであり、`supplier_sources` や `purchases` を正本に持つ列が混ざっている
- ここで全部を書き換え可能にすると、`仕入れ元データ` 起点の同期方針と矛盾しやすい
- どの画面でどの値を直すべきかが曖昧になる

#### EC販売 UI で更新可能にする列

- `orderNo`
- `soldAt`
- `payoutAt`
- `saleAmountRaw`
- `adFeeRaw`
- `marketplaceFeeRaw`
- `payoutAmountRaw`
- `saleExchangeRate`
- `payoutExchangeRate`
- `receivedAmountJpy`
- `overseasShippingYen`
- `feeTaxRefundJpy`
- `purchaseTaxRefundJpy`
- `profitJpy`
- `profitRate`
- `daysToSell`
- `domesticTrackingNo`
- `slsTrackingNo`

#### EC販売 UI で更新しない列

- `sku`
- `itemName`
- `purchaseDate`
- `listedAt`
- `purchasePriceJpy`
- `saleAmountJpy`
- `totalFeesRaw`
- その他、`supplier_sources` / `purchases` / 計算結果をそのまま表示している列

#### 運用上の考え方

- `仕入れ元データ` を正本にする列は、原則として `仕入れ管理` 側で直す
- `EC販売` 側では、販売・精算・補正値として意味がある列を中心に更新する
- 将来的に列ごとの書き戻し先を整理できたら、更新対象を段階的に広げる

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
