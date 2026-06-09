import type { SupplierSource } from "@/types/supplier";

export function SupplierSourceShippingTable({
  sources,
}: {
  sources: SupplierSource[];
}) {
  return (
    <table className="ledgerGrid supplierSourceGrid">
      <colgroup>
        <col className="skuCol" />
        <col className="sourceCol" />
        <col className="sourceCol" />
        <col className="weightCol" />
        <col className="weightCol" />
        <col className="sizeCol" />
        <col className="sizeCol" />
        <col className="sizeCol" />
        <col className="dateCol" />
        <col className="dateCol" />
      </colgroup>
      <thead>
        <tr className="headerRow">
          <th>SKU</th>
          <th>発送サイト</th>
          <th>梱包者</th>
          <th>実重g</th>
          <th>体積重g</th>
          <th>縦cm</th>
          <th>横cm</th>
          <th>高さcm</th>
          <th>初回メール</th>
          <th>領収書印刷日</th>
        </tr>
      </thead>
      <tbody>
        {sources.map((source) => (
          <tr key={source.sku}>
            <td className="selectedCell">{source.sku}</td>
            <td>{source.shippingSite}</td>
            <td>{source.packer}</td>
            <td className="numberCell">{source.actualWeight}</td>
            <td className="numberCell">{source.dimensionalWeight}</td>
            <td className="numberCell">{source.length}</td>
            <td className="numberCell">{source.width}</td>
            <td className="numberCell">{source.height}</td>
            <td>{source.firstMailAt}</td>
            <td>{source.receiptPrintedAt}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}
