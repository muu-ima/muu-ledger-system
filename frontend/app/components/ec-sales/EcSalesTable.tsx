import type { EcSalesRecord } from "@/types/ecSales";

type EcSalesTableProps = {
  records: EcSalesRecord[];
};

export function EcSalesTable({ records }: EcSalesTableProps) {
  return (
    <table className="ledgerGrid ecSalesGrid">
      <colgroup>
        <col className="typeCol" />
        <col className="skuCol" />
        <col className="verifyCol" />
        <col className="dateCol" />
        <col className="dateCol" />
        <col className="dateCol" />
        <col className="dateCol" />
        <col className="nameCol" />
        <col className="moneyCol" />
        <col className="moneyCol" />
        <col className="moneyCol" />
        <col className="moneyCol" />
        <col className="moneyCol" />
        <col className="moneyCol" />
        <col className="moneyCol" />
        <col className="rateCol" />
        <col className="rateCol" />
        <col className="moneyCol" />
        <col className="moneyCol" />
        <col className="moneyCol" />
        <col className="moneyCol" />
        <col className="moneyCol" />
        <col className="rateCol" />
        <col className="qtyCol" />
        <col className="sourceCol" />
        <col className="sourceCol" />
      </colgroup>
      <thead>
        <tr className="headerRow">
          <th>同梱</th>
          <th>SKU</th>
          <th>Order number</th>
          <th>仕入れ日</th>
          <th>出品日</th>
          <th>販売日</th>
          <th>出金日</th>
          <th>商品名</th>
          <th>仕入れ金額</th>
          <th>販売金額</th>
          <th>販売金額(円)</th>
          <th>手数料合計</th>
          <th>広告費</th>
          <th>shopee手数料</th>
          <th>Payout金額</th>
          <th>販売時為替</th>
          <th>出金時為替</th>
          <th>受取金額</th>
          <th>海外送料</th>
          <th>手数料還付</th>
          <th>消費税還付</th>
          <th>最終損益</th>
          <th>利益率</th>
          <th>売れるまで</th>
          <th>国内送り状</th>
          <th>SLS送り状</th>
        </tr>
      </thead>
      <tbody>
        {records.map((record) => (
          <tr key={`${record.sku}-${record.orderNo}`}>
            <td>{record.bundledFlag}</td>
            <td className="selectedCell">{record.sku}</td>
            <td>{record.orderNo}</td>
            <td>{record.purchaseDate}</td>
            <td>{record.listedAt}</td>
            <td>{record.soldAt}</td>
            <td>{record.payoutAt}</td>
            <td className="nameCell">{record.itemName}</td>
            <td className="numberCell">{record.purchasePriceJpy}</td>
            <td className="numberCell">{record.saleAmountRaw}</td>
            <td className="numberCell">{record.saleAmountJpy}</td>
            <td className="numberCell">{record.totalFeesRaw}</td>
            <td className="numberCell">{record.adFeeRaw}</td>
            <td className="numberCell">{record.marketplaceFeeRaw}</td>
            <td className="numberCell">{record.payoutAmountRaw}</td>
            <td className="numberCell">{record.saleExchangeRate}</td>
            <td className="numberCell">{record.payoutExchangeRate}</td>
            <td className="numberCell">{record.receivedAmountJpy}</td>
            <td className="numberCell">{record.overseasShippingYen}</td>
            <td className="numberCell">{record.feeTaxRefundJpy}</td>
            <td className="numberCell">{record.purchaseTaxRefundJpy}</td>
            <td className="numberCell">{record.profitJpy}</td>
            <td className="numberCell">{record.profitRate}</td>
            <td className="numberCell">{record.daysToSell}</td>
            <td>{record.domesticTrackingNo}</td>
            <td>{record.slsTrackingNo}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}
