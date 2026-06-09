import type { SupplierSource } from "@/types/supplier";

export function SupplierSourceSummaryTable({
  sources,
}: {
  sources: SupplierSource[];
}) {
  return (
    <table className="ledgerGrid supplierSourceGrid">
      <colgroup>
        <col className="rowNoCol" />
        <col className="skuCol" />
        <col className="verifyCol" />
        <col className="dateCol" />
        <col className="sourceCol" />
        <col className="moneyCol" />
        <col className="nameCol" />
        <col className="dateCol" />
        <col className="moneyCol" />
        <col className="moneyCol" />
      </colgroup>
      <thead>
        <tr className="headerRow">
          <th>No</th>
          <th>SKU</th>
          <th>Order no.</th>
          <th>仕入日</th>
          <th>仕入れ先</th>
          <th>仕入れ</th>
          <th>商品名</th>
          <th>販売日</th>
          <th>販売額</th>
          <th>送料</th>
        </tr>
      </thead>
      <tbody>
        {sources.map((source) => (
          <tr key={source.sku}>
            <td>{source.rowNo}</td>
            <td className="selectedCell">{source.sku}</td>
            <td>{source.orderNo}</td>
            <td>{source.acquiredAt}</td>
            <td>{source.supplier}</td>
            <td className="numberCell">{source.purchasePrice}</td>
            <td className="nameCell">{source.itemName}</td>
            <td>{source.soldAt}</td>
            <td className="numberCell">{source.saleAmount}</td>
            <td className="numberCell">{source.shippingCost}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}
