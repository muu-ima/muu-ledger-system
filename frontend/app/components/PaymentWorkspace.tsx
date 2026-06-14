"use client";

import { useDeferredValue, useEffect, useMemo, useState } from "react";
import { DragScrollArea } from "@/app/components/common/DragScrollArea";
import { PaginationControls } from "@/app/components/common/PaginationControls";
import { fetchPayments } from "@/lib/payments";
import {
  paymentViews,
  type PaymentImportBatch,
  type PaymentTransaction,
  type PaymentView,
} from "@/types/payments";

type PaymentStatusView = "all" | "completed" | "positive" | "negative";

const paymentStatusViews: { label: string; value: PaymentStatusView }[] = [
  { label: "すべて", value: "all" },
  { label: "支払い完了", value: "completed" },
  { label: "払出プラス", value: "positive" },
  { label: "払出マイナス", value: "negative" },
];
const PAGE_SIZE = 20;

function parseMoneyAmount(value: string) {
  const normalized = value.replace(/[^\d.-]/g, "");
  const parsed = Number(normalized);

  return Number.isFinite(parsed) ? parsed : 0;
}

function matchesPaymentStatus(
  transaction: PaymentTransaction,
  statusView: PaymentStatusView,
) {
  if (statusView === "completed") return transaction.payoutDate !== "";
  if (statusView === "positive") return parseMoneyAmount(transaction.netAmount) > 0;
  if (statusView === "negative") return parseMoneyAmount(transaction.netAmount) < 0;

  return true;
}

function matchesPaymentSearch(
  transaction: PaymentTransaction,
  searchQuery: string,
) {
  const query = searchQuery.trim().toLowerCase();
  if (!query) return true;

  return [
    transaction.orderNo,
    transaction.buyerUsername,
    transaction.payoutMethod,
    transaction.payoutStatus,
  ]
    .join(" ")
    .toLowerCase()
    .includes(query);
}

function matchesBatchSearch(batch: PaymentImportBatch, searchQuery: string) {
  const query = searchQuery.trim().toLowerCase();
  if (!query) return true;

  return [batch.filename, batch.status, batch.skipDetails]
    .join(" ")
    .toLowerCase()
    .includes(query);
}

