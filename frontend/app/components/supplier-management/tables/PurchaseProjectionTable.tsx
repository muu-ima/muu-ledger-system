import type { SupplierSource } from "@/types/supplier";

export function PurchaseProjectionTable({
  sources,
}: {
  sources: SupplierSource[];
}) {
  return (
    <table className="ledgerGrid purchaseProjectionGrid">
      <colgroup>
        <col className="skuCol" />
        <col className="verifyCol" />
        <col className="dateCol" />
        <col className="sourceCol" />
        <col className="moneyCol" />
        <col className="catCol" />
        <col className="nameCol" />
        <col className="dateCol" />
        <col className="sourceCol" />
        <col className="moneyCol" />
      </colgroup>
      <thead>
        <tr className="headerRow">
          <th>SKU</th>
          <th>Order no.</th>
          <th>仕入れ日</th>
          <th>仕入れ先</th>
          <th>仕入れ金額</th>
          <th>品目</th>
          <th>商品名</th>
          <th>販売日</th>
          <th>販売先</th>
          <th>販売金額</th>
        </tr>
      </thead>
      <tbody>
        {sources.map((source) => (
          <tr key={source.sku}>
            <td className="selectedCell">{source.sku}</td>
            <td>{source.orderNo}</td>
            <td>{source.acquiredAt}</td>
            <td>{source.supplier}</td>
            <td className="numberCell">{source.purchasePrice}</td>
            <td className="warningCell">未分類</td>
            <td className="nameCell">{source.itemName}</td>
            <td>{source.soldAt}</td>
            <td>ebay</td>
            <td className="numberCell">{source.saleAmount}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}
