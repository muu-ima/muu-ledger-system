import type { SupplierSource } from "@/types/supplier";

export function SupplierSourceDetailTable({
  sources,
}: {
  sources: SupplierSource[];
}) {
  return (
    <table className="ledgerGrid supplierSourceGrid">
      <colgroup>
        <col className="rowNoCol" />
        <col className="skuCol" />
        <col className="sourceCol" />
        <col className="verifyCol" />
        <col className="dateCol" />
        <col className="buyerCol" />
        <col className="typeCol" />
        <col className="moneyCol" />
        <col className="noteCol" />
        <col className="noteCol" />
      </colgroup>
      <thead>
        <tr className="headerRow">
          <th>No</th>
          <th>SKU</th>
          <th>アカウント</th>
          <th>Order no.</th>
          <th>販売日</th>
          <th>国</th>
          <th>MAG</th>
          <th>ポイント</th>
          <th>備考</th>
          <th>商品名</th>
        </tr>
      </thead>
      <tbody>
        {sources.map((source) => (
          <tr key={source.sku}>
            <td>{source.rowNo}</td>
            <td className="selectedCell">{source.sku}</td>
            <td>{source.account}</td>
            <td>{source.orderNo}</td>
            <td>{source.soldAt}</td>
            <td>{source.country}</td>
            <td>{source.mag}</td>
            <td className="numberCell">{source.points}</td>
            <td>{source.note}</td>
            <td className="nameCell">{source.itemName}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}
