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

function saleValue(item: LedgerItem) {
  if (item.salePrice) return formatYen(item.salePrice);
  if (item.status === "in_stock") return "在庫";
  return "";
}

function isSold(item: LedgerItem) {
  return item.status === "sold" || Boolean(item.soldAt);
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
            {items.map((item) => {
              const sold = isSold(item);
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
  );
}
