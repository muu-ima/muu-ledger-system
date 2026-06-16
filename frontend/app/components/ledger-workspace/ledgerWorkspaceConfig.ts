export const tabs = [
  "古物台帳",
  "仕入れ管理",
  "EC販売",
  "為替レート",
  "ペイメント",
  "Shopeeオーダー",
] as const;

export type WorkspaceTab = (typeof tabs)[number];

export const tabDescriptions: Record<WorkspaceTab, string> = {
  古物台帳: "仕入表と仕入れ元データを集約した現場台帳ビュー",
  仕入れ管理: "仕入れ元データと仕入れ表を統合した管理ビュー",
  EC販売: "販売、精算、送料、損益を確認するビュー",
  為替レート: "販売日と出金日の換算レートを確認するビュー",
  ペイメント: "入金、手数料、Payoutを確認するビュー",
  Shopeeオーダー: "Shopeeで受け付けた注文原票を確認するビュー",
};

export const categories = [
  "フィギュア",
  "ラジコン",
  "プラモデル",
  "模型",
  "ホビー",
  "ゲームソフト",
  "ぬいぐるみ",
  "楽器",
  "腕時計",
  "掛け時計",
  "電化製品",
  "生活家電",
  "日用雑貨",
  "リール",
  "カメラ",
  "車用品",
  "時計",
  "その他",
] as const;

export const supplierOptions = [
  "メルカリ",
  "メルカリショップ",
  "楽天市場",
  "Amazon",
  "yahooフリマ",
  "トレファク",
] as const;
