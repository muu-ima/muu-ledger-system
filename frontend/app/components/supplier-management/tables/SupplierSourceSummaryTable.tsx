import type { SupplierSource } from "@/types/supplier";

type SupplierSourceSummaryTableProps = {
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
  field: "purchasedFlag",
  onRowChange: SupplierSourceSummaryTableProps["onRowChange"],
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

export function SupplierSourceSummaryTable({
  onRowChange,
  onRowUpdate,
  sources,
  statusMessage,
}: SupplierSourceSummaryTableProps) {
  return (
    <>
      <table className="ledgerGrid supplierSourceGrid">
        <colgroup>
          <col className="rowNoCol" />
          <col className="skuCol" />
          <col className="verifyCol" />
          <col className="sourceCol" />
          <col className="dateCol" />
          <col className="sourceCol" />
          <col className="moneyCol" />
          <col className="nameCol" />
          <col className="dateCol" />
          <col className="moneyCol" />
          <col className="moneyCol" />
          <col className="actionCol" />
        </colgroup>
        <thead>
          <tr className="headerRow">
            <th>No</th>
            <th>SKU</th>
            <th>Order no.</th>
            <th>購入済み</th>
            <th>仕入日</th>
            <th>仕入れ先</th>
            <th>仕入れ</th>
            <th>商品名</th>
            <th>販売日</th>
            <th>販売額</th>
            <th>送料</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          {sources.map((source) => (
            <tr key={source.sku}>
              <td>{source.rowNo}</td>
              <td className="selectedCell">{source.sku}</td>
              <td>
                <input
                  className="ecSalesCellInput"
                  type="text"
                  value={source.orderNo}
                  onChange={(event) =>
                    onRowChange(source.sku, "orderNo", event.target.value)
                  }
                />
              </td>
              <td>{booleanSelect(source, "purchasedFlag", onRowChange)}</td>
              <td>
                <input
                  className="ecSalesCellInput"
                  type="text"
                  value={source.acquiredAt}
                  onChange={(event) =>
                    onRowChange(source.sku, "acquiredAt", event.target.value)
                  }
                />
              </td>
              <td>
                <input
                  className="ecSalesCellInput"
                  type="text"
                  value={source.supplier}
                  onChange={(event) =>
                    onRowChange(source.sku, "supplier", event.target.value)
                  }
                />
              </td>
              <td className="numberCell">
                <input
                  className="ecSalesCellInput"
                  type="text"
                  value={source.purchasePrice}
                  onChange={(event) =>
                    onRowChange(source.sku, "purchasePrice", event.target.value)
                  }
                />
              </td>
              <td className="nameCell">
                <input
                  className="ecSalesCellInput"
                  type="text"
                  value={source.itemName}
                  onChange={(event) =>
                    onRowChange(source.sku, "itemName", event.target.value)
                  }
                />
              </td>
              <td>
                <input
                  className="ecSalesCellInput"
                  type="text"
                  value={source.soldAt}
                  onChange={(event) =>
                    onRowChange(source.sku, "soldAt", event.target.value)
                  }
                />
              </td>
              <td className="numberCell">
                <input
                  className="ecSalesCellInput"
                  type="text"
                  value={source.saleAmount}
                  onChange={(event) =>
                    onRowChange(source.sku, "saleAmount", event.target.value)
                  }
                />
              </td>
              <td className="numberCell">
                <input
                  className="ecSalesCellInput"
                  type="text"
                  value={source.shippingCost}
                  onChange={(event) =>
                    onRowChange(source.sku, "shippingCost", event.target.value)
                  }
                />
              </td>
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
