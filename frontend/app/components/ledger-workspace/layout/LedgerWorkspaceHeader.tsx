import {
  tabs,
  type WorkspaceTab,
} from "@/app/components/ledger-workspace/ledgerWorkspaceConfig";

type LedgerWorkspaceHeaderProps = {
  activeTab: WorkspaceTab;
  onTabChange: (tab: WorkspaceTab) => void;
};

export function LedgerWorkspaceHeader({
  activeTab,
  onTabChange,
}: LedgerWorkspaceHeaderProps) {
  return (
    <header className="appHeader">
      <div>
        <div className="brand">Kobutsu Ledger</div>
        <p>古物台帳・EC販売・仕入れ管理</p>
      </div>

      <nav className="topNav" aria-label="メインメニュー">
        {tabs.slice(0, 4).map((tab) => (
          <button
            key={tab}
            className={activeTab === tab ? "active" : ""}
            type="button"
            onClick={() => onTabChange(tab)}
          >
            {tab}
          </button>
        ))}
      </nav>
    </header>
  );
}
