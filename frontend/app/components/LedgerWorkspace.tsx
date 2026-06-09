"use client";

import { useEffect, useMemo, useState } from "react";
import { LedgerRecordSections } from "@/app/components/ledger-workspace/LedgerRecordSections";
import SupplierManagement from "@/app/components/SupplierManagement";
import type { LedgerItem } from "@/types/ledger";

const tabs = [
  "古物台帳",
  "仕入れ管理",
  "EC販売",
  "為替レート",
  "ペイメント",
] as const;

type WorkspaceTab = (typeof tabs)[number];

const tabDescriptions: Record<WorkspaceTab, string> = {
  古物台帳: "受入れ、払出し、相手方・確認に分けた台帳ビュー",
  仕入れ管理: "仕入れ元データと仕入れ表を統合した管理ビュー",
  EC販売: "販売、精算、送料、損益を確認するビュー",
  為替レート: "販売日と出金日の換算レートを確認するビュー",
  ペイメント: "入金、手数料、Payoutを確認するビュー",
};

const categories = [
  "カメラ",
  "フィギュア",
  "プラモデル",
  "ラジコン",
  "ゲームソフト",
  "ホビー",
  "車用品",
  "時計",
  "その他",
];

const supplierOptions = [
  "メルカリ",
  "メルカリショップ",
  "楽天市場",
  "Amazon",
  "yahooフリマ",
  "トレファク",
];

export default function LedgerWorkspace({ items }: { items: LedgerItem[] }) {
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [query, setQuery] = useState("");
  const [activeTab, setActiveTab] = useState<WorkspaceTab>("古物台帳");

  useEffect(() => {
    const saved = window.localStorage.getItem("kobutsu:sidebar-open");
    if (saved) setSidebarOpen(saved === "1");
  }, []);

  useEffect(() => {
    window.localStorage.setItem("kobutsu:sidebar-open", sidebarOpen ? "1" : "0");
  }, [sidebarOpen]);

  const visibleItems = useMemo(() => {
    const needle = query.trim().toLowerCase();
    if (!needle) return items;

    return items.filter((item) =>
      [
        item.managementNo,
        item.category,
        item.itemName,
        item.acquiredFrom,
        item.soldTo,
      ]
        .join(" ")
        .toLowerCase()
        .includes(needle),
    );
  }, [items, query]);

  const resultCount = visibleItems.length;

  return (
    <div className="workspace">
      <header className="appHeader">
        <div>
          <div className="brand">Kobutsu Ledger</div>
          <p>古物台帳・EC販売・仕入れ管理</p>
        </div>

        <nav className="topNav" aria-label="メインメニュー">
          {tabs.slice(0, 4).map((tab) => (
            <button
              key={tab}
              className={activeTab === tab ? "active" : ""}
              type="button"
              onClick={() => setActiveTab(tab)}
            >
              {tab}
            </button>
          ))}
        </nav>
      </header>

      <div className="workArea">
        <aside className={sidebarOpen ? "sidebar open" : "sidebar"}>
          <div className="sidebarHeader">
            <button
              className="iconButton"
              type="button"
              onClick={() => setSidebarOpen((value) => !value)}
              aria-label={sidebarOpen ? "フィルターを閉じる" : "フィルターを開く"}
            >
              {sidebarOpen ? "‹" : "›"}
            </button>
            <span>フィルター</span>
            <span className="sidebarSpacer" />
          </div>

          <div className="sidebarBody">
            <div className="sheetList" aria-label="シート">
              {tabs.map((tab) => (
                <button
                  key={tab}
                  className={activeTab === tab ? "selected" : ""}
                  type="button"
                  onClick={() => setActiveTab(tab)}
                >
                  {tab}
                </button>
              ))}
            </div>

            <fieldset>
              <legend>商品カテゴリ</legend>
              {categories.map((category) => (
                <label key={category} className="checkRow">
                  <input type="checkbox" />
                  <span>{category}</span>
                </label>
              ))}
            </fieldset>

            <fieldset>
              <legend>基本情報</legend>
              <input
                value={query}
                onChange={(event) => setQuery(event.target.value)}
                placeholder="SKU / 商品名 / 仕入先"
              />
              <input placeholder="注文番号" />
              <input placeholder="buyer ID" />
            </fieldset>

            <fieldset>
              <legend>仕入先</legend>
              {supplierOptions.map((supplier) => (
                <label key={supplier} className="checkRow">
                  <input type="checkbox" />
                  <span>{supplier}</span>
                </label>
              ))}
            </fieldset>

            <button className="filterButton" type="button">
              絞り込む
            </button>
          </div>
        </aside>

        <main className="ledgerMain">
          {activeTab === "仕入れ管理" ? (
            <SupplierManagement />
          ) : (
            <>
              <section className="ledgerTop">
                <div>
                  <h1>{activeTab}</h1>
                  <p>{tabDescriptions[activeTab]}</p>
                </div>
                <div className="ledgerTopActions">
                  <div className="resultCount">該当 {resultCount} 件</div>
                </div>
              </section>

              <LedgerRecordSections items={visibleItems} />
            </>
          )}
        </main>
      </div>
    </div>
  );
}
