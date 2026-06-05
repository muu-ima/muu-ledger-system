# Column Dictionary

元スプレッドシートの列を解読するための作業メモです。

この文書では、CSVの列名、役割、推定される参照関係を整理します。元シートは数式参照や手動入力が混在しているため、ここに書く「参照元」は確定仕様ではなく、サンプルCSVとスクリーンショットからの現時点の推定です。

## 基本方針

- `SKU` / `カスタムラベル(SKU)` / `SKU（カスタムラベル）` を同じ管理番号として扱う。
- `Order no.` / `eBay Oder No,` は同じeBay注文番号として扱う。元データに `Oder` 表記の揺れがある。
- `purchases_sample.csv` は「仕入れ表」のエクスポートに近い。
- `supplier_master_sample.csv` は「仕入れ元データ」。仕入れ表の主要参照元で、発送・梱包・送料まで含む。
- `ledger_sample.csv` は「古物台帳」。受入れ・払出し・相手方情報を二段ヘッダーで持つ。
- `ec_sales_sample.csv` は、仕入れ表、仕入れ元データ、ペイメント、為替、手動入力を合成した集計表に近い。

## UI上の統一方針

元スプレッドシートでは `仕入れ表` と `仕入れ元データ` が別シートになっているが、列の重複と参照関係が強いため、アプリUIでは `仕入れ管理` として統一する。

CSV解読とインポート設計では元シート名を残し、画面上では以下のように扱う。

| 元シート/CSV | UI上の扱い |
| --- | --- |
| `purchases_sample.csv` / 仕入れ表 | `仕入れ管理` の補完・確認項目 |
| `supplier_master_sample.csv` / 仕入れ元データ | `仕入れ管理` の主要参照元 |
| `ledger_sample.csv` / 古物台帳 | `古物台帳` |
| `ec_sales_sample.csv` / EC販売 | `EC販売` |

## 主要キー

| 概念 | 列名の揺れ | 用途 | 注意 |
| --- | --- | --- | --- |
| 管理番号 | `SKU`, `カスタムラベル(SKU)`, `SKU（カスタムラベル）` | 商品・仕入れ・販売の結合キー | セル内改行入りのSKUがあるため正規化が必要 |
| eBay注文番号 | `Order no.`, `eBay Oder No,` | 販売・払出し・ペイメントの結合キー | `Oder` は元シート由来の表記揺れとして許容する |
| 仕入れ日 | `仕入れ日`, `仕入日`, `仕入れ年月日` | 受入れ日 | `YYYY/M/D` と `M/D` が混在する |
| 販売日 | `販売日`, `販売年月日` | 払出し日 | 年なしの日付は文脈から年補完が必要 |

## SKUの構造仮説

`SKU` / `カスタムラベル(SKU)` / `SKU（カスタムラベル）` は、単なる連番ではなく、入力者または担当者らしき情報を含む管理番号に見える。

例:

| SKU例 | 推定構成 |
| --- | --- |
| `20251125_mizushima_02` | `2025/11/25` + `mizushima` + `02` |
| `20251030_akai_01` | `2025/10/30` + `akai` + `01` |
| `20251112_mogi_25` | `2025/11/12` + `mogi` + `25` |
| `K20250421_watanabe_01` | 接頭辞 `K` + `2025/04/21` + `watanabe` + `01` |

このため、アプリ側では `sku` をそのまま保持しつつ、必要なら派生項目として `sku_date`, `sku_operator`, `sku_sequence`, `sku_prefix` を抽出できるようにする。

## purchases_sample.csv / 仕入れ表

ヘッダー行: 4行目  
データ開始行: 5行目

