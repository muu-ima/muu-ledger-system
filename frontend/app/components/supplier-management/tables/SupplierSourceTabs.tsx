import {
  supplierSourceViews,
  type SupplierSourceView,
} from "@/types/supplier";

type SupplierSourceTabsProps = {
  sourceView: SupplierSourceView;
  onSourceViewChange: (view: SupplierSourceView) => void;
};

export function SupplierSourceTabs({
  sourceView,
  onSourceViewChange,
}: SupplierSourceTabsProps) {
  return (
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
  );
}
