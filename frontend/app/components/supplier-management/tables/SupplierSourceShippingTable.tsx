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
        <col className="dateCol" />
        <col className="sourceCol" />
        <col className="sourceCol" />
        <col className="sourceCol" />
        <col className="sourceCol" />
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
          <th>発送チャット</th>
          <th>初回メール</th>
          <th>領収書印刷日</th>
          <th>国内追跡番号</th>
          <th>SLS追跡番号</th>
          <th>ヤマト控え有無</th>
          <th>収支チェック</th>
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
            <td>{source.shippingChatAt}</td>
            <td>{source.firstMailAt}</td>
            <td>{source.receiptPrintedAt}</td>
            <td>{source.domesticTrackingNo}</td>
            <td>{source.slsTrackingNo}</td>
            <td>{source.yamatoSlipFlag}</td>
            <td>{source.balanceCheckedFlag}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}