| 列 | CSV列名 | 推定フィールド | 種別 | 参照元候補 | メモ |
| --- | --- | --- | --- | --- | --- |
| A | `SKU` | `sku` | 参照/キー/入力者推定 | `仕入れ元データ` B | 管理番号。日付・入力者/担当者らしき文字列・連番を含む |
| B | `Order no.` | `order_no` | 参照 | `仕入れ元データ` C | スクショで `='仕入れ元データ'!C6` を確認 |
| C | `仕入れ日` | `purchase_date` | 参照 | `仕入れ元データ` F | サンプル値が一致。スクショ上も自動列 |
| D | `仕入れ先` | `supplier_name` | 参照 | `仕入れ元データ` V | 例: メルカリ、楽天市場。サンプル値が一致 |
| E | `仕入れ金額（送料込）` | `purchase_price_jpy` | 参照 | `仕入れ元データ` J | スクショとサンプル値から仕入れ元データ参照と推定 |
| F | `品目` | `category` | 手動 | 仕入れ表 | スクショ上は手動。プルダウンあり |
| G | `商品名` | `item_name` | 参照 | `仕入れ元データ` U | スクショで `='仕入れ元データ'!U6` を確認 |
| H | `付属品` | `accessories` | 手動 | 仕入れ表 | 空欄が多い |
| I | `状態` | `condition` | 手動 | 仕入れ表 | `新品`, `中古` |
| J | `備考` | `purchase_note` | 手動 | 仕入れ表 | 欠損・注意事項 |
| K | `写真` | `photo_storage` | 手動/定型 | 仕入れ表 | 例: `Amazon Photos` |
| L | `出品日` | `listed_at` | 手動/参照 | 仕入れ表 | 商品ページ出品日 |
| M | `販売日` | `sold_at` | 参照 | 仕入れ元データ or EC販売 | 払出し日と連動する可能性 |
| N | `販売先` | `marketplace` | 定型/参照 | 仕入れ表 | 例: `ebay` |
| O | `販売金額` | `sale_amount_raw` | 参照 | 仕入れ元データ or EC販売 | 通貨記号が混在する |

## supplier_master_sample.csv / 仕入れ元データ

ヘッダー行: 5行目  
データ開始行: 6行目

| 列 | CSV列名 | 推定フィールド | 種別 | メモ |
| --- | --- | --- | --- | --- |
| A | 無名 | `row_no` | 行番号 | 連番。業務キーではない |
| B | `SKU（カスタムラベル）` | `sku` | キー/入力者推定 | purchasesのA列候補。日付・入力者/担当者らしき文字列・連番を含む |
| C | `eBay Oder No,` | `order_no` | キー/参照 | purchasesのB列候補 |
| D | `アカウント` | `seller_account` | 運用 | signpost / tokyo.cairn など |
| E | `販売日` | `sold_at` | 販売 | purchasesのM列候補 |
| F | `仕入日` | `purchase_date` | 仕入 | purchasesのC列候補 |
| G | `国` | `buyer_country` | 販売/配送 | 古物台帳の買主国にも反映される可能性 |
| H | `MAG` | `mag_flag` | 運用 | MAG対象フラグと思われる |
| I | `販売額` | `sale_amount_raw` | 販売 | purchasesのO列候補 |
| J | `仕入れ（\）` | `purchase_price_jpy` | 仕入 | purchasesのE列候補 |
| K | `送料` | `shipping_actual_yen` | 配送/損益 | EC販売の海外送料候補 |
| L | `ポイント加算` | `points_note` | 損益補正 | 例: `541P` |
| M | `その他備考（追加料金等）` | `shipping_note` | 補正 | 関税・手数料・返送送料など |
| N | `梱包者` | `packer` | 運用 | 人名 + 日付の文字列 |
| O | `発送サイト` | `shipping_service` | 配送 | elogi / CPaSS / EMS など |
| P | `実重g` | `weight_g` | 配送 | 数値 |
| Q | `体積重g` | `applied_weight_g` | 配送 | 数値 |
| R | `cm` | `length_cm` | 配送 | 寸法1。元シートでは同名列 |
| S | `cm` | `width_cm` | 配送 | 寸法2。列位置で識別する |
| T | `cm` | `height_cm` | 配送 | 寸法3。列位置で識別する |
| U | `商品名` | `item_name` | 商品 | purchasesのG列参照元として確認済み |
| V | `仕入れ先` | `supplier_name` | 仕入 | purchasesのD列候補 |
| W | `初回メール` | `first_mail_at` | 運用 | 日付または空欄 |
| X | `領収書印刷` | `receipt_printed_at` | 運用 | 日付/メモ |

