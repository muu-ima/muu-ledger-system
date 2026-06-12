"use client";

import { useDeferredValue, useMemo, useState } from "react";
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

        {activeView === "受入れ" ? <LedgerIntakeSection items={filteredItems} /> : null}
        {activeView === "払出し" ? <LedgerPayoutSection items={filteredItems} /> : null}
        {activeView === "相手方・確認" ? (
          <LedgerPartySection items={filteredItems} />
        ) : null}

        {filteredItems.length === 0 ? (
          <div className="ecSalesUpdateStatus">条件に一致するデータはありません</div>
        ) : null}
      </div>
    </div>
  );
}
