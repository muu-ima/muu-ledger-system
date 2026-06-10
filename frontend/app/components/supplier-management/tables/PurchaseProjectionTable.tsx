import type { PurchaseProjectionRow } from "@/types/supplier";

type PurchaseProjectionColumn = {
  className: string;
  editable?: boolean;
  key: keyof PurchaseProjectionRow;
  label: string;
};

const purchaseProjectionColumns: PurchaseProjectionColumn[] = [
  { key: "sku", label: "SKU", className: "skuCol" },
  { key: "orderNo", label: "Order no.", className: "verifyCol" },
  { key: "acquiredAt", label: "仕入れ日", className: "dateCol" },
  { key: "supplier", label: "仕入れ先", className: "sourceCol" },
  { key: "purchasePrice", label: "仕入れ金額", className: "moneyCol" },
  { key: "category", label: "品目", className: "sourceCol", editable: true },
  { key: "accessories", label: "付属品", className: "noteCol", editable: true },
  { key: "conditionLabel", label: "状態", className: "sourceCol", editable: true },
  { key: "description", label: "備考", className: "noteCol", editable: true },
  { key: "photoUrl", label: "写真", className: "noteCol", editable: true },
  { key: "itemName", label: "商品名", className: "nameCol" },
  { key: "soldAt", label: "販売日", className: "dateCol" },
  { key: "soldTo", label: "販売先", className: "sourceCol", editable: true },
  { key: "saleAmount", label: "販売金額", className: "moneyCol" },
];

type PurchaseProjectionTableProps = {
  rows: PurchaseProjectionRow[];
  statusMessage: string;
  onRowChange: (
    sku: string,
    field: keyof PurchaseProjectionRow,
    value: string,
  ) => void;
  onRowUpdate: (row: PurchaseProjectionRow) => void;
};

export function PurchaseProjectionTable({
  rows,
  statusMessage,
  onRowChange,
  onRowUpdate,
}: PurchaseProjectionTableProps) {
  return (
    <>
      <table className="ledgerGrid purchaseProjectionGrid">
        <colgroup>
          {purchaseProjectionColumns.map((column) => (
            <col key={column.key} className={column.className} />
          ))}
          <col className="actionCol" />
        </colgroup>
        <thead>
          <tr className="headerRow">
            {purchaseProjectionColumns.map((column) => (
              <th key={column.key}>{column.label}</th>
            ))}
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          {rows.map((row) => (
            <tr key={row.sku}>
              {purchaseProjectionColumns.map((column) => (
                <td
                  key={column.key}
                  className={
                    column.key === "sku"
                      ? "selectedCell"
                      : ["purchasePrice", "saleAmount"].includes(column.key)
                        ? "numberCell"
                        : column.key === "itemName"
                          ? "nameCell"
                          : undefined
                  }
                >
                  {column.editable ? (
                    <input
                      className="ecSalesCellInput"
                      type="text"
                      value={row[column.key]}
                      onChange={(event) =>
                        onRowChange(row.sku, column.key, event.target.value)
                      }
                    />
                  ) : (
                    row[column.key]
                  )}
                </td>
              ))}
              <td className="actionCell">
                <button type="button" onClick={() => onRowUpdate(row)}>
                  更新
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
      {statusMessage ? (
        <div className="ecSalesUpdateStatus">{statusMessage}</div>
      ) : null}
    </>
  );
}
