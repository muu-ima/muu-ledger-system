import {
  tabs,
  type WorkspaceTab,
} from "@/app/components/ledger-workspace/ledgerWorkspaceConfig";

type LedgerWorkspaceSidebarProps = {
  activeTab: WorkspaceTab;
  isOpen: boolean;
  onTabChange: (tab: WorkspaceTab) => void;
  onToggle: () => void;
};

export function LedgerWorkspaceSidebar({
  activeTab,
  isOpen,
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
          aria-label={isOpen ? "メニューを閉じる" : "メニューを開く"}
        >
          {isOpen ? "‹" : "›"}
        </button>
        <span>メニュー</span>
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
      </div>
    </aside>
  );
}
