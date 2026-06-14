"use client";

import { useDeferredValue, useEffect, useMemo, useState } from "react";
import { DragScrollArea } from "@/app/components/common/DragScrollArea";
import { PaginationControls } from "@/app/components/common/PaginationControls";
import { fetchExchangeRates } from "@/lib/exchangeRates";
import {
  exchangeRateViews,
  type ExchangeRateFetchStatus,
  type ExchangeRateRow,
  type ExchangeRateView,
} from "@/types/exchangeRates";

type ExchangeRateSourceView = "all" | "manual" | "api" | "other";
type ExchangeRateStats = {
  apiCount: number;
  latestRateDate: string;
  manualCount: number;
  otherCount: number;
  totalCount: number;
};

const exchangeRateSourceViews: {
  label: string;
  value: ExchangeRateSourceView;
}[] = [
  { label: "すべて", value: "all" },
  { label: "手入力", value: "manual" },
  { label: "API取得", value: "api" },
  { label: "その他", value: "other" },
];
const PAGE_SIZE = 20;

function matchesSource(row: ExchangeRateRow, sourceView: ExchangeRateSourceView) {
  if (sourceView === "manual") return row.isManualOverride;
  if (sourceView === "api") return row.source === "exchangerate_api";
  if (sourceView === "other") {
    return !row.isManualOverride && row.source !== "exchangerate_api";
  }

  return true;
}

function matchesSearch(row: ExchangeRateRow, searchQuery: string) {
  const query = searchQuery.trim().toLowerCase();
  if (!query) return true;

  return [row.rateDate, row.pair, row.source, row.notes]
    .join(" ")
    .toLowerCase()
    .includes(query);
}

function buildExchangeRateStats(rates: ExchangeRateRow[]): ExchangeRateStats {
  return rates.reduce<ExchangeRateStats>(
    (stats, row) => {
      const latestRateDate =
        row.rateDate > stats.latestRateDate ? row.rateDate : stats.latestRateDate;

      if (row.isManualOverride) {
        return {
          ...stats,
          latestRateDate,
          manualCount: stats.manualCount + 1,
          totalCount: stats.totalCount + 1,
        };
      }

      if (row.source === "exchangerate_api") {
        return {
          ...stats,
          apiCount: stats.apiCount + 1,
          latestRateDate,
          totalCount: stats.totalCount + 1,
        };
      }

      return {
        ...stats,
        latestRateDate,
        otherCount: stats.otherCount + 1,
        totalCount: stats.totalCount + 1,
      };
    },
    {
      apiCount: 0,
      latestRateDate: "",
      manualCount: 0,
      otherCount: 0,
      totalCount: 0,
    },
  );
}

