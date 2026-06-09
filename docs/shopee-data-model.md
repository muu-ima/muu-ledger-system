# Shopee Data Model Notes

Shopee CSV をそのままテーブル設計の正解にせず、業務で安定して使う軸を先に固めるためのメモです。

## 方針

- CSV再現より業務モデルを優先する
- `仕入れ表` と `仕入れ元データ` をコアにする
- `orders` と `payments` は外部原票として後から接続できる形にする
- `ec_sales` は保存テーブルではなく集計ビューとして扱う
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
