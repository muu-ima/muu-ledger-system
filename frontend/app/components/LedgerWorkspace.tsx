"use client";

import { type FormEvent, useEffect, useMemo, useState } from "react";
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

const supplierSourceSample = {
  rowNo: "1",
  sku: "20251125_mizushima_02",
  orderNo: "25-13888-57021",
  account: "signpost",
  soldAt: "12/2",
  acquiredAt: "12/3",
  country: "アメリカ",
  mag: "",
  saleAmount: "$300.00",
  purchasePrice: "¥24,980",
  shippingCost: "¥10,735",
  points: "",
  note: "関税・手数料合算",
  packer: "小栁12/9",
  shippingSite: "elogi",
  actualWeight: "307",
  dimensionalWeight: "728",
  length: "32.5",
  width: "28",
  height: "4",
  itemName: "Canon PowerShot SX620 HS Black 20.2MP 25x Zoom Compact digital camera Tested",
  supplier: "メルカリショップ",
  firstMailAt: "12/2",
  receiptPrintedAt: "",
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

function normalizeSampleDate(value: string) {
  if (!value) return "";
  if (/^\d{4}-\d{2}-\d{2}$/.test(value)) return value;

  const match = value.match(/^(\d{1,2})[/.](\d{1,2})$/);
  if (!match) return value;

  const [, month, day] = match;
  return `2025-${month.padStart(2, "0")}-${day.padStart(2, "0")}`;
}

export default function LedgerWorkspace({ items }: { items: LedgerItem[] }) {
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [query, setQuery] = useState("");
  const [activeTab, setActiveTab] = useState("古物台帳");
  const [supplierForm, setSupplierForm] = useState(supplierSourceSample);
  const [supplierSubmitStatus, setSupplierSubmitStatus] = useState("");

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

  const resultCount = activeTab === "仕入れ管理" ? 1 : visibleItems.length;

  function updateSupplierForm(
    field: keyof typeof supplierSourceSample,
    value: string,
  ) {
    setSupplierForm((current) => ({ ...current, [field]: value }));
  }

  async function submitSupplierSource(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSupplierSubmitStatus("保存中");

    const baseUrl = process.env.NEXT_PUBLIC_WORDPRESS_URL || "";
    const payload = {
      sku: supplierForm.sku,
      order_no: supplierForm.orderNo,
      account_name: supplierForm.account,
      sold_at: normalizeSampleDate(supplierForm.soldAt),
      acquired_at: normalizeSampleDate(supplierForm.acquiredAt),
      buyer_country: supplierForm.country,
      sale_amount: supplierForm.saleAmount,
      purchase_price: supplierForm.purchasePrice,
      shipping_cost: supplierForm.shippingCost,
      shipping_note: supplierForm.note,
      packer: supplierForm.packer,
      shipping_site: supplierForm.shippingSite,
      actual_weight_g: Number(supplierForm.actualWeight) || 0,
      dimensional_weight_g: Number(supplierForm.dimensionalWeight) || 0,
      package_length_cm: supplierForm.length,
      package_width_cm: supplierForm.width,
      package_height_cm: supplierForm.height,
      item_name: supplierForm.itemName,
      acquired_from: supplierForm.supplier,
      sold_to: "ebay",
      status: supplierForm.soldAt ? "sold" : "in_stock",
    };

    try {
      const response = await fetch(`${baseUrl}/wp-json/kobutsu/v1/items`, {
        method: "POST",
        credentials: "include",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      });

      if (!response.ok) {
        const data = (await response.json().catch(() => null)) as
          | { message?: string }
          | null;
        setSupplierSubmitStatus(data?.message || "保存できませんでした");
        return;
      }

      setSupplierSubmitStatus("保存しました");
    } catch {
      setSupplierSubmitStatus("WordPressに接続できませんでした");
    }
  }

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
            <div className="resultCount">該当 {resultCount} 件</div>
          </section>

          {activeTab === "仕入れ管理" ? (
            <div className="ledgerSections">
              <section className="ledgerSection">
                <div className="sectionTitle">
                  <h2>入力</h2>
                  <span>WordPress REST API に保存</span>
                </div>
                <form className="supplierForm" onSubmit={submitSupplierSource}>
                  <label>
                    <span>SKU</span>
                    <input
                      value={supplierForm.sku}
                      onChange={(event) => updateSupplierForm("sku", event.target.value)}
                    />
                  </label>
                  <label>
                    <span>Order no.</span>
                    <input
                      value={supplierForm.orderNo}
                      onChange={(event) =>
                        updateSupplierForm("orderNo", event.target.value)
                      }
                    />
                  </label>
                  <label>
                    <span>アカウント</span>
                    <input
                      value={supplierForm.account}
                      onChange={(event) =>
                        updateSupplierForm("account", event.target.value)
                      }
                    />
                  </label>
                  <label>
                    <span>販売日</span>
                    <input
                      value={supplierForm.soldAt}
                      onChange={(event) => updateSupplierForm("soldAt", event.target.value)}
                    />
                  </label>
                  <label>
                    <span>仕入日</span>
                    <input
                      value={supplierForm.acquiredAt}
                      onChange={(event) =>
                        updateSupplierForm("acquiredAt", event.target.value)
                      }
                    />
                  </label>
                  <label>
                    <span>国</span>
                    <input
                      value={supplierForm.country}
                      onChange={(event) =>
                        updateSupplierForm("country", event.target.value)
                      }
                    />
                  </label>
                  <label>
                    <span>販売額</span>
                    <input
                      value={supplierForm.saleAmount}
                      onChange={(event) =>
                        updateSupplierForm("saleAmount", event.target.value)
                      }
                    />
                  </label>
                  <label>
                    <span>仕入れ</span>
                    <input
                      value={supplierForm.purchasePrice}
                      onChange={(event) =>
                        updateSupplierForm("purchasePrice", event.target.value)
                      }
                    />
                  </label>
                  <label>
                    <span>送料</span>
                    <input
                      value={supplierForm.shippingCost}
                      onChange={(event) =>
                        updateSupplierForm("shippingCost", event.target.value)
                      }
                    />
                  </label>
                  <label>
                    <span>梱包者</span>
                    <input
                      value={supplierForm.packer}
                      onChange={(event) => updateSupplierForm("packer", event.target.value)}
                    />
                  </label>
                  <label>
                    <span>発送サイト</span>
                    <input
                      value={supplierForm.shippingSite}
                      onChange={(event) =>
                        updateSupplierForm("shippingSite", event.target.value)
                      }
                    />
                  </label>
                  <label>
                    <span>実重g</span>
                    <input
                      value={supplierForm.actualWeight}
                      onChange={(event) =>
                        updateSupplierForm("actualWeight", event.target.value)
                      }
                    />
                  </label>
                  <label>
                    <span>体積重g</span>
                    <input
                      value={supplierForm.dimensionalWeight}
                      onChange={(event) =>
                        updateSupplierForm("dimensionalWeight", event.target.value)
                      }
                    />
                  </label>
                  <label>
                    <span>縦cm</span>
                    <input
                      value={supplierForm.length}
                      onChange={(event) => updateSupplierForm("length", event.target.value)}
                    />
                  </label>
                  <label>
                    <span>横cm</span>
                    <input
                      value={supplierForm.width}
                      onChange={(event) => updateSupplierForm("width", event.target.value)}
                    />
                  </label>
                  <label>
                    <span>高さcm</span>
                    <input
                      value={supplierForm.height}
                      onChange={(event) => updateSupplierForm("height", event.target.value)}
                    />
                  </label>
                  <label className="wideField">
                    <span>商品名</span>
                    <input
                      value={supplierForm.itemName}
                      onChange={(event) =>
                        updateSupplierForm("itemName", event.target.value)
                      }
                    />
                  </label>
                  <label>
                    <span>仕入れ先</span>
                    <input
                      value={supplierForm.supplier}
                      onChange={(event) =>
                        updateSupplierForm("supplier", event.target.value)
                      }
                    />
                  </label>
                  <label>
                    <span>初回メール</span>
                    <input
                      value={supplierForm.firstMailAt}
                      onChange={(event) =>
                        updateSupplierForm("firstMailAt", event.target.value)
                      }
                    />
                  </label>
                  <label className="wideField">
                    <span>備考</span>
                    <textarea
                      value={supplierForm.note}
                      onChange={(event) => updateSupplierForm("note", event.target.value)}
                    />
                  </label>
                  <div className="formActions">
                    <button type="submit">保存</button>
                    <span>{supplierSubmitStatus}</span>
                  </div>
                </form>
              </section>

              <section className="ledgerSection">
                <div className="sectionTitle">
                  <h2>仕入れ元データ</h2>
                  <span>supplier_master_sample.csv 6行目</span>
                </div>
                <div className="ledgerTableFrame">
                  <table className="ledgerGrid supplierSourceGrid">
                    <colgroup>
                      <col className="rowNoCol" />
                      <col className="skuCol" />
                      <col className="verifyCol" />
                      <col className="sourceCol" />
                      <col className="dateCol" />
                      <col className="dateCol" />
                      <col className="buyerCol" />
                      <col className="typeCol" />
                      <col className="moneyCol" />
                      <col className="moneyCol" />
                      <col className="moneyCol" />
                      <col className="noteCol" />
                      <col className="sourceCol" />
                      <col className="sourceCol" />
                      <col className="weightCol" />
                      <col className="weightCol" />
                      <col className="sizeCol" />
                      <col className="sizeCol" />
                      <col className="sizeCol" />
                      <col className="nameCol" />
                      <col className="sourceCol" />
                      <col className="dateCol" />
                    </colgroup>
                    <thead>
                      <tr className="headerRow">
                        <th>No</th>
                        <th>SKU</th>
                        <th>Order no.</th>
                        <th>アカウント</th>
                        <th>販売日</th>
                        <th>仕入日</th>
                        <th>国</th>
                        <th>MAG</th>
                        <th>販売額</th>
                        <th>仕入れ</th>
                        <th>送料</th>
                        <th>備考</th>
                        <th>梱包者</th>
                        <th>発送サイト</th>
                        <th>実重g</th>
                        <th>体積重g</th>
                        <th>cm</th>
                        <th>cm</th>
                        <th>cm</th>
                        <th>商品名</th>
                        <th>仕入れ先</th>
                        <th>初回メール</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>{supplierForm.rowNo}</td>
                        <td className="selectedCell">{supplierForm.sku}</td>
                        <td>{supplierForm.orderNo}</td>
                        <td>{supplierForm.account}</td>
                        <td>{supplierForm.soldAt}</td>
                        <td>{supplierForm.acquiredAt}</td>
                        <td>{supplierForm.country}</td>
                        <td>{supplierForm.mag}</td>
                        <td className="numberCell">{supplierForm.saleAmount}</td>
                        <td className="numberCell">{supplierForm.purchasePrice}</td>
                        <td className="numberCell">{supplierForm.shippingCost}</td>
                        <td>{supplierForm.note}</td>
                        <td>{supplierForm.packer}</td>
                        <td>{supplierForm.shippingSite}</td>
                        <td className="numberCell">{supplierForm.actualWeight}</td>
                        <td className="numberCell">{supplierForm.dimensionalWeight}</td>
                        <td className="numberCell">{supplierForm.length}</td>
                        <td className="numberCell">{supplierForm.width}</td>
                        <td className="numberCell">{supplierForm.height}</td>
                        <td className="nameCell">{supplierForm.itemName}</td>
                        <td>{supplierForm.supplier}</td>
                        <td>{supplierForm.firstMailAt}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </section>

              <section className="ledgerSection">
                <div className="sectionTitle">
                  <h2>仕入れ表への反映</h2>
                  <span>purchases_sample.csv に入る主要項目</span>
                </div>
                <div className="ledgerTableFrame">
                  <table className="ledgerGrid purchaseProjectionGrid">
                    <colgroup>
                      <col className="skuCol" />
                      <col className="verifyCol" />
                      <col className="dateCol" />
                      <col className="sourceCol" />
                      <col className="moneyCol" />
                      <col className="catCol" />
                      <col className="nameCol" />
                      <col className="dateCol" />
                      <col className="sourceCol" />
                      <col className="moneyCol" />
                    </colgroup>
                    <thead>
                      <tr className="headerRow">
                        <th>SKU</th>
                        <th>Order no.</th>
                        <th>仕入れ日</th>
                        <th>仕入れ先</th>
                        <th>仕入れ金額</th>
                        <th>品目</th>
                        <th>商品名</th>
                        <th>販売日</th>
                        <th>販売先</th>
                        <th>販売金額</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td className="selectedCell">{supplierForm.sku}</td>
                        <td>{supplierForm.orderNo}</td>
                        <td>{supplierForm.acquiredAt}</td>
                        <td>{supplierForm.supplier}</td>
                        <td className="numberCell">{supplierForm.purchasePrice}</td>
                        <td className="warningCell">未分類</td>
                        <td className="nameCell">{supplierForm.itemName}</td>
                        <td>{supplierForm.soldAt}</td>
                        <td>ebay</td>
                        <td className="numberCell">{supplierForm.saleAmount}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </section>
            </div>
          ) : (
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
          )}
        </main>
      </div>
    </div>
  );
}