export default function PaymentWorkspace() {
  const [activeView, setActiveView] = useState<PaymentView>("ペイメント原票");
  const [statusView, setStatusView] = useState<PaymentStatusView>("all");
  const [searchQuery, setSearchQuery] = useState("");
  const [transactions, setTransactions] = useState<PaymentTransaction[]>([]);
  const [batches, setBatches] = useState<PaymentImportBatch[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [loadStatus, setLoadStatus] = useState("");
  const [currentPage, setCurrentPage] = useState(1);
  const deferredSearchQuery = useDeferredValue(searchQuery);

  const filteredTransactions = useMemo(
    () =>
      transactions.filter(
        (transaction) =>
          matchesPaymentStatus(transaction, statusView) &&
          matchesPaymentSearch(transaction, deferredSearchQuery),
      ),
    [deferredSearchQuery, statusView, transactions],
  );
  const filteredBatches = useMemo(
    () =>
      batches.filter((batch) =>
        matchesBatchSearch(batch, deferredSearchQuery),
      ),
    [batches, deferredSearchQuery],
  );
  const activeTotal =
    activeView === "ペイメント原票"
      ? filteredTransactions.length
      : filteredBatches.length;
  const totalRecords =
    activeView === "ペイメント原票" ? transactions.length : batches.length;
  const totalPages = Math.max(1, Math.ceil(activeTotal / PAGE_SIZE));
  const paginatedTransactions = useMemo(
    () =>
      filteredTransactions.slice(
        (currentPage - 1) * PAGE_SIZE,
        currentPage * PAGE_SIZE,
      ),
    [currentPage, filteredTransactions],
  );
  const paginatedBatches = useMemo(
    () =>
      filteredBatches.slice(
        (currentPage - 1) * PAGE_SIZE,
        currentPage * PAGE_SIZE,
      ),
    [currentPage, filteredBatches],
  );

  useEffect(() => {
    let cancelled = false;

    async function loadPayments() {
      try {
        const data = await fetchPayments();
        if (cancelled) return;

        setTransactions(data.transactions);
        setBatches(data.batches);
        setLoadStatus("");
      } catch {
        if (!cancelled) {
          setLoadStatus("WordPressからペイメントを取得できませんでした");
        }
      } finally {
        if (!cancelled) {
          setIsLoading(false);
        }
      }
    }

    loadPayments();

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

  return (
    <>
      <section className="ledgerTop">
        <div>
          <h1>ペイメント</h1>
          <p>入金、手数料、Payoutを確認するビュー</p>
        </div>
        <div className="ledgerTopActions">
          <div className="resultCount">
            {isLoading ? "読込中" : `該当 ${activeTotal} / ${totalRecords} 件`}
          </div>
        </div>
      </section>

      <div className="ledgerSections">
        <section className="ledgerSection">
          <div className="ecSalesListCard">
            <div className="ecSalesListToolbar">
              {activeView === "ペイメント原票" ? (
                <div className="ecSalesStatusTabs" aria-label="ペイメント状態">
                  {paymentStatusViews.map((view) => (
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
              ) : (
                <div className="resultCount">直近のCSV取り込み履歴</div>
              )}
              <label className="ecSalesSearch">
                <span>検索</span>
                <input
                  placeholder={
                    activeView === "ペイメント原票"
                      ? "注文ID / 購入者 / 支払方法"
                      : "ファイル名 / 状態 / スキップ内訳"
                  }
                  type="search"
                  value={searchQuery}
                  onChange={(event) => setSearchQuery(event.target.value)}
                />
              </label>
            </div>

            <div className="tableTabs primaryTabs" role="tablist" aria-label="ペイメント表示">
              {paymentViews.map((view) => (
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

            {activeView === "ペイメント原票" ? (
              <PaymentTransactionsTable transactions={paginatedTransactions} />
            ) : (
              <PaymentImportBatchesTable batches={paginatedBatches} />
            )}

            <PaginationControls
              currentPage={currentPage}
              pageSize={PAGE_SIZE}
              totalItems={activeTotal}
              onPageChange={setCurrentPage}
            />

            {loadStatus ? (
              <div className="ecSalesUpdateStatus">{loadStatus}</div>
            ) : null}
          </div>
        </section>
      </div>
    </>
  );
}

function PaymentTransactionsTable({
  transactions,
}: {
  transactions: PaymentTransaction[];
}) {
  return (
    <DragScrollArea className="ecSalesTableFrame">
      <table className="ledgerGrid paymentGrid">
        <thead>
          <tr className="headerRow">
            <th className="verifyCol">注文ID</th>
            <th className="dateCol">注文日</th>
            <th className="dateCol">支払い完了日</th>
            <th className="buyerCol">購入者</th>
            <th className="moneyCol numberCell">販売額</th>
            <th className="moneyCol numberCell">払出額</th>
            <th className="sourceCol">支払方法</th>
            <th className="sourceCol">状態</th>
            <th className="dateCol">取込日時</th>
          </tr>
        </thead>
        <tbody>
          {transactions.length === 0 ? (
            <tr>
              <td colSpan={9}>条件に一致するペイメント原票はありません</td>
            </tr>
          ) : (
            transactions.map((transaction) => (
              <tr key={transaction.id}>
                <td>{transaction.orderNo}</td>
                <td>{transaction.transactionDate}</td>
                <td>{transaction.payoutDate}</td>
                <td>{transaction.buyerUsername}</td>
                <td className="numberCell">{transaction.grossAmount}</td>
                <td className="numberCell">{transaction.netAmount}</td>
                <td>{transaction.payoutMethod}</td>
                <td>{transaction.payoutStatus}</td>
                <td>{transaction.createdAt}</td>
              </tr>
            ))
          )}
        </tbody>
      </table>
    </DragScrollArea>
  );
}

function PaymentImportBatchesTable({
  batches,
}: {
  batches: PaymentImportBatch[];
}) {
  return (
    <DragScrollArea className="ecSalesTableFrame">
      <table className="ledgerGrid paymentGrid">
        <thead>
          <tr className="headerRow">
            <th className="dateCol">実行日時</th>
            <th className="nameCol">ファイル名</th>
            <th className="typeCol">状態</th>
            <th className="qtyCol numberCell">保存</th>
            <th className="qtyCol numberCell">スキップ</th>
            <th className="noteCol">スキップ内訳</th>
          </tr>
        </thead>
        <tbody>
          {batches.length === 0 ? (
            <tr>
              <td colSpan={6}>取り込み履歴はありません</td>
            </tr>
          ) : (
            batches.map((batch) => (
              <tr key={batch.id}>
                <td>{batch.completedAt || batch.createdAt}</td>
                <td className="nameCell">{batch.filename}</td>
                <td>{batch.status}</td>
                <td className="numberCell">{batch.importedRows.toLocaleString("ja-JP")}</td>
                <td className="numberCell">{batch.skippedRows.toLocaleString("ja-JP")}</td>
                <td className="nameCell">{batch.skipDetails}</td>
              </tr>
            ))
          )}
        </tbody>
      </table>
    </DragScrollArea>
  );
}
