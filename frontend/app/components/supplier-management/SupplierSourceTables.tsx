import { PurchaseProjectionTable } from "@/app/components/supplier-management/PurchaseProjectionTable";
import { SupplierSourceDetailTable } from "@/app/components/supplier-management/SupplierSourceDetailTable";
import { SupplierSourceShippingTable } from "@/app/components/supplier-management/SupplierSourceShippingTable";
import { SupplierSourceSummaryTable } from "@/app/components/supplier-management/SupplierSourceSummaryTable";
import { SupplierSourceTabs } from "@/app/components/supplier-management/SupplierSourceTabs";
import {
  type SupplierDataView,
  type SupplierSource,
  type SupplierSourceView,
} from "@/types/supplier";

type SupplierSourceTablesProps = {
  dataView: SupplierDataView;
  sourceView: SupplierSourceView;
  sources: SupplierSource[];
  onDataViewChange: (view: SupplierDataView) => void;
  onSourceViewChange: (view: SupplierSourceView) => void;
};

export function SupplierSourceTables({
  dataView,
  sourceView,
  sources,
  onDataViewChange,
  onSourceViewChange,
}: SupplierSourceTablesProps) {
  return (
    <section className="ledgerSection">
      <div className="sectionTitle">
        <h2>仕入れ管理データ</h2>
        <span>保存済みデータと仕入れ表への反映内容</span>
      </div>
      <SupplierSourceTabs
        dataView={dataView}
        sourceView={sourceView}
        onDataViewChange={onDataViewChange}
        onSourceViewChange={onSourceViewChange}
      />
      <div className="ledgerTableFrame">
        {dataView === "仕入れ元データ" && sourceView === "要約" ? (
          <SupplierSourceSummaryTable sources={sources} />
        ) : null}
        {dataView === "仕入れ元データ" && sourceView === "発送・梱包" ? (
          <SupplierSourceShippingTable sources={sources} />
        ) : null}
        {dataView === "仕入れ元データ" && sourceView === "詳細・原票" ? (
          <SupplierSourceDetailTable sources={sources} />
        ) : null}
        {dataView === "仕入れ表への反映" ? (
          <PurchaseProjectionTable sources={sources} />
        ) : null}
      </div>
    </section>
  );
}
