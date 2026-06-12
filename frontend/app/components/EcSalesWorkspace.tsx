"use client";

import { useDeferredValue, useEffect, useMemo, useState } from "react";
import { PaginationControls } from "@/app/components/common/PaginationControls";
import { EcSalesSummaryTabs } from "@/app/components/ec-sales/EcSalesSummaryTabs";
import { EcSalesTabs } from "@/app/components/ec-sales/EcSalesTabs";
import { EcSalesTable } from "@/app/components/ec-sales/EcSalesTable";
import {
  createWordpressJsonHeaders,
  normalizeCurrency,
  normalizeEcSalesRecord,
  parseNumberLike,
  resolveWordpressBaseUrl,
} from "@/lib/ecSales";
import { ecSalesSampleRecords } from "@/lib/ecSalesSamples";
import { wordpressRestUrl } from "@/lib/supplierSources";
import type {
  EcSalesRecord,
  EcSalesRecordApiRow,
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
const PAGE_SIZE = 20;

function matchesStatus(record: EcSalesRecord, statusView: EcSalesStatusView) {
  if (statusView === "unsettled") return record.receivedAmountJpy === "";
  if (statusView === "profit") return parseNumberLike(record.profitJpy) > 0;
  if (statusView === "loss") return parseNumberLike(record.profitJpy) < 0;
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

function findRecordIndex(
  records: EcSalesRecord[],
  sku: string,
  orderNo: string,
) {
  return records.findIndex(
    (record) => record.sku === sku && record.orderNo === orderNo,
  );
}

function ecSalesRecordToUpdatePayload(record: EcSalesRecord) {
  const saleCurrency = normalizeCurrency(record.saleAmountRaw);

  return {
    order_no: record.orderNo,
    sale_date: record.soldAt,
    sale_amount: parseNumberLike(record.saleAmountRaw),
    sale_currency: saleCurrency === "UNKNOWN" ? "USD" : saleCurrency,
    payout_date: record.payoutAt,
    received_amount_jpy: parseNumberLike(record.receivedAmountJpy),
    profit_jpy: parseNumberLike(record.profitJpy),
    profit_rate: parseNumberLike(record.profitRate),
    domestic_tracking_no: record.domesticTrackingNo,
    sls_tracking_no: record.slsTrackingNo,
  };
}

export default function EcSalesWorkspace() {
  const [activeView, setActiveView] = useState<EcSalesView>("集計ビュー");
  const [summaryView, setSummaryView] = useState<EcSalesSummaryView>("全体");
  const [statusView, setStatusView] = useState<EcSalesStatusView>("all");
  const [searchQuery, setSearchQuery] = useState("");
  const [records, setRecords] = useState<EcSalesRecord[]>(ecSalesSampleRecords);
  const [updateStatus, setUpdateStatus] = useState("");
  const [currentPage, setCurrentPage] = useState(1);
  const deferredSearchQuery = useDeferredValue(searchQuery);
  const filteredRecords = useMemo(
    () =>
      records.filter(
        (record) =>
          matchesStatus(record, statusView) &&
          matchesSearch(record, deferredSearchQuery),
      ),
    [deferredSearchQuery, records, statusView],
  );
  const totalPages = Math.max(1, Math.ceil(filteredRecords.length / PAGE_SIZE));
  const paginatedRecords = useMemo(
    () =>
      filteredRecords.slice(
        (currentPage - 1) * PAGE_SIZE,
        currentPage * PAGE_SIZE,
      ),
    [currentPage, filteredRecords],
  );

  useEffect(() => {
    const baseUrl = resolveWordpressBaseUrl(
      process.env.NEXT_PUBLIC_WORDPRESS_URL || "",
    );
    let cancelled = false;

    async function loadEcSales() {
      try {
        const response = await fetch(
          wordpressRestUrl(baseUrl, "/kobutsu/v1/ec-sales"),
          { credentials: "include" },
        );
        if (!response.ok) return;

        const data = (await response.json()) as EcSalesRecordApiRow[];
        if (!cancelled && data.length) {
          setRecords(data.map(normalizeEcSalesRecord));
        }
      } catch {
        // Keep sample records available when WordPress is unavailable.
      }
    }

    loadEcSales();

    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    setCurrentPage(1);
  }, [activeView, deferredSearchQuery, statusView]);

  useEffect(() => {
    if (currentPage > totalPages) {
      setCurrentPage(totalPages);
    }
  }, [currentPage, totalPages]);

  const updateRecord = (
    sku: string,
    orderNo: string,
    field: keyof EcSalesRecord,
    value: string,
  ) => {
    setRecords((currentRecords) => {
      const recordIndex = findRecordIndex(currentRecords, sku, orderNo);
      if (recordIndex === -1) return currentRecords;

      return currentRecords.map((record, index) =>
        index === recordIndex ? { ...record, [field]: value } : record,
      );
    });
  };

  const markRecordUpdated = async (record: EcSalesRecord) => {
    if (!/^\d+$/.test(record.saleId)) {
      setUpdateStatus(
        `${record.sku || record.orderNo} は画面上で更新しました。DB保存はAPIデータ読込後に有効になります`,
      );
      return;
    }

    setUpdateStatus(`${record.sku || record.orderNo} を保存中`);
    const baseUrl = resolveWordpressBaseUrl(
      process.env.NEXT_PUBLIC_WORDPRESS_URL || "",
    );

    try {
      const response = await fetch(
        wordpressRestUrl(baseUrl, `/kobutsu/v1/ec-sales/${record.saleId}`),
        {
          method: "POST",
          credentials: "include",
          headers: createWordpressJsonHeaders(),
          body: JSON.stringify(ecSalesRecordToUpdatePayload(record)),
        },
      );

      if (!response.ok) {
        const data = (await response.json().catch(() => null)) as {
          message?: string;
        } | null;
        setUpdateStatus(data?.message || "保存できませんでした");
        return;
      }

      const savedRecord = normalizeEcSalesRecord(
        (await response.json()) as EcSalesRecordApiRow,
      );
      setRecords((currentRecords) =>
        currentRecords.map((currentRecord) =>
          currentRecord.saleId === savedRecord.saleId
            ? savedRecord
            : currentRecord,
        ),
      );
      setUpdateStatus(
        `${savedRecord.sku || savedRecord.orderNo} を保存しました`,
      );
    } catch {
      setUpdateStatus("WordPressに接続できませんでした");
    }
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
          <EcSalesTabs activeView={activeView} onViewChange={setActiveView} />
          {activeView === "集計ビュー" ? (
            <>
              <div className="ecSalesListCard">
                <div className="ecSalesListToolbar">
                  <div
                    className="ecSalesStatusTabs"
                    aria-label="EC販売ステータス"
                  >
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
                  records={paginatedRecords}
                  summaryView={summaryView}
                  onRecordChange={updateRecord}
                  onRecordUpdate={markRecordUpdated}
                />
                <PaginationControls
                  currentPage={currentPage}
                  pageSize={PAGE_SIZE}
                  totalItems={filteredRecords.length}
                  onPageChange={setCurrentPage}
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
