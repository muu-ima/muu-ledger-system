import type { SupplierSource } from "@/types/supplier";

type SupplierSourceDetailTableProps = {
  onRowChange: (
    sku: string,
    field: keyof SupplierSource,
    value: string,
  ) => void;
  onRowUpdate: (row: SupplierSource) => void;
  sources: SupplierSource[];
  statusMessage: string;
};

function editableCell(
  source: SupplierSource,
  field: keyof SupplierSource,
  onRowChange: SupplierSourceDetailTableProps["onRowChange"],
  className?: string,
) {
  return (
    <td className={className}>
      <input
        className="ecSalesCellInput"
        type="text"
        value={source[field]}
        onChange={(event) => onRowChange(source.sku, field, event.target.value)}
      />
    </td>
  );
}

export function SupplierSourceDetailTable({
  onRowChange,
  onRowUpdate,
  sources,
  statusMessage,
}: SupplierSourceDetailTableProps) {
  return (
    <>
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
          <col className="actionCol" />
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
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          {sources.map((source) => (
            <tr key={source.sku}>
              <td>{source.rowNo}</td>
              <td className="selectedCell">{source.sku}</td>
              {editableCell(source, "account", onRowChange)}
              {editableCell(source, "orderNo", onRowChange)}
              {editableCell(source, "soldAt", onRowChange)}
              {editableCell(source, "country", onRowChange)}
              {editableCell(source, "mag", onRowChange)}
              {editableCell(source, "points", onRowChange, "numberCell")}
              {editableCell(source, "note", onRowChange)}
              {editableCell(source, "itemName", onRowChange, "nameCell")}
              <td className="actionCell">
                <button type="button" onClick={() => onRowUpdate(source)}>
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