export default function ExchangeRateWorkspace() {
  const [activeView, setActiveView] = useState<ExchangeRateView>("レート一覧");
  const [sourceView, setSourceView] = useState<ExchangeRateSourceView>("all");
  const [searchQuery, setSearchQuery] = useState("");
  const [rates, setRates] = useState<ExchangeRateRow[]>([]);
  const [lastFetch, setLastFetch] = useState<ExchangeRateFetchStatus | null>(
    null,
  );
  const [nextFetchAt, setNextFetchAt] = useState("");
  const [isLoading, setIsLoading] = useState(true);
  const [loadStatus, setLoadStatus] = useState("");
  const [currentPage, setCurrentPage] = useState(1);
  const deferredSearchQuery = useDeferredValue(searchQuery);

  const filteredRates = useMemo(
    () =>
      rates.filter(
        (row) =>
          matchesSource(row, sourceView) &&
          matchesSearch(row, deferredSearchQuery),
      ),
    [deferredSearchQuery, rates, sourceView],
  );
  const totalPages = Math.max(1, Math.ceil(filteredRates.length / PAGE_SIZE));
  const paginatedRates = useMemo(
    () =>
      filteredRates.slice(
        (currentPage - 1) * PAGE_SIZE,
        currentPage * PAGE_SIZE,
      ),
    [currentPage, filteredRates],
  );
  const stats = useMemo(() => buildExchangeRateStats(rates), [rates]);

  useEffect(() => {
    let cancelled = false;

    async function loadRates() {
      try {
        const data = await fetchExchangeRates();
        if (cancelled) return;

        setRates(data.rates);
        setLastFetch(data.lastFetch);
        setNextFetchAt(data.nextFetchAt);
        setLoadStatus("");
      } catch {
        if (!cancelled) {
          setLoadStatus("WordPressから為替レートを取得できませんでした");
        }
      } finally {
        if (!cancelled) {
          setIsLoading(false);
        }
      }
    }

    loadRates();

    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    setCurrentPage(1);
  }, [activeView, deferredSearchQuery, sourceView]);

  useEffect(() => {
    if (currentPage > totalPages) {
      setCurrentPage(totalPages);
    }
  }, [currentPage, totalPages]);

  return (
    <>
      <section className="ledgerTop">
        <div>
          <h1>為替レート</h1>
          <p>販売日と出金日の換算レートを確認するビュー</p>
        </div>
        <div className="ledgerTopActions">
          <div className="resultCount">
            {isLoading
              ? "読込中"
              : activeView === "レート一覧"
                ? `該当 ${filteredRates.length} / ${rates.length} 件`
                : `保存済み ${rates.length} 件`}
          </div>
        </div>
      </section>

      <div className="ledgerSections">
        <section className="ledgerSection">
          <div className="ecSalesListCard">
            <div className="ecSalesListToolbar">
              {activeView === "レート一覧" ? (
                <div className="ecSalesStatusTabs" aria-label="為替取得元">
                  {exchangeRateSourceViews.map((view) => (
                    <button
                      className={sourceView === view.value ? "active" : ""}
                      key={view.value}
                      onClick={() => setSourceView(view.value)}
                      type="button"
                    >
                      {view.label}
                    </button>
                  ))}
                </div>
              ) : (
                <div className="resultCount">自動取得の状態</div>
              )}
              <label className="ecSalesSearch">
                <span>検索</span>
                <input
                  placeholder="日付 / 通貨ペア / 取得元"
                  type="search"
                  value={searchQuery}
                  onChange={(event) => setSearchQuery(event.target.value)}
                  disabled={activeView !== "レート一覧"}
                />
              </label>
            </div>

            <div className="tableTabs primaryTabs" role="tablist" aria-label="為替レート表示">
              {exchangeRateViews.map((view) => (
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

            {activeView === "レート一覧" ? (
              <>
                <ExchangeRateTable rates={paginatedRates} />
                <PaginationControls
                  currentPage={currentPage}
                  pageSize={PAGE_SIZE}
                  totalItems={filteredRates.length}
                  onPageChange={setCurrentPage}
                />
              </>
            ) : (
              <ExchangeRateFetchStatusPanel
                lastFetch={lastFetch}
                nextFetchAt={nextFetchAt}
                stats={stats}
              />
            )}

            {loadStatus ? (
              <div className="ecSalesUpdateStatus">{loadStatus}</div>
            ) : null}
          </div>
        </section>
      </div>
    </>
  );
}

function ExchangeRateTable({ rates }: { rates: ExchangeRateRow[] }) {
  return (
    <DragScrollArea className="ecSalesTableFrame">
      <table className="ledgerGrid exchangeRateGrid">
        <thead>
          <tr className="headerRow">
            <th className="dateCol">日付</th>
            <th className="sourceCol">通貨ペア</th>
            <th className="rateCol numberCell">レート</th>
            <th className="sourceCol">取得元</th>
            <th className="typeCol">固定</th>
            <th className="dateCol">取得日時</th>
            <th className="noteCol">メモ</th>
          </tr>
        </thead>
        <tbody>
          {rates.length === 0 ? (
            <tr>
              <td colSpan={7}>条件に一致する為替レートはありません</td>
            </tr>
          ) : (
            rates.map((rate) => (
              <tr key={rate.id}>
                <td>{rate.rateDate}</td>
                <td>{rate.pair}</td>
                <td className="numberCell">{rate.rate}</td>
                <td>{rate.source}</td>
                <td>{rate.isManualOverride ? "はい" : "いいえ"}</td>
                <td>{rate.fetchedAt}</td>
                <td className="nameCell">{rate.notes}</td>
              </tr>
            ))
          )}
        </tbody>
      </table>
    </DragScrollArea>
  );
}

function ExchangeRateFetchStatusPanel({
  lastFetch,
  nextFetchAt,
  stats,
}: {
  lastFetch: ExchangeRateFetchStatus | null;
  nextFetchAt: string;
  stats: ExchangeRateStats;
}) {
  return (
    <div className="exchangeRateStatusPanel">
      <dl>
        <div>
          <dt>適用優先順位</dt>
          <dd>手入力 &gt; API取得 &gt; その他</dd>
        </div>
        <div>
          <dt>最新レート日</dt>
          <dd>{stats.latestRateDate || "未保存"}</dd>
        </div>
        <div>
          <dt>保存済みレート</dt>
          <dd>{stats.totalCount.toLocaleString("ja-JP")} 件</dd>
        </div>
        <div>
          <dt>取得元内訳</dt>
          <dd>
            手入力 {stats.manualCount.toLocaleString("ja-JP")} / API{" "}
            {stats.apiCount.toLocaleString("ja-JP")} / その他{" "}
            {stats.otherCount.toLocaleString("ja-JP")}
          </dd>
        </div>
        <div>
          <dt>次回自動取得</dt>
          <dd>{nextFetchAt || "未登録"}</dd>
        </div>
        <div>
          <dt>最終自動取得</dt>
          <dd>{lastFetch?.ranAt || "まだ実行されていません"}</dd>
        </div>
        <div>
          <dt>状態</dt>
          <dd>{lastFetch?.ranAt ? (lastFetch.ok ? "成功" : "失敗") : ""}</dd>
        </div>
        <div>
          <dt>対象日</dt>
          <dd>{lastFetch?.date || ""}</dd>
        </div>
        <div>
          <dt>保存 / スキップ</dt>
          <dd>
            {lastFetch?.ranAt
              ? `${lastFetch.saved.toLocaleString("ja-JP")} / ${lastFetch.skipped.toLocaleString("ja-JP")}`
              : ""}
          </dd>
        </div>
        <div>
          <dt>メッセージ</dt>
          <dd>{lastFetch?.message || ""}</dd>
        </div>
      </dl>
    </div>
  );
}
