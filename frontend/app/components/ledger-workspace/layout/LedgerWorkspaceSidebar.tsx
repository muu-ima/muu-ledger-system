import {
  tabs,
  type WorkspaceTab,
} from "@/app/components/ledger-workspace/ledgerWorkspaceConfig";
import { LedgerWorkspaceSidebarFilters } from "@/app/components/ledger-workspace/layout/sidebar/LedgerWorkspaceSidebarFilters";
import {
  sidebarBasicInputs,
  sidebarCheckboxSections,
} from "@/app/components/ledger-workspace/layout/sidebar/ledgerWorkspaceSidebarConfig";

type LedgerWorkspaceSidebarProps = {
  activeTab: WorkspaceTab;
  isOpen: boolean;
  query: string;
  onQueryChange: (value: string) => void;
  onTabChange: (tab: WorkspaceTab) => void;
  onToggle: () => void;
};

export function LedgerWorkspaceSidebar({
  activeTab,
  isOpen,
  query,
  onQueryChange,
  onTabChange,
  onToggle,
}: LedgerWorkspaceSidebarProps) {
  return (
    <aside className={isOpen ? "sidebar open" : "sidebar"}>
      <div className="sidebarHeader">
        <button
          className="iconButton"
          type="button"
          onClick={onToggle}
          aria-label={isOpen ? "フィルターを閉じる" : "フィルターを開く"}
        >
          {isOpen ? "‹" : "›"}
        </button>
        <span>フィルター</span>
        <span className="sidebarSpacer" />
      </div>

      <div className="sidebarBody">
        <div className="sheetList" aria-label="シート">
          {tabs.map((tab) => (
            <button
              key={tab}
              className={activeTab === tab ? "selected" : ""}
              type="button"
              onClick={() => onTabChange(tab)}
            >
              {tab}
            </button>
          ))}
        </div>

        <LedgerWorkspaceSidebarFilters
          checkboxSections={sidebarCheckboxSections}
          inputConfigs={sidebarBasicInputs}
          query={query}
          onQueryChange={onQueryChange}
        />

        <button className="filterButton" type="button">
          絞り込む
        </button>
      </div>
    </aside>
  );
}
