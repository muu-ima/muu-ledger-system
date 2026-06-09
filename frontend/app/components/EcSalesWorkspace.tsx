"use client";

import { useDeferredValue, useState } from "react";
import { EcSalesSummaryTabs } from "@/app/components/ec-sales/EcSalesSummaryTabs";
import { EcSalesTabs } from "@/app/components/ec-sales/EcSalesTabs";
import { EcSalesTable } from "@/app/components/ec-sales/EcSalesTable";
import { ecSalesSampleRecords } from "@/lib/ecSalesSamples";
import type {
  EcSalesRecord,
  EcSalesSummaryView,
  EcSalesView,
} from "@/types/ecSales";

type EcSalesStatusView = "all" | "unsettled" | "profit" | "loss" | "shipped";

const ecSalesStatusViews: { label: string; value: EcSalesStatusView }[] = [
  { label: "すべて", value: "all" },
  { label: "未精算", value: "unsettled" },
  { label: "利益あり", value: "profit" },
  { label: "赤字", value: "loss" },
  { label: "配送あり", value: "shipped" },
];

function parseSignedNumber(value: string) {
  const normalized = value.replace(/[^0-9.-]/g, "");
  const parsed = Number(normalized);

  return Number.isFinite(parsed) ? parsed : 0;
}

function matchesStatus(record: EcSalesRecord, statusView: EcSalesStatusView) {
  if (statusView === "unsettled") return record.receivedAmountJpy === "";
  if (statusView === "profit") return parseSignedNumber(record.profitJpy) > 0;
  if (statusView === "loss") return parseSignedNumber(record.profitJpy) < 0;
  if (statusView === "shipped") {
    return Boolean(record.domesticTrackingNo || record.slsTrackingNo);
  }

  return true;
}

function matchesSearch(record: EcSalesRecord, searchQuery: string) {
  const normalizedQuery = searchQuery.trim().toLowerCase();
  if (!normalizedQuery) return true;

  return [record.sku, record.orderNo, record.itemName]
    .join(" ")
    .toLowerCase()
    .includes(normalizedQuery);
}

export default function EcSalesWorkspace() {
  const [activeView, setActiveView] = useState<EcSalesView>("集計ビュー");
  const [summaryView, setSummaryView] = useState<EcSalesSummaryView>("全体");
  const [statusView, setStatusView] = useState<EcSalesStatusView>("all");
  const [searchQuery, setSearchQuery] = useState("");
  const [records, setRecords] = useState<EcSalesRecord[]>(ecSalesSampleRecords);
  const [updateStatus, setUpdateStatus] = useState("");
  const deferredSearchQuery = useDeferredValue(searchQuery);
  const filteredRecords = records.filter(
    (record) =>
      matchesStatus(record, statusView) &&
      matchesSearch(record, deferredSearchQuery),
  );

  const updateRecord = (
    sku: string,
    orderNo: string,
    field: keyof EcSalesRecord,
    value: string,
  ) => {
    setRecords((currentRecords) =>
      currentRecords.map((record) =>
        record.sku === sku && record.orderNo === orderNo
          ? { ...record, [field]: value }
          : record,
      ),
    );
  };

  const markRecordUpdated = (record: EcSalesRecord) => {
    setUpdateStatus(`${record.sku || record.orderNo} を画面上で更新しました`);
  };

  return (
    <>
      <section className="ledgerTop">
        <div>
          <h1>EC販売</h1>
          <p>販売、精算、送料、損益を確認するビュー</p>
        </div>
        <div className="ledgerTopActions">
          <div className="resultCount">
            該当 {filteredRecords.length} / {records.length} 件
          </div>
        </div>
      </section>

      <div className="ledgerSections">
        <section className="ledgerSection">
          <div className="sectionTitle">
            <h2>EC販売集計</h2>
            <span>仕入れ表、仕入れ元データ、ペイメント、為替の合成ビュー</span>
          </div>
          <EcSalesTabs activeView={activeView} onViewChange={setActiveView} />
          {activeView === "集計ビュー" ? (
            <>
              <div className="ecSalesListCard">
                <div className="ecSalesListToolbar">
                  <div className="ecSalesStatusTabs" aria-label="EC販売ステータス">
                    {ecSalesStatusViews.map((view) => (
                      <button
                        className={statusView === view.value ? "active" : ""}
                        key={view.value}
                        onClick={() => setStatusView(view.value)}
                        type="button"
                      >
                        {view.label}
                      </button>
                    ))}
                  </div>
                  <label className="ecSalesSearch">
                    <span>検索</span>
                    <input
                      placeholder="SKU / Order no. / 商品名"
                      type="search"
                      value={searchQuery}
                      onChange={(event) => setSearchQuery(event.target.value)}
                    />
                  </label>
                </div>
                <EcSalesSummaryTabs
                  activeView={summaryView}
                  onViewChange={setSummaryView}
                />
                <EcSalesTable
                  records={filteredRecords}
                  summaryView={summaryView}
                  onRecordChange={updateRecord}
                  onRecordUpdate={markRecordUpdated}
                />
                {updateStatus ? (
                  <div className="ecSalesUpdateStatus">{updateStatus}</div>
                ) : null}
              </div>
            </>
          ) : (
            <div className="emptyTableState">
              {activeView} の明細表示を準備中です
            </div>
          )}
        </section>
      </div>
    </>
  );
}
