import { CopyableText } from "@/app/components/common/CopyableText";
import type { EcSalesRecord, EcSalesSummaryView } from "@/types/ecSales";

type EcSalesColumn = {
  className: string;
  cellClassName?: string;
  editable?: boolean;
  key: keyof EcSalesRecord;
  label: string;
};

const editableColumns = new Set<keyof EcSalesRecord>([
  "orderNo",
  "soldAt",
  "payoutAt",
  "saleAmountRaw",
  "adFeeRaw",
  "marketplaceFeeRaw",
  "payoutAmountRaw",
  "saleExchangeRate",
  "payoutExchangeRate",
  "receivedAmountJpy",
  "overseasShippingYen",
  "feeTaxRefundJpy",
  "purchaseTaxRefundJpy",
  "profitJpy",
  "profitRate",
  "daysToSell",
  "domesticTrackingNo",
  "slsTrackingNo",
]);

const allColumns: EcSalesColumn[] = [
  { key: "bundledFlag", label: "同梱", className: "typeCol" },
  { key: "sku", label: "SKU", className: "skuCol", cellClassName: "selectedCell" },
  {
    key: "orderNo",
    label: "Order number",
    className: "verifyCol",
    editable: editableColumns.has("orderNo"),
  },
  { key: "purchaseDate", label: "仕入れ日", className: "dateCol" },
  { key: "listedAt", label: "出品日", className: "dateCol" },
  {
    key: "soldAt",
    label: "販売日",
    className: "dateCol",
    editable: editableColumns.has("soldAt"),
  },
  {
    key: "payoutAt",
    label: "出金日",
    className: "dateCol",
    editable: editableColumns.has("payoutAt"),
  },
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
    editable: editableColumns.has("saleAmountRaw"),
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
  {
    key: "adFeeRaw",
    label: "広告費",
    className: "moneyCol",
    cellClassName: "numberCell",
    editable: editableColumns.has("adFeeRaw"),
  },
  {
    key: "marketplaceFeeRaw",
    label: "shopee手数料",
    className: "moneyCol",
    cellClassName: "numberCell",
    editable: editableColumns.has("marketplaceFeeRaw"),
  },
  {
    key: "payoutAmountRaw",
    label: "Payout金額",
    className: "moneyCol",
    cellClassName: "numberCell",
    editable: editableColumns.has("payoutAmountRaw"),
  },
  {
    key: "saleExchangeRate",
    label: "販売時為替",
    className: "rateCol",
    cellClassName: "numberCell",
    editable: editableColumns.has("saleExchangeRate"),
  },
  {
    key: "payoutExchangeRate",
    label: "出金時為替",
    className: "rateCol",
    cellClassName: "numberCell",
    editable: editableColumns.has("payoutExchangeRate"),
  },
  {
    key: "receivedAmountJpy",
    label: "受取金額",
    className: "moneyCol",
    cellClassName: "numberCell",
    editable: editableColumns.has("receivedAmountJpy"),
  },
  {
    key: "overseasShippingYen",
    label: "海外送料",
    className: "moneyCol",
    cellClassName: "numberCell",
    editable: editableColumns.has("overseasShippingYen"),
  },
  {
    key: "feeTaxRefundJpy",
    label: "手数料還付",
    className: "moneyCol",
    cellClassName: "numberCell",
    editable: editableColumns.has("feeTaxRefundJpy"),
  },
  {
    key: "purchaseTaxRefundJpy",
    label: "消費税還付",
    className: "moneyCol",
    cellClassName: "numberCell",
    editable: editableColumns.has("purchaseTaxRefundJpy"),
  },
  {
    key: "profitJpy",
    label: "最終損益",
    className: "moneyCol",
    cellClassName: "numberCell",
    editable: editableColumns.has("profitJpy"),
  },
  {
    key: "profitRate",
    label: "利益率",
    className: "rateCol",
    cellClassName: "numberCell",
    editable: editableColumns.has("profitRate"),
  },
  {
    key: "daysToSell",
    label: "売れるまで",
    className: "qtyCol",
    cellClassName: "numberCell",
    editable: editableColumns.has("daysToSell"),
  },
  {
    key: "domesticTrackingNo",
    label: "国内送り状",
    className: "sourceCol",
    editable: editableColumns.has("domesticTrackingNo"),
  },
  {
    key: "slsTrackingNo",
    label: "SLS送り状",
    className: "sourceCol",
    editable: editableColumns.has("slsTrackingNo"),
  },
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
                  column.key === "orderNo" ? (
                    <span className="copyableInputGroup">
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
                      <CopyableText value={record.orderNo} showValue={false} />
                    </span>
                  ) : (
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
                  )
                ) : column.key === "sku" ? (
                  <CopyableText value={record.sku} />
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
