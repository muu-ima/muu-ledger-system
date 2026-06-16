"use client";

import { useDeferredValue, useEffect, useMemo, useState } from "react";
import { CopyableText } from "@/app/components/common/CopyableText";
import { DragScrollArea } from "@/app/components/common/DragScrollArea";
import { PaginationControls } from "@/app/components/common/PaginationControls";
import { fetchShopeeOrders } from "@/lib/shopeeOrders";
import {
  shopeeOrderViews,
  type ShopeeOrder,
  type ShopeeOrderImportBatch,
  type ShopeeOrderView,
} from "@/types/shopeeOrders";

type ShopeeOrderStatusView =
  | "all"
  | "active"
  | "to_ship"
  | "shipping"
  | "completed"
  | "cancelled";

const shopeeOrderStatusViews: {
  label: string;
  value: ShopeeOrderStatusView;
}[] = [
  { label: "すべて", value: "all" },
  { label: "進行中", value: "active" },
  { label: "発送待ち", value: "to_ship" },
  { label: "配送中", value: "shipping" },
  { label: "完了", value: "completed" },
  { label: "キャンセル", value: "cancelled" },
];
const PAGE_SIZE = 20;

function normalizedStatus(order: ShopeeOrder) {
  return order.orderStatus.trim().toLowerCase();
}

function matchesShopeeOrderStatus(
  order: ShopeeOrder,
  statusView: ShopeeOrderStatusView,
) {
  const status = normalizedStatus(order);

  if (statusView === "to_ship") return status === "to ship";
  if (statusView === "shipping") return status === "shipping";
  if (statusView === "completed") return status.includes("complete");
  if (statusView === "cancelled") return status.includes("cancel");
  if (statusView === "active") {
    return !status.includes("cancel") && !status.includes("complete");
  }

  return true;
}

function matchesShopeeOrderSearch(order: ShopeeOrder, searchQuery: string) {
  const query = searchQuery.trim().toLowerCase();
  if (!query) return true;

  return [
    order.orderNo,
    order.orderStatus,
    order.sku,
    order.parentSku,
    order.productName,
    order.buyerUsername,
    order.country,
    order.trackingNumber,
  ]
    .join(" ")
    .toLowerCase()
    .includes(query);
}

function matchesBatchSearch(
  batch: ShopeeOrderImportBatch,
  searchQuery: string,
) {
  const query = searchQuery.trim().toLowerCase();
  if (!query) return true;

  return [batch.filename, batch.status, batch.skipDetails]
    .join(" ")
    .toLowerCase()
    .includes(query);
}