## ledger_sample.csv / 古物台帳

ヘッダー行: 3-4行目の二段構成  
データ開始行: 5行目

| 列 | 上段 | 下段 | 推定フィールド | 種別 | メモ |
| --- | --- | --- | --- | --- | --- |
| A | `仕入れ年月日` |  | `purchase_date` | 受入れ | purchasesのC列と同義 |
| B | `SKU` |  | `sku` | キー/入力者推定 | purchasesのA列と同義。日付・入力者/担当者らしき文字列・連番を含む |
| C | `区別` |  | `purchase_type` | 受入れ | 例: `買受` |
| D | `取引した古物` | `品目` | `category` | 商品 | purchasesのF列と同義 |
| E |  | `商品名` | `item_name` | 商品 | purchasesのG列と同義 |
| F |  | `数量` | `quantity` | 商品 | ほぼ `1` |
| G |  | `代価` | `purchase_price_jpy` | 受入れ | purchasesのE列と同義 |
| H |  | `区分` | `supplier_name` | 受入れ | 仕入れ先/購入チャネル |
| I | `確認方法 取引ID` |  | `seller_identification` | 本人確認 | 例: 本人確認済み |
| J | `取引きの相手方` | `住所` | `seller_address` | 相手方 | 空欄が多い |
| K |  | `氏名` | `seller_name` | 相手方 | 空欄が多い |
| L |  | `年齢` | `seller_age` | 相手方 | 空欄が多い |
| M |  | `職業` | `seller_occupation` | 相手方 | 空欄が多い |
| N | `販売年月日` |  | `sold_at` | 払出し | purchasesのM列と同義 |
| O | `区別` |  | `sale_type` | 払出し | 例: `売却` |
| P | `代価` |  | `sale_amount_raw` | 払出し | purchasesのO列と同義 |
| Q | `販売先` |  | `marketplace` | 払出し | 例: `ebay` |
| R | `確認方法 取引ID` |  | `order_no` | 払出し | eBay注文番号 |
| S | `取引きの相手方` | `国名` | `buyer_country` | 買主 | supplierのG列と同義の可能性 |
| T |  | `buyer ID` | `buyer_id` | 買主 | eBay buyer ID |
| U |  | `氏名` | `buyer_name` | 買主 | `#N/A` が混在 |
| V |  | `市` | `buyer_city` | 買主 | `#N/A` が混在 |
| W |  | `州` | `buyer_state` | 買主 | `#N/A` が混在 |
| X |  | `郵便番号` | `buyer_postal_code` | 買主 | `#N/A` が混在 |
| Y |  | `address1` | `buyer_address1` | 買主 | 送付先住所 |
| Z |  | `address2` | `buyer_address2` | 買主 | 送付先住所 |
| AA |  | `address3` | `buyer_address3` | 買主 | 送付先住所 |

## ec_sales_sample.csv / EC販売

ヘッダー行: 4行目  
参照元メモ行: 3行目  
データ開始行: 5行目

