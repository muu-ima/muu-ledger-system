import {
  ecSalesSummaryViews,
  type EcSalesSummaryView,
} from "@/types/ecSales";

type EcSalesSummaryTabsProps = {
  activeView: EcSalesSummaryView;
  onViewChange: (view: EcSalesSummaryView) => void;
};

export function EcSalesSummaryTabs({
  activeView,
  onViewChange,
}: EcSalesSummaryTabsProps) {
  return (
    <div className="tableTabs" role="tablist" aria-label="EC販売集計表示">
      {ecSalesSummaryViews.map((view) => (
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
