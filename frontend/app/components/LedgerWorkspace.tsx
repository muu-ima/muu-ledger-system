"use client";

import { useEffect, useMemo, useState } from "react";
import type { LedgerItem } from "@/types/ledger";

const tabs = [
  "古物台帳",
  "仕入れ管理",
  "EC販売",
  "為替レート",
  "ペイメント",
];

const tabDescriptions: Record<string, string> = {
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

const statusLabel = {
  in_stock: "在庫",
  sold: "売却",
  returned: "返品",
  disposed: "処分",
};

function formatYen(value: number) {
  if (!value) return "";
  return `¥${value.toLocaleString("ja-JP")}`;
}

function saleValue(item: LedgerItem) {
  if (item.salePrice) return formatYen(item.salePrice);
  if (item.status === "in_stock") return "在庫";
  return "";
}

export default function LedgerWorkspace({ items }: { items: LedgerItem[] }) {
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [query, setQuery] = useState("");
  const [activeTab, setActiveTab] = useState("古物台帳");

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
          <section className="ledgerTop">
            <div>
              <h1>{activeTab}</h1>
              <p>{tabDescriptions[activeTab]}</p>
            </div>
            <div className="resultCount">該当 {visibleItems.length} 件</div>
          </section>

          <div className="ledgerSections">
            <section className="ledgerSection">
              <div className="sectionTitle">
                <h2>受入れ</h2>
                <span>仕入れ・古物情報</span>
              </div>
              <div className="ledgerTableFrame">
                <table className="ledgerGrid intakeGrid">
                  <colgroup>
                    <col className="dateCol" />
                    <col className="skuCol" />
                    <col className="typeCol" />
                    <col className="catCol" />
                    <col className="nameCol" />
                    <col className="qtyCol" />
                    <col className="moneyCol" />
                    <col className="sourceCol" />
                  </colgroup>
                  <thead>
                    <tr className="headerRow">
                      <th>仕入れ年月日</th>
                      <th>SKU</th>
                      <th>区別</th>
                      <th>品目</th>
                      <th>商品名</th>
                      <th>数量</th>
                      <th>代価</th>
                      <th>仕入れ先</th>
                    </tr>
                  </thead>
                  <tbody>
                    {visibleItems.map((item, index) => (
                      <tr key={item.id}>
                        <td>{item.acquiredAt || (index % 5 === 0 ? "在庫" : "")}</td>
                        <td className="selectedCell">{item.managementNo}</td>
                        <td>買受</td>
                        <td>{item.category}</td>
                        <td className="nameCell">{item.itemName}</td>
                        <td className="numberCell">1</td>
                        <td className="numberCell">{formatYen(item.purchasePrice)}</td>
                        <td>{item.acquiredFrom}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </section>

            <section className="ledgerSection">
              <div className="sectionTitle">
                <h2>払出し</h2>
                <span>販売・ステータス</span>
              </div>
              <div className="ledgerTableFrame">
                <table className="ledgerGrid payoutGrid">
                  <colgroup>
                    <col className="skuCol" />
                    <col className="dateCol" />
                    <col className="typeCol" />
                    <col className="moneyCol" />
                    <col className="sourceCol" />
                    <col className="verifyCol" />
                  </colgroup>
                  <thead>
                    <tr className="headerRow">
                      <th>SKU</th>
                      <th>販売年月日</th>
                      <th>区別</th>
                      <th>代価</th>
                      <th>販売先</th>
                      <th>確認方法 取引ID</th>
                    </tr>
                  </thead>
                  <tbody>
                    {visibleItems.map((item) => {
                      const sold = item.status === "sold" || Boolean(item.soldAt);
                      return (
                        <tr key={item.id}>
                          <td className="selectedCell">{item.managementNo}</td>
                          <td>{item.soldAt}</td>
                          <td>{sold ? "売却" : statusLabel[item.status]}</td>
                          <td className={sold ? "numberCell selectedCell" : "warningCell"}>
                            {saleValue(item)}
                          </td>
                          <td>{item.soldTo || "ebay"}</td>
                          <td>{sold ? item.managementNo.replaceAll("_", "") : ""}</td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            </section>

            <section className="ledgerSection">
              <div className="sectionTitle">
                <h2>相手方・確認</h2>
                <span>本人確認・買主情報</span>
              </div>
              <div className="ledgerTableFrame">
                <table className="ledgerGrid partyGrid">
                  <colgroup>
                    <col className="skuCol" />
                    <col className="verifyCol" />
                    <col className="buyerCol" />
                    <col className="buyerCol" />
                    <col className="addressCol" />
                  </colgroup>
                  <thead>
                    <tr className="headerRow">
                      <th>SKU</th>
                      <th>仕入れ確認</th>
                      <th>国名</th>
                      <th>buyer ID</th>
                      <th>送付先住所</th>
                    </tr>
                  </thead>
                  <tbody>
                    {visibleItems.map((item) => {
                      const sold = item.status === "sold" || Boolean(item.soldAt);
                      return (
                        <tr key={item.id}>
                          <td className="selectedCell">{item.managementNo}</td>
                          <td>{item.sellerIdentification}</td>
                          <td>{sold ? "アメリカ" : ""}</td>
                          <td>{sold ? "buyer_sample" : ""}</td>
                          <td>{sold ? "Sample address, city, country" : ""}</td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            </section>
          </div>
        </main>
      </div>
    </div>
  );
}