export default function ShopeeOrderWorkspace() {
  const [activeView, setActiveView] =
    useState<ShopeeOrderView>("オーダー原票");
  const [statusView, setStatusView] =
    useState<ShopeeOrderStatusView>("all");
  const [searchQuery, setSearchQuery] = useState("");
  const [orders, setOrders] = useState<ShopeeOrder[]>([]);
  const [batches, setBatches] = useState<ShopeeOrderImportBatch[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [loadStatus, setLoadStatus] = useState("");
  const [currentPage, setCurrentPage] = useState(1);
  const deferredSearchQuery = useDeferredValue(searchQuery);

  const filteredOrders = useMemo(
    () =>
      orders.filter(
        (order) =>
          matchesShopeeOrderStatus(order, statusView) &&
          matchesShopeeOrderSearch(order, deferredSearchQuery),
      ),
    [deferredSearchQuery, orders, statusView],
  );
  const filteredBatches = useMemo(
    () =>
      batches.filter((batch) =>
        matchesBatchSearch(batch, deferredSearchQuery),
      ),
    [batches, deferredSearchQuery],
  );
  const activeTotal =
    activeView === "オーダー原票"
      ? filteredOrders.length
      : filteredBatches.length;
  const totalRecords =
    activeView === "オーダー原票" ? orders.length : batches.length;
  const totalPages = Math.max(1, Math.ceil(activeTotal / PAGE_SIZE));
  const paginatedOrders = useMemo(
    () =>
      filteredOrders.slice(
        (currentPage - 1) * PAGE_SIZE,
        currentPage * PAGE_SIZE,
      ),
    [currentPage, filteredOrders],
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

    async function loadShopeeOrders() {
      try {
        const data = await fetchShopeeOrders();
        if (cancelled) return;

        setOrders(data.orders);
        setBatches(data.batches);
        setLoadStatus("");
      } catch {
        if (!cancelled) {
          setLoadStatus("WordPressからShopeeオーダーを取得できませんでした");
        }
      } finally {
        if (!cancelled) {
          setIsLoading(false);
        }
      }
    }

    loadShopeeOrders();

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
          <h1>Shopeeオーダー</h1>
          <p>Shopeeで受け付けた注文原票を確認するビュー</p>
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
              {activeView === "オーダー原票" ? (
                <div className="ecSalesStatusTabs" aria-label="Shopeeオーダー状態">
                  {shopeeOrderStatusViews.map((view) => (
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
                    activeView === "オーダー原票"
                      ? "注文ID / SKU / 商品名 / 購入者"
                      : "ファイル名 / 状態 / スキップ内訳"
                  }
                  type="search"
                  value={searchQuery}
                  onChange={(event) => setSearchQuery(event.target.value)}
                />
              </label>
            </div>

            <div
              className="tableTabs primaryTabs"
              role="tablist"
              aria-label="Shopeeオーダー表示"
            >
              {shopeeOrderViews.map((view) => (
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

            {activeView === "オーダー原票" ? (
              <ShopeeOrdersTable orders={paginatedOrders} />
            ) : (
              <ShopeeOrderImportBatchesTable batches={paginatedBatches} />
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

function ShopeeOrdersTable({ orders }: { orders: ShopeeOrder[] }) {
  return (
    <DragScrollArea className="ecSalesTableFrame">
      <table className="ledgerGrid shopeeOrderGrid">
        <thead>
          <tr className="headerRow">
            <th className="verifyCol">注文ID</th>
            <th className="typeCol">状態</th>
            <th className="dateCol">注文日</th>
            <th className="dateCol">支払い日時</th>
            <th className="verifyCol">SKU</th>
            <th className="nameCol">商品名</th>
            <th className="buyerCol">購入者</th>
            <th className="typeCol">国</th>
            <th className="qtyCol numberCell">数量</th>
            <th className="moneyCol numberCell">金額</th>
            <th className="sourceCol">配送番号</th>
            <th className="sourceCol">配送方法</th>
            <th className="dateCol">取込日時</th>
          </tr>
        </thead>
        <tbody>
          {orders.length === 0 ? (
            <tr>
              <td colSpan={13}>条件に一致するShopeeオーダー原票はありません</td>
            </tr>
          ) : (
            orders.map((order) => (
              <tr key={order.id}>
                <td>
                  <CopyableText value={order.orderNo} />
                </td>
                <td>{order.orderStatus}</td>
                <td>{order.orderCreatedAt}</td>
                <td>{order.orderPaidAt}</td>
                <td>
                  <CopyableText value={order.sku} />
                </td>
                <td className="nameCell">{order.productName}</td>
                <td>{order.buyerUsername}</td>
                <td>{order.country}</td>
                <td className="numberCell">
                  {order.quantity.toLocaleString("ja-JP")}
                </td>
                <td className="numberCell">{order.displayAmount}</td>
                <td>
                  <CopyableText value={order.trackingNumber} />
                </td>
                <td>{order.shipmentMethod || order.shippingOption}</td>
                <td>{order.createdAt}</td>
              </tr>
            ))
          )}
        </tbody>
      </table>
    </DragScrollArea>
  );
}

function ShopeeOrderImportBatchesTable({
  batches,
}: {
  batches: ShopeeOrderImportBatch[];
}) {
  return (
    <DragScrollArea className="ecSalesTableFrame">
      <table className="ledgerGrid shopeeOrderGrid">
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
                <td className="numberCell">
                  {batch.importedRows.toLocaleString("ja-JP")}
                </td>
                <td className="numberCell">
                  {batch.skippedRows.toLocaleString("ja-JP")}
                </td>
                <td className="nameCell">{batch.skipDetails}</td>
              </tr>
            ))
          )}
        </tbody>
      </table>
    </DragScrollArea>
  );
}
