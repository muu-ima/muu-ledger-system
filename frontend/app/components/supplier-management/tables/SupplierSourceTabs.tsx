import {
  supplierDataViews,
  supplierSourceViews,
  type SupplierDataView,
  type SupplierSourceView,
} from "@/types/supplier";

type SupplierSourceTabsProps = {
  dataView: SupplierDataView;
  sourceView: SupplierSourceView;
  onDataViewChange: (view: SupplierDataView) => void;
  onSourceViewChange: (view: SupplierSourceView) => void;
};

export function SupplierSourceTabs({
  dataView,
  sourceView,
  onDataViewChange,
  onSourceViewChange,
}: SupplierSourceTabsProps) {
  return (
    <>
      <div
        className="tableTabs primaryTabs"
        role="tablist"
        aria-label="仕入れ管理データ"
      >
        {supplierDataViews.map((view) => (
          <button
            key={view}
            type="button"
            role="tab"
            aria-selected={dataView === view}
            className={dataView === view ? "active" : ""}
            onClick={() => onDataViewChange(view)}
          >
            {view}
          </button>
        ))}
      </div>

      {dataView === "仕入れ元データ" ? (
        <div className="tableTabs" role="tablist" aria-label="仕入れ元データ表示">
          {supplierSourceViews.map((view) => (
            <button
              key={view}
              type="button"
              role="tab"
              aria-selected={sourceView === view}
              className={sourceView === view ? "active" : ""}
              onClick={() => onSourceViewChange(view)}
            >
              {view}
            </button>
          ))}
        </div>
      ) : null}
    </>
  );
}
