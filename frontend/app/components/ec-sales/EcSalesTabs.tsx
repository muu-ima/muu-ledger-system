import { ecSalesViews, type EcSalesView } from "@/types/ecSales";

type EcSalesTabsProps = {
  activeView: EcSalesView;
  onViewChange: (view: EcSalesView) => void;
};

export function EcSalesTabs({
  activeView,
  onViewChange,
}: EcSalesTabsProps) {
  return (
    <div
      className="tableTabs primaryTabs"
      role="tablist"
      aria-label="EC販売データ"
    >
      {ecSalesViews.map((view) => (
        <button
          key={view}
          type="button"
          role="tab"
          aria-selected={activeView === view}
          className={activeView === view ? "active" : ""}
          onClick={() => onViewChange(view)}
        >
          {view}
        </button>
      ))}
    </div>
  );
}
