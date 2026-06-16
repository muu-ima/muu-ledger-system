import { CopyableText } from "@/app/components/common/CopyableText";
import { DragScrollArea } from "@/app/components/common/DragScrollArea";
import type { LedgerItem } from "@/types/ledger";

function formatYen(value: number) {
  if (!value) return "";
  return `¥${value.toLocaleString("ja-JP")}`;
}

function purchaseTypeLabel(type: string) {
  if (type === "buy") return "買受";
  if (type === "consignment") return "委託";
  return type || "買受";
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
      <DragScrollArea>
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
            <col className="verifyCol" />
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
              <th>区分</th>
              <th>確認方法 取引ID</th>
            </tr>
          </thead>
          <tbody>
            {items.map((item, index) => (
              <tr key={item.id}>
                <td>{item.acquiredAt || (index % 5 === 0 ? "在庫" : "")}</td>
                <td className="selectedCell">
                  <CopyableText value={item.managementNo} />
                </td>
                <td>{purchaseTypeLabel(item.purchaseType)}</td>
                <td>{item.category}</td>
                <td className="nameCell">{item.itemName}</td>
                <td className="numberCell">{item.quantity.toLocaleString("ja-JP")}</td>
                <td className="numberCell">{formatYen(item.purchasePrice)}</td>
                <td>{item.acquiredFrom}</td>
                <td>
                  <CopyableText value={item.sourceOrderNo || item.sellerIdentification} />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </DragScrollArea>
    </section>
  );
}
