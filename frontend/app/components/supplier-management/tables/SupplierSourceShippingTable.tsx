import { CopyableText } from "@/app/components/common/CopyableText";
import type { SupplierSource } from "@/types/supplier";

type SupplierSourceShippingTableProps = {
  onRowChange: (
    sku: string,
    field: keyof SupplierSource,
    value: string,
  ) => void;
  onRowUpdate: (row: SupplierSource) => void;
  sources: SupplierSource[];
  statusMessage: string;
};

function booleanSelect(
  source: SupplierSource,
  field: "yamatoSlipFlag" | "balanceCheckedFlag",
  onRowChange: SupplierSourceShippingTableProps["onRowChange"],
) {
  return (
    <select
      className="ecSalesCellInput"
      value={source[field]}
      onChange={(event) => onRowChange(source.sku, field, event.target.value)}
    >
      <option value="">-</option>
      <option value="TRUE">TRUE</option>
      <option value="FALSE">FALSE</option>
    </select>
  );
}

function editableCell(
  source: SupplierSource,
  field: keyof SupplierSource,
  onRowChange: SupplierSourceShippingTableProps["onRowChange"],
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

export function SupplierSourceShippingTable({
  onRowChange,
  onRowUpdate,
  sources,
  statusMessage,
}: SupplierSourceShippingTableProps) {
  return (
    <>
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
          <col className="sizeCol" />
          <col className="dateCol" />
          <col className="dateCol" />
          <col className="dateCol" />
          <col className="sourceCol" />
          <col className="sourceCol" />
          <col className="sourceCol" />
          <col className="sourceCol" />
          <col className="actionCol" />
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
            <th>サイズ</th>
            <th>発送チャット</th>
            <th>初回メール</th>
            <th>領収書印刷日</th>
            <th>国内追跡番号</th>
            <th>SLS追跡番号</th>
            <th>ヤマト控え有無</th>
            <th>収支チェック</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          {sources.map((source) => (
            <tr key={source.sku}>
              <td className="selectedCell">
                <CopyableText value={source.sku} />
              </td>
              {editableCell(source, "shippingSite", onRowChange)}
              {editableCell(source, "packer", onRowChange)}
              {editableCell(source, "actualWeight", onRowChange, "numberCell")}
              {editableCell(
                source,
                "dimensionalWeight",
                onRowChange,
                "numberCell",
              )}
              {editableCell(source, "length", onRowChange, "numberCell")}
              {editableCell(source, "width", onRowChange, "numberCell")}
              {editableCell(source, "height", onRowChange, "numberCell")}
              {editableCell(source, "size", onRowChange, "numberCell")}
              {editableCell(source, "shippingChatAt", onRowChange)}
              {editableCell(source, "firstMailAt", onRowChange)}
              {editableCell(source, "receiptPrintedAt", onRowChange)}
              {editableCell(source, "domesticTrackingNo", onRowChange)}
              {editableCell(source, "slsTrackingNo", onRowChange)}
              <td>{booleanSelect(source, "yamatoSlipFlag", onRowChange)}</td>
              <td>{booleanSelect(source, "balanceCheckedFlag", onRowChange)}</td>
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
