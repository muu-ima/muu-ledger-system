import type { EcSalesRecord, EcSalesSummaryView } from "@/types/ecSales";

type EcSalesColumn = {
  className: string;
  cellClassName?: string;
  editable?: boolean;
  key: keyof EcSalesRecord;
  label: string;
};

const allColumns: EcSalesColumn[] = [
  { key: "bundledFlag", label: "同梱", className: "typeCol" },
  { key: "sku", label: "SKU", className: "skuCol", cellClassName: "selectedCell" },
  { key: "orderNo", label: "Order number", className: "verifyCol", editable: true },
  { key: "purchaseDate", label: "仕入れ日", className: "dateCol" },
  { key: "listedAt", label: "出品日", className: "dateCol" },
  { key: "soldAt", label: "販売日", className: "dateCol", editable: true },
  { key: "payoutAt", label: "出金日", className: "dateCol", editable: true },
  { key: "itemName", label: "商品名", className: "nameCol", cellClassName: "nameCell" },
  {
    key: "purchasePriceJpy",
    label: "仕入れ金額",
    className: "moneyCol",
    cellClassName: "numberCell",
  },
  {
    key: "saleAmountRaw",
    label: "販売金額",
    className: "moneyCol",
    cellClassName: "numberCell",
    editable: true,
  },
  {
    key: "saleAmountJpy",
    label: "販売金額(円)",
    className: "moneyCol",
    cellClassName: "numberCell",
  },
  {
    key: "totalFeesRaw",
    label: "手数料合計",
    className: "moneyCol",
    cellClassName: "numberCell",
  },
  { key: "adFeeRaw", label: "広告費", className: "moneyCol", cellClassName: "numberCell" },
  {
    key: "marketplaceFeeRaw",
    label: "shopee手数料",
    className: "moneyCol",
    cellClassName: "numberCell",
  },
  {
    key: "payoutAmountRaw",
    label: "Payout金額",
    className: "moneyCol",
    cellClassName: "numberCell",
  },
  {
    key: "saleExchangeRate",
    label: "販売時為替",
    className: "rateCol",
    cellClassName: "numberCell",
  },
  {
    key: "payoutExchangeRate",
    label: "出金時為替",
    className: "rateCol",
    cellClassName: "numberCell",
  },
  {
    key: "receivedAmountJpy",
    label: "受取金額",
    className: "moneyCol",
    cellClassName: "numberCell",
    editable: true,
  },
  {
    key: "overseasShippingYen",
    label: "海外送料",
    className: "moneyCol",
    cellClassName: "numberCell",
  },
  {
    key: "feeTaxRefundJpy",
    label: "手数料還付",
    className: "moneyCol",
    cellClassName: "numberCell",
  },
  {
    key: "purchaseTaxRefundJpy",
    label: "消費税還付",
    className: "moneyCol",
    cellClassName: "numberCell",
  },
  {
    key: "profitJpy",
    label: "最終損益",
    className: "moneyCol",
    cellClassName: "numberCell",
    editable: true,
  },
  {
    key: "profitRate",
    label: "利益率",
    className: "rateCol",
    cellClassName: "numberCell",
    editable: true,
  },
  {
    key: "daysToSell",
    label: "売れるまで",
    className: "qtyCol",
    cellClassName: "numberCell",
    editable: true,
  },
  { key: "domesticTrackingNo", label: "国内送り状", className: "sourceCol", editable: true },
  { key: "slsTrackingNo", label: "SLS送り状", className: "sourceCol", editable: true },
];

const summaryColumns: Record<EcSalesSummaryView, EcSalesColumn[]> = {
  全体: allColumns,
  収益: allColumns.filter((column) =>
    [
      "sku",
      "orderNo",
      "itemName",
      "purchasePriceJpy",
      "saleAmountRaw",
      "saleAmountJpy",
      "receivedAmountJpy",
      "profitJpy",
      "profitRate",
    ].includes(column.key),
  ),
  "手数料・為替": allColumns.filter((column) =>
    [
      "sku",
      "orderNo",
      "saleAmountRaw",
      "totalFeesRaw",
      "adFeeRaw",
      "marketplaceFeeRaw",
      "payoutAmountRaw",
      "saleExchangeRate",
      "payoutExchangeRate",
      "receivedAmountJpy",
      "feeTaxRefundJpy",
      "purchaseTaxRefundJpy",
    ].includes(column.key),
  ),
  "配送・日付": allColumns.filter((column) =>
    [
      "bundledFlag",
      "sku",
      "orderNo",
      "purchaseDate",
      "listedAt",
      "soldAt",
      "payoutAt",
      "overseasShippingYen",
      "daysToSell",
      "domesticTrackingNo",
      "slsTrackingNo",
    ].includes(column.key),
  ),
};

type EcSalesTableProps = {
  onRecordChange: (
    sku: string,
    orderNo: string,
    field: keyof EcSalesRecord,
    value: string,
  ) => void;
  onRecordUpdate: (record: EcSalesRecord) => void;
  records: EcSalesRecord[];
  summaryView: EcSalesSummaryView;
};

function inputTypeForColumn(column: EcSalesColumn) {
  if (["soldAt", "payoutAt"].includes(column.key)) return "date";
  if (
    [
      "receivedAmountJpy",
      "profitJpy",
      "profitRate",
      "daysToSell",
    ].includes(column.key)
  ) {
    return "number";
  }

  return "text";
}

export function EcSalesTable({
  onRecordChange,
  onRecordUpdate,
  records,
  summaryView,
}: EcSalesTableProps) {
  const columns = summaryColumns[summaryView];

  return (
    <table className="ledgerGrid ecSalesGrid">
      <colgroup>
        {columns.map((column) => (
          <col key={column.key} className={column.className} />
        ))}
        <col className="actionCol" />
      </colgroup>
      <thead>
        <tr className="headerRow">
          {columns.map((column) => (
            <th key={column.key}>{column.label}</th>
          ))}
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        {records.map((record) => (
          <tr key={`${record.sku}-${record.orderNo}`}>
            {columns.map((column) => (
              <td key={column.key} className={column.cellClassName}>
                {column.editable ? (
                  <input
                    className="ecSalesCellInput"
                    type={inputTypeForColumn(column)}
                    value={record[column.key]}
                    onChange={(event) =>
                      onRecordChange(
                        record.sku,
                        record.orderNo,
                        column.key,
                        event.target.value,
                      )
                    }
                  />
                ) : (
                  record[column.key]
                )}
              </td>
            ))}
            <td className="actionCell">
              <button type="button" onClick={() => onRecordUpdate(record)}>
                更新
              </button>
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}
