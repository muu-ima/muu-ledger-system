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

export function LedgerRecordSections({
  items,
}: {
  items: LedgerItem[];
}) {
  return (
    <div className="ledgerSections">
      <section className="ledgerSection">
        <div className="sectionTitle">
          <h2>受入れ</h2>
          <span>仕入れ・古物情報</span>
        </div>
        <div className="ledgerTableFrame">
          <table className="ledgerGrid intakeGrid">
            <colgroup>
              <col className="dateCol" />
              <col className="skuCol" />
              <col className="typeCol" />
              <col className="catCol" />
              <col className="nameCol" />
              <col className="qtyCol" />
              <col className="moneyCol" />
              <col className="sourceCol" />
            </colgroup>
            <thead>
              <tr className="headerRow">
                <th>仕入れ年月日</th>
                <th>SKU</th>
                <th>区別</th>
                <th>品目</th>
                <th>商品名</th>
                <th>数量</th>
                <th>代価</th>
                <th>仕入れ先</th>
              </tr>
            </thead>
            <tbody>
              {items.map((item, index) => (
                <tr key={item.id}>
                  <td>{item.acquiredAt || (index % 5 === 0 ? "在庫" : "")}</td>
                  <td className="selectedCell">{item.managementNo}</td>
                  <td>買受</td>
                  <td>{item.category}</td>
                  <td className="nameCell">{item.itemName}</td>
                  <td className="numberCell">1</td>
                  <td className="numberCell">{formatYen(item.purchasePrice)}</td>
                  <td>{item.acquiredFrom}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>

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

      <section className="ledgerSection">
        <div className="sectionTitle">
          <h2>相手方・確認</h2>
          <span>本人確認・買主情報</span>
        </div>
        <div className="ledgerTableFrame">
          <table className="ledgerGrid partyGrid">
            <colgroup>
              <col className="skuCol" />
              <col className="verifyCol" />
              <col className="buyerCol" />
              <col className="buyerCol" />
              <col className="addressCol" />
            </colgroup>
            <thead>
              <tr className="headerRow">
                <th>SKU</th>
                <th>仕入れ確認</th>
                <th>国名</th>
                <th>buyer ID</th>
                <th>送付先住所</th>
              </tr>
            </thead>
            <tbody>
              {items.map((item) => {
                const sold = isSold(item);
                return (
                  <tr key={item.id}>
                    <td className="selectedCell">{item.managementNo}</td>
                    <td>{item.sellerIdentification}</td>
                    <td>{sold ? "アメリカ" : ""}</td>
                    <td>{sold ? "buyer_sample" : ""}</td>
                    <td>{sold ? "Sample address, city, country" : ""}</td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </section>
    </div>
  );
}
