"use client";

import { type FormEvent, useEffect, useState } from "react";
import {
  normalizeSampleDate,
  supplierSourceFromApi,
  supplierSourceSample,
  upsertSupplierSource,
  wordpressRestUrl,
} from "@/lib/supplierSources";
import {
  supplierDataViews,
  supplierSourceViews,
  type SupplierDataView,
  type SupplierSource,
  type SupplierSourceApiRow,
  type SupplierSourceSubmitPayload,
  type SupplierSourceView,
} from "@/types/supplier";

export default function SupplierManagement() {
  const [supplierForm, setSupplierForm] = useState(supplierSourceSample);
  const [supplierSources, setSupplierSources] = useState<SupplierSource[]>([
    supplierSourceSample,
  ]);
  const [supplierSourceView, setSupplierSourceView] =
    useState<SupplierSourceView>("要約");
  const [supplierDataView, setSupplierDataView] =
    useState<SupplierDataView>("仕入れ元データ");
  const [supplierModalOpen, setSupplierModalOpen] = useState(false);
  const [supplierSubmitStatus, setSupplierSubmitStatus] = useState("");

  useEffect(() => {
    const baseUrl = process.env.NEXT_PUBLIC_WORDPRESS_URL || "";
    let cancelled = false;

    async function loadSupplierSources() {
      try {
        const response = await fetch(
          wordpressRestUrl(baseUrl, "/kobutsu/v1/supplier-sources"),
          { credentials: "include" },
        );
        if (!response.ok) return;

        const data = (await response.json()) as SupplierSourceApiRow[];
        if (!cancelled && data.length) {
          setSupplierSources(data.map(supplierSourceFromApi));
        }
      } catch {
        // Use the bundled sample row when WordPress is unavailable.
      }
    }

    loadSupplierSources();

    return () => {
      cancelled = true;
    };
  }, []);

  function updateSupplierForm(field: keyof SupplierSource, value: string) {
    setSupplierForm((current) => ({ ...current, [field]: value }));
    setSupplierSubmitStatus("");
  }

  function reflectSupplierSource() {
    setSupplierSources((current) => upsertSupplierSource(current, supplierForm));
    setSupplierSubmitStatus("仕入元データへ反映しました");
  }

  async function submitSupplierSource(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSupplierSubmitStatus("保存中");

    const baseUrl = process.env.NEXT_PUBLIC_WORDPRESS_URL || "";
    const payload: SupplierSourceSubmitPayload = {
      source_row_no: Number(supplierForm.rowNo) || 0,
      sku: supplierForm.sku,
      order_no: supplierForm.orderNo,
      account_name: supplierForm.account,
      sold_at: normalizeSampleDate(supplierForm.soldAt),
      acquired_at: normalizeSampleDate(supplierForm.acquiredAt),
      buyer_country: supplierForm.country,
      mag: supplierForm.mag,
      sale_amount: supplierForm.saleAmount,
      purchase_price: supplierForm.purchasePrice,
      shipping_cost: supplierForm.shippingCost,
      points: supplierForm.points,
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
      first_mail_at: supplierForm.firstMailAt,
      receipt_printed_at: supplierForm.receiptPrintedAt,
      sold_to: "ebay",
      status: supplierForm.soldAt ? "sold" : "in_stock",
    };

    try {
      const response = await fetch(
        wordpressRestUrl(baseUrl, "/kobutsu/v1/supplier-sources"),
        {
          method: "POST",
          credentials: "include",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(payload),
        },
      );

      if (!response.ok) {
        const data = (await response.json().catch(() => null)) as
          | { message?: string }
          | null;
        setSupplierSubmitStatus(data?.message || "保存できませんでした");
        return;
      }

      const data = (await response.json()) as SupplierSourceApiRow;
      const savedSource = supplierSourceFromApi(data);
      setSupplierSources((current) => upsertSupplierSource(current, savedSource));
      setSupplierSubmitStatus("保存しました");
      setSupplierModalOpen(false);
    } catch {
      setSupplierSubmitStatus("WordPressに接続できませんでした");
    }
  }

  return (
    <>
      <section className="ledgerTop">
        <div>
          <h1>仕入れ管理</h1>
          <p>仕入れ元データと仕入れ表を統合した管理ビュー</p>
        </div>
        <div className="ledgerTopActions">
          <button type="button" onClick={() => setSupplierModalOpen(true)}>
            新規仕入れ
          </button>
          <div className="resultCount">該当 {supplierSources.length} 件</div>
        </div>
      </section>

      <div className="ledgerSections">
        {supplierModalOpen ? (
          <div className="modalOverlay" role="presentation">
            <section
              className="supplierModal"
              role="dialog"
              aria-modal="true"
              aria-labelledby="supplier-modal-title"
            >
              <div className="modalHeader">
                <div>
                  <h2 id="supplier-modal-title">新規仕入れ</h2>
                  <span>仕入れ管理テーブルへ保存</span>
                </div>
                <button
                  type="button"
                  className="modalCloseButton"
                  onClick={() => setSupplierModalOpen(false)}
                  aria-label="閉じる"
                >
                  ×
                </button>
              </div>
              <form className="supplierForm" onSubmit={submitSupplierSource}>
                <fieldset className="formSection">
                  <legend>必須入力</legend>
                  <div className="formSectionGrid">
                    <label>
                      <span>SKU</span>
                      <input
                        value={supplierForm.sku}
                        onChange={(event) =>
                          updateSupplierForm("sku", event.target.value)
                        }
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
                      <span>仕入日</span>
                      <input
                        value={supplierForm.acquiredAt}
                        onChange={(event) =>
                          updateSupplierForm("acquiredAt", event.target.value)
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
                      <span>仕入れ</span>
                      <input
                        value={supplierForm.purchasePrice}
                        onChange={(event) =>
                          updateSupplierForm("purchasePrice", event.target.value)
                        }
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
                  </div>
                </fieldset>

                <fieldset className="formSection">
                  <legend>よく使う入力</legend>
                  <div className="formSectionGrid">
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
                        onChange={(event) =>
                          updateSupplierForm("soldAt", event.target.value)
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
                      <span>送料</span>
                      <input
                        value={supplierForm.shippingCost}
                        onChange={(event) =>
                          updateSupplierForm("shippingCost", event.target.value)
                        }
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
                      <span>梱包者</span>
                      <input
                        value={supplierForm.packer}
                        onChange={(event) =>
                          updateSupplierForm("packer", event.target.value)
                        }
                      />
                    </label>
                    <label className="wideField">
                      <span>備考</span>
                      <textarea
                        value={supplierForm.note}
                        onChange={(event) =>
                          updateSupplierForm("note", event.target.value)
                        }
                      />
                    </label>
                  </div>
                </fieldset>

                <fieldset className="formSection">
                  <legend>詳細入力</legend>
                  <div className="formSectionGrid">
                    <label>
                      <span>MAG</span>
                      <input
                        value={supplierForm.mag}
                        onChange={(event) =>
                          updateSupplierForm("mag", event.target.value)
                        }
                      />
                    </label>
                    <label>
                      <span>ポイント加算</span>
                      <input
                        value={supplierForm.points}
                        onChange={(event) =>
                          updateSupplierForm("points", event.target.value)
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
                        onChange={(event) =>
                          updateSupplierForm("length", event.target.value)
                        }
                      />
                    </label>
                    <label>
                      <span>横cm</span>
                      <input
                        value={supplierForm.width}
                        onChange={(event) =>
                          updateSupplierForm("width", event.target.value)
                        }
                      />
                    </label>
                    <label>
                      <span>高さcm</span>
                      <input
                        value={supplierForm.height}
                        onChange={(event) =>
                          updateSupplierForm("height", event.target.value)
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
                    <label>
                      <span>領収書印刷日</span>
                      <input
                        value={supplierForm.receiptPrintedAt}
                        onChange={(event) =>
                          updateSupplierForm("receiptPrintedAt", event.target.value)
                        }
                      />
                    </label>
                  </div>
                </fieldset>
                <div className="formActions">
                  <button type="button" onClick={reflectSupplierSource}>
                    仕入元データへ反映
                  </button>
                  <button type="submit">保存</button>
                  <span>{supplierSubmitStatus}</span>
                </div>
              </form>
            </section>
          </div>
        ) : null}

        <section className="ledgerSection">
          <div className="sectionTitle">
            <h2>仕入れ管理データ</h2>
            <span>保存済みデータと仕入れ表への反映内容</span>
          </div>
          <div className="tableTabs primaryTabs" role="tablist" aria-label="仕入れ管理データ">
            {supplierDataViews.map((view) => (
              <button
                key={view}
                type="button"
                role="tab"
                aria-selected={supplierDataView === view}
                className={supplierDataView === view ? "active" : ""}
                onClick={() => setSupplierDataView(view)}
              >
                {view}
              </button>
            ))}
          </div>
          {supplierDataView === "仕入れ元データ" ? (
            <div className="tableTabs" role="tablist" aria-label="仕入れ元データ表示">
              {supplierSourceViews.map((view) => (
                <button
                  key={view}
                  type="button"
                  role="tab"
                  aria-selected={supplierSourceView === view}
                  className={supplierSourceView === view ? "active" : ""}
                  onClick={() => setSupplierSourceView(view)}
                >
                  {view}
                </button>
              ))}
            </div>
          ) : null}
          <div className="ledgerTableFrame">
            {supplierDataView === "仕入れ元データ" &&
            supplierSourceView === "要約" ? (
              <SupplierSourceSummaryTable sources={supplierSources} />
            ) : null}

            {supplierDataView === "仕入れ元データ" &&
            supplierSourceView === "発送・梱包" ? (
              <SupplierSourceShippingTable sources={supplierSources} />
            ) : null}

            {supplierDataView === "仕入れ元データ" &&
            supplierSourceView === "詳細・原票" ? (
              <SupplierSourceDetailTable sources={supplierSources} />
            ) : null}

            {supplierDataView === "仕入れ表への反映" ? (
              <PurchaseProjectionTable sources={supplierSources} />
            ) : null}
          </div>
        </section>
      </div>
    </>
  );
}

function SupplierSourceSummaryTable({ sources }: { sources: SupplierSource[] }) {
  return (
    <table className="ledgerGrid supplierSourceGrid">
      <colgroup>
        <col className="rowNoCol" />
        <col className="skuCol" />
        <col className="verifyCol" />
        <col className="dateCol" />
        <col className="sourceCol" />
        <col className="moneyCol" />
        <col className="nameCol" />
        <col className="dateCol" />
        <col className="moneyCol" />
        <col className="moneyCol" />
      </colgroup>
      <thead>
        <tr className="headerRow">
          <th>No</th>
          <th>SKU</th>
          <th>Order no.</th>
          <th>仕入日</th>
          <th>仕入れ先</th>
          <th>仕入れ</th>
          <th>商品名</th>
          <th>販売日</th>
          <th>販売額</th>
          <th>送料</th>
        </tr>
      </thead>
      <tbody>
        {sources.map((source) => (
          <tr key={source.sku}>
            <td>{source.rowNo}</td>
            <td className="selectedCell">{source.sku}</td>
            <td>{source.orderNo}</td>
            <td>{source.acquiredAt}</td>
            <td>{source.supplier}</td>
            <td className="numberCell">{source.purchasePrice}</td>
            <td className="nameCell">{source.itemName}</td>
            <td>{source.soldAt}</td>
            <td className="numberCell">{source.saleAmount}</td>
            <td className="numberCell">{source.shippingCost}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}

function SupplierSourceShippingTable({ sources }: { sources: SupplierSource[] }) {
  return (
    <table className="ledgerGrid supplierSourceGrid">
      <colgroup>
        <col className="skuCol" />
        <col className="sourceCol" />
        <col className="sourceCol" />
        <col className="weightCol" />
        <col className="weightCol" />
        <col className="sizeCol" />
        <col className="sizeCol" />
        <col className="sizeCol" />
        <col className="dateCol" />
        <col className="dateCol" />
      </colgroup>
      <thead>
        <tr className="headerRow">
          <th>SKU</th>
          <th>発送サイト</th>
          <th>梱包者</th>
          <th>実重g</th>
          <th>体積重g</th>
          <th>縦cm</th>
          <th>横cm</th>
          <th>高さcm</th>
          <th>初回メール</th>
          <th>領収書印刷日</th>
        </tr>
      </thead>
      <tbody>
        {sources.map((source) => (
          <tr key={source.sku}>
            <td className="selectedCell">{source.sku}</td>
            <td>{source.shippingSite}</td>
            <td>{source.packer}</td>
            <td className="numberCell">{source.actualWeight}</td>
            <td className="numberCell">{source.dimensionalWeight}</td>
            <td className="numberCell">{source.length}</td>
            <td className="numberCell">{source.width}</td>
            <td className="numberCell">{source.height}</td>
            <td>{source.firstMailAt}</td>
            <td>{source.receiptPrintedAt}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}

function SupplierSourceDetailTable({ sources }: { sources: SupplierSource[] }) {
  return (
    <table className="ledgerGrid supplierSourceGrid">
      <colgroup>
        <col className="rowNoCol" />
        <col className="skuCol" />
        <col className="sourceCol" />
        <col className="buyerCol" />
        <col className="typeCol" />
        <col className="moneyCol" />
        <col className="noteCol" />
        <col className="noteCol" />
      </colgroup>
      <thead>
        <tr className="headerRow">
          <th>No</th>
          <th>SKU</th>
          <th>アカウント</th>
          <th>国</th>
          <th>MAG</th>
          <th>ポイント</th>
          <th>備考</th>
          <th>商品名</th>
        </tr>
      </thead>
      <tbody>
        {sources.map((source) => (
          <tr key={source.sku}>
            <td>{source.rowNo}</td>
            <td className="selectedCell">{source.sku}</td>
            <td>{source.account}</td>
            <td>{source.country}</td>
            <td>{source.mag}</td>
            <td className="numberCell">{source.points}</td>
            <td>{source.note}</td>
            <td className="nameCell">{source.itemName}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}

function PurchaseProjectionTable({ sources }: { sources: SupplierSource[] }) {
  return (
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
        {sources.map((source) => (
          <tr key={source.sku}>
            <td className="selectedCell">{source.sku}</td>
            <td>{source.orderNo}</td>
            <td>{source.acquiredAt}</td>
            <td>{source.supplier}</td>
            <td className="numberCell">{source.purchasePrice}</td>
            <td className="warningCell">未分類</td>
            <td className="nameCell">{source.itemName}</td>
            <td>{source.soldAt}</td>
            <td>ebay</td>
            <td className="numberCell">{source.saleAmount}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}