| 列 | CSV列名 | 参照元メモ | 推定フィールド | 種別 |
| --- | --- | --- | --- | --- |
| A | `同梱` |  | `combined_shipping_flag` | 手動/運用 |
| B | `カスタムラベル(SKU)` | `仕入れ表` | `sku` | 参照 |
| C | `eBay Oder No,` | `仕入れ元データ` | `order_no` | 参照 |
| D | `仕入れ日` | `仕入れ表` | `purchase_date` | 参照 |
| E | `販売日` |  | `sold_at` | 参照/販売 |
| F | `出金日` | `ペイメント` | `payout_at` | 参照 |
| G | `商品名` | `仕入れ表` | `item_name` | 参照 |
| H | `仕入れ金額(送料込み)` | `仕入れ表` | `purchase_price_jpy` | 参照 |
| I | `販売金額` |  | `sale_amount_raw` | 参照/販売 |
| J | `販売金額（￥）` |  | `sale_amount_jpy` | 計算 |
| K | `Total fees` | `ペイメント` | `total_fees_raw` | 参照 |
| L | `広告費(PL)` | `手動` | `ad_fee_raw` | 手動 |
| M | `ebay手数料` |  | `ebay_fee_raw` | 計算/参照 |
| N | `Payout金額` |  | `payout_amount_raw` | 計算/参照 |
| O | `販売時の為替` | `売れた日の為替` | `sale_exchange_rate` | 参照 |
| P | `出金時の為替（ペイオニア手数料含む）` |  | `payout_exchange_rate` | 参照/計算 |
| Q | `受取金額` |  | `received_jpy` | 計算 |
| R | `海外送料` | `仕入れ元データ` | `shipping_actual_yen` | 参照 |
| S | `手数料消費税還付` |  | `fee_tax_refund_jpy` | 計算 |
| T | `消費税還付` |  | `purchase_tax_refund_jpy` | 計算 |
| U | `最終損益` |  | `profit_jpy` | 計算 |
| V | `利益率` |  | `profit_rate` | 計算 |
| W | `売れるまでの日数` |  | `days_to_sell` | 計算 |
| X | `送り状番号` | `手動` | `tracking_no` | 手動 |
| Y | `備考` |  | `settlement_note` | 手動/補足 |

## purchases_sample.csv への反映仮説

| purchases列 | 反映元候補 | 根拠 |
| --- | --- | --- |
| `SKU` | `supplier_master_sample.csv` B | 全表で一致する管理番号。入力者/担当者らしき文字列も含む |
| `Order no.` | `supplier_master_sample.csv` C | スクショで `='仕入れ元データ'!C6` を確認 |
| `仕入れ日` | `supplier_master_sample.csv` F | サンプル値が一致。仕入れ表では自動列 |
| `仕入れ先` | `supplier_master_sample.csv` V | サンプル値が一致 |
| `仕入れ金額（送料込）` | `supplier_master_sample.csv` J | スクショとサンプル値から仕入れ元データ参照と推定 |
| `品目` | `ledger_sample.csv` D or 手動 | 仕入れ元データには品目がない |
| `商品名` | `supplier_master_sample.csv` U | スクショで `='仕入れ元データ'!U6` を確認 |
| `付属品` | 手動 | 対応列が見当たらない |
| `状態` | 手動 | 対応列が見当たらない。`新品` / `中古` |
| `備考` | 手動 or `supplier_master_sample.csv` M | 追加料金備考とは意味が違う可能性 |
| `写真` | 手動/定型 | `Amazon Photos` 固定に近い |
| `出品日` | 手動/別シート | 仕入れ元データには直接対応なし |
| `販売日` | `supplier_master_sample.csv` E / `ledger_sample.csv` N | サンプル値が一致 |
| `販売先` | 定型 or `ledger_sample.csv` Q | 例: `ebay` |
| `販売金額` | `supplier_master_sample.csv` I / `ledger_sample.csv` P | 通貨付き販売額 |

## インポート時の注意

- 空行、参照元メモ行、二段ヘッダーをスキップ/合成する必要がある。
- `#VALUE!`, `#REF!`, `#N/A` は文字列として保持しつつ、正規化後はエラー状態にも分離する。
- `キャンセル` は送料欄や販売行に入るため、金額パース失敗ではなく業務状態として扱う。
- 通貨は `¥`, `$`, `AU$`, `AUD`, `€`, `£`, `￡`, `c$` が混在する。
- 日付は `2026/1/6`, `2026/01/15`, `1/17`, `3.23` などが混在する。
- 同名列 `cm` は列位置で `length_cm`, `width_cm`, `height_cm` に割り当てる。
