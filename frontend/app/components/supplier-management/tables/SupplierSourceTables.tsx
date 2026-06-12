import { DragScrollArea } from "@/app/components/common/DragScrollArea";
import { PurchaseProjectionTable } from "@/app/components/supplier-management/tables/PurchaseProjectionTable";
import { SupplierSourceDetailTable } from "@/app/components/supplier-management/tables/SupplierSourceDetailTable";
import { SupplierSourceShippingTable } from "@/app/components/supplier-management/tables/SupplierSourceShippingTable";
import { SupplierSourceSummaryTable } from "@/app/components/supplier-management/tables/SupplierSourceSummaryTable";
import {
  type PurchaseProjectionRow,
  type SupplierDataView,
  type SupplierSource,
  type SupplierSourceView,
} from "@/types/supplier";

type SupplierSourceTablesProps = {
  dataView: SupplierDataView;
  purchaseProjectionRows: PurchaseProjectionRow[];
  purchaseProjectionStatus: string;
  sourceView: SupplierSourceView;
  sourceStatusMessage: string;
  sources: SupplierSource[];
  onPurchaseProjectionRowChange: (
    sku: string,
    field: keyof PurchaseProjectionRow,
    value: string,
  ) => void;
  onPurchaseProjectionRowSave: (row: PurchaseProjectionRow) => void;
  onSourceRowChange: (
    sku: string,
    field: keyof SupplierSource,
    value: string,
  ) => void;
  onSourceRowSave: (row: SupplierSource) => void;
};

export function SupplierSourceTables({
  dataView,
  purchaseProjectionRows,
  purchaseProjectionStatus,
  sourceView,
  sourceStatusMessage,
  sources,
  onPurchaseProjectionRowChange,
  onPurchaseProjectionRowSave,
  onSourceRowChange,
  onSourceRowSave,
}: SupplierSourceTablesProps) {
  return (
    <DragScrollArea>
      {dataView === "仕入れ元データ" && sourceView === "要約" ? (
        <SupplierSourceSummaryTable
          sources={sources}
          statusMessage={sourceStatusMessage}
          onRowChange={onSourceRowChange}
          onRowUpdate={onSourceRowSave}
        />
      ) : null}
      {dataView === "仕入れ元データ" && sourceView === "発送・梱包" ? (
        <SupplierSourceShippingTable
          sources={sources}
          statusMessage={sourceStatusMessage}
          onRowChange={onSourceRowChange}
          onRowUpdate={onSourceRowSave}
        />
      ) : null}
      {dataView === "仕入れ元データ" && sourceView === "詳細・原票" ? (
        <SupplierSourceDetailTable
          sources={sources}
          statusMessage={sourceStatusMessage}
          onRowChange={onSourceRowChange}
          onRowUpdate={onSourceRowSave}
        />
      ) : null}
      {dataView === "仕入れ表への反映" ? (
        <PurchaseProjectionTable
          rows={purchaseProjectionRows}
          statusMessage={purchaseProjectionStatus}
          onRowChange={onPurchaseProjectionRowChange}
          onRowUpdate={onPurchaseProjectionRowSave}
        />
      ) : null}
    </DragScrollArea>
  );
}
