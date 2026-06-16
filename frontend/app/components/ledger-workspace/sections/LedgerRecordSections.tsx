"use client";

import { useDeferredValue, useEffect, useMemo, useState } from "react";
import { PaginationControls } from "@/app/components/common/PaginationControls";
import { LedgerIntakeSection } from "@/app/components/ledger-workspace/sections/LedgerIntakeSection";
import { LedgerPartySection } from "@/app/components/ledger-workspace/sections/LedgerPartySection";
import { LedgerPayoutSection } from "@/app/components/ledger-workspace/sections/LedgerPayoutSection";
import type { LedgerItem, LedgerStatus } from "@/types/ledger";

const ledgerRecordViews = ["受入れ", "払出し", "相手方・確認"] as const;
const ledgerStatusViews = [
  { label: "すべて", value: "all" },
  { label: "在庫", value: "in_stock" },
  { label: "売却", value: "sold" },
  { label: "返品", value: "returned" },
  { label: "処分", value: "disposed" },
] as const;
const PAGE_SIZE = 20;

type LedgerRecordView = (typeof ledgerRecordViews)[number];
type LedgerStatusView = "all" | LedgerStatus;

function matchesLedgerStatus(item: LedgerItem, statusView: LedgerStatusView) {
  if (statusView === "all") return true;
  return item.status === statusView;
}

function matchesLedgerSearch(item: LedgerItem, searchQuery: string) {
  const normalizedSearch = searchQuery.trim().toLowerCase();

  if (!normalizedSearch) return true;

  return [
    item.managementNo,
    item.itemName,
    item.category,
    item.acquiredFrom,
    item.soldTo,
    item.saleOrderNo,
    item.buyerId,
    item.buyerName,
    item.buyerCountry,
  ]
    .join(" ")
    .toLowerCase()
    .includes(normalizedSearch);
}

export function LedgerRecordSections({
  items,
}: {
  items: LedgerItem[];
}) {
  const [activeView, setActiveView] = useState<LedgerRecordView>("受入れ");
  const [statusView, setStatusView] = useState<LedgerStatusView>("all");
  const [searchQuery, setSearchQuery] = useState("");
  const [currentPage, setCurrentPage] = useState(1);
  const deferredSearchQuery = useDeferredValue(searchQuery);

  const filteredItems = useMemo(
    () =>
      items.filter(
        (item) =>
          matchesLedgerStatus(item, statusView) &&
          matchesLedgerSearch(item, deferredSearchQuery),
      ),
    [deferredSearchQuery, items, statusView],
  );
  const totalPages = Math.max(1, Math.ceil(filteredItems.length / PAGE_SIZE));
  const paginatedItems = useMemo(
    () =>
      filteredItems.slice(
        (currentPage - 1) * PAGE_SIZE,
        currentPage * PAGE_SIZE,
      ),
    [currentPage, filteredItems],
  );

  useEffect(() => {
    setCurrentPage(1);
  }, [activeView, deferredSearchQuery, statusView]);

  useEffect(() => {
    if (currentPage > totalPages) {
      setCurrentPage(totalPages);
    }
  }, [currentPage, totalPages]);

  return (
    <div className="ledgerSections">
      <div className="ecSalesListCard ledgerListCard">
        <div className="ecSalesListToolbar">
          <div className="ecSalesStatusTabs" aria-label="古物台帳ステータス">
            {ledgerStatusViews.map((view) => (
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
          <div className="listToolbarMeta">
            <div className="resultCount">
              該当 {filteredItems.length} / {items.length} 件
            </div>
            <label className="ecSalesSearch">
              <span>検索</span>
              <input
                placeholder="SKU / 商品名 / 仕入先 / 販売先"
                type="search"
                value={searchQuery}
                onChange={(event) => setSearchQuery(event.target.value)}
              />
            </label>
          </div>
        </div>

        <div className="tableTabs primaryTabs" role="tablist" aria-label="古物台帳表示">
          {ledgerRecordViews.map((view) => (
            <button
              key={view}
              type="button"
              role="tab"
              aria-selected={activeView === view}
              className={activeView === view ? "active" : ""}
              onClick={() => setActiveView(view)}
            >
              {view}
            </button>
          ))}
        </div>

        {activeView === "受入れ" ? <LedgerIntakeSection items={paginatedItems} /> : null}
        {activeView === "払出し" ? <LedgerPayoutSection items={paginatedItems} /> : null}
        {activeView === "相手方・確認" ? (
          <LedgerPartySection items={paginatedItems} />
        ) : null}

        <PaginationControls
          currentPage={currentPage}
          pageSize={PAGE_SIZE}
          totalItems={filteredItems.length}
          onPageChange={setCurrentPage}
        />

        {filteredItems.length === 0 ? (
          <div className="ecSalesUpdateStatus">条件に一致するデータはありません</div>
        ) : null}
      </div>
    </div>
  );
}
