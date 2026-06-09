import type { LedgerItem } from "@/types/ledger";

function formatYen(value: number) {
  if (!value) return "";
  return `¥${value.toLocaleString("ja-JP")}`;
}

export function LedgerIntakeSection({
  items,
}: {
  items: LedgerItem[];
}) {
  return (
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
  );
}
