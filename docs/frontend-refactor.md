# Frontend Refactor Plan

Next.jsフロントのJSX肥大化と型警告を減らすための作業方針です。

## 目的

- 画面コンポーネントから業務型、API変換、保存処理、表JSXを分離する。
- `any` や曖昧な `unknown` を画面側に持ち込まず、API境界で型を受け止める。
- 1回の作業で1責務だけを動かし、コミットとプッシュを小さく保つ。

## 現状

- `LedgerWorkspace.tsx` は全体レイアウト、タブ、サイドバー、古物台帳表示を担当する。
- `SupplierManagement.tsx` は仕入管理の画面に分離済みだが、まだフォームJSX、テーブルJSX、保存処理、状態管理が同居している。
- `frontend/types/supplier.ts` に仕入管理の型、`frontend/lib/supplierSources.ts` にサンプル・API変換・正規化処理を分離済み。

## 分離方針

### 1. 型

型は `frontend/types` に集約します。

- 画面表示用の型: `SupplierSource`
- REST APIレスポンス型: `SupplierSourceApiRow`
- REST API保存payload型: `SupplierSourceSubmitPayload`
- タブなどのUI選択肢型: `SupplierDataView`, `SupplierSourceView`

画面コンポーネントでは `Record<string, unknown>` や `any` を直接扱わず、API変換関数を経由します。

### 2. 変換・保存補助

業務データの変換、日付正規化、金額表示、upsertなどは `frontend/lib` に置きます。

- APIレスポンスから画面表示型への変換
- フォーム値から保存payloadへの変換
- SKU重複時の更新ルール
- 日付、金額、通貨の正規化

`SupplierManagement.tsx` には、変換の詳細ではなく「いつ読み込むか」「いつ保存するか」だけを残します。

### 3. JSXコンポーネント

大きなJSXは `frontend/app/components/supplier-management` 配下へ分けます。

候補:

- `SupplierManagementHeader.tsx`: 見出し、新規仕入れボタン、件数表示
- `SupplierSourceModal.tsx`: モーダル枠とフォーム送信
- `SupplierSourceForm.tsx`: 入力フォーム本体
- `SupplierSourceTabs.tsx`: 仕入元データ / 仕入れ表への反映のタブ
- `SupplierSourceTables.tsx`: 仕入元データの各テーブル
- `PurchaseProjectionTable.tsx`: 仕入れ表への反映テーブル

まずはフォームとテーブルを分け、状態管理は後続でhook化します。

### 4. 状態管理

フォーム状態、一覧状態、保存状態は最終的にhookへ寄せます。

候補:

- `useSupplierSources`: 読み込み、保存、ローカル反映、ステータスメッセージ
- `useSupplierSourceForm`: フォーム値更新、初期値、リセット

hook化はJSX分離後に行います。先にhook化すると、フォーム項目の分割と状態更新の境界が読みにくくなるためです。

## 作業順序

1. フォームJSXを `SupplierSourceForm.tsx` に分離する。
2. モーダル枠を `SupplierSourceModal.tsx` に分離する。
3. 仕入元データの3テーブルを `SupplierSourceTables.tsx` に分離する。
4. 仕入れ表への反映テーブルを `PurchaseProjectionTable.tsx` に分離する。
5. 保存payload生成を `frontend/lib/supplierSources.ts` に移す。
6. `useSupplierSources` と `useSupplierSourceForm` を作る。

各ステップは1コミットにします。

## 完了条件

- `SupplierManagement.tsx` が画面構成を読むための薄い親コンポーネントになっている。
- JSX警告がフォーム部品やテーブル部品の局所に閉じている。
- APIレスポンスの型変換が `lib` に閉じている。
- `npm run typecheck` と `npm run build` が通る。
