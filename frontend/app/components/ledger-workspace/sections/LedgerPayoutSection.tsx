import { CopyableText } from "@/app/components/common/CopyableText";
import { DragScrollArea } from "@/app/components/common/DragScrollArea";
import type { LedgerItem, LedgerStatus } from "@/types/ledger";

const statusLabel: Record<LedgerStatus, string> = {
  in_stock: "在庫",
  sold: "売却",
  returned: "返品",
  disposed: "処分",
};

function formatYen(value: number) {
  if (!value) return "";
  return `¥${value.toLocaleString("ja-JP")}`;
}

function formatSaleAmount(item: LedgerItem) {
  if (item.saleAmount) {
    const currency = item.saleCurrency || "";
    return `${currency} ${item.saleAmount.toLocaleString("ja-JP", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })}`.trim();
  }

  return formatYen(item.salePrice);
}

function saleTypeLabel(type: string) {
  if (type === "sold") return "売却";
  if (type === "returned") return "返品";
  if (type === "disposed") return "処分";
  return type || "売却";
}

function saleValue(item: LedgerItem) {
  if (item.saleAmount || item.salePrice) return formatSaleAmount(item);
  if (item.status === "in_stock") return "在庫";
  return "";
}

function isSold(item: LedgerItem) {
  return item.status === "sold" || Boolean(item.soldAt);
}

function linkSourceLabel(source: string) {
  if (source === "sales") return "EC販売";
  if (source === "shopee_orders") return "Shopeeオーダー";
  return "";
}

function matchTypeLabel(matchType: string) {
  if (matchType === "order_id") return "Order ID一致";
  if (matchType === "sku") return "SKU候補";
  return "";
}

function isShopeeSupplement(item: LedgerItem) {
  return item.ledgerLinkSource === "shopee_orders";
}

function isSkuCandidate(item: LedgerItem) {
  return item.shopeeMatchType === "sku";
}

export function LedgerPayoutSection({
  items,
}: {
  items: LedgerItem[];
}) {
  return (
    <section className="ledgerSection">
      <div className="sectionTitle">
        <h2>払出し</h2>
        <span>販売・ステータス</span>
      </div>
      <DragScrollArea>
        <table className="ledgerGrid payoutGrid">
          <colgroup>
            <col className="skuCol" />
            <col className="dateCol" />
            <col className="typeCol" />
            <col className="moneyCol" />
            <col className="sourceCol" />
            <col className="verifyCol" />
            <col className="buyerCol" />
            <col className="sourceCol" />
            <col className="typeCol" />
            <col className="sourceCol" />
            <col className="sourceCol" />
          </colgroup>
          <thead>
            <tr className="headerRow">
              <th>SKU</th>
              <th>販売年月日</th>
              <th>区別</th>
              <th>代価</th>
              <th>販売先</th>
              <th>確認方法 取引ID</th>
              <th>国名</th>
              <th>配送番号</th>
              <th>注文状態</th>
              <th>反映元</th>
              <th>照合</th>
            </tr>
          </thead>
          <tbody>
            {items.map((item) => {
              const sold = isSold(item);
              return (
                <tr key={item.id}>
                  <td className="selectedCell">
                    <CopyableText value={item.managementNo} />
                  </td>
                  <td>{item.soldAt}</td>
                  <td>{sold ? saleTypeLabel(item.saleType) : statusLabel[item.status]}</td>
                  <td className={sold ? "numberCell selectedCell" : "warningCell"}>
                    {saleValue(item)}
                  </td>
                  <td>{item.soldTo}</td>
                  <td>
                    {sold ? (
                      <CopyableText value={item.saleOrderNo} />
                    ) : (
                      ""
                    )}
                  </td>
                  <td>{item.buyerCountry}</td>
                  <td>
                    <CopyableText value={item.trackingNo} />
                  </td>
                  <td>{item.shopeeOrderStatus}</td>
                  <td className={isShopeeSupplement(item) ? "supplementCell" : ""}>
                    {linkSourceLabel(item.ledgerLinkSource)}
                  </td>
                  <td className={isSkuCandidate(item) ? "candidateCell" : ""}>
                    {matchTypeLabel(item.shopeeMatchType)}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </DragScrollArea>
    </section>
  );
}
