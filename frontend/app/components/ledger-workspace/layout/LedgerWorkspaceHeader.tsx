import type { WorkspaceTab } from "@/app/components/ledger-workspace/ledgerWorkspaceConfig";

type LedgerWorkspaceHeaderProps = {
  activeTab: WorkspaceTab;
};

export function LedgerWorkspaceHeader({
  activeTab,
}: LedgerWorkspaceHeaderProps) {
  return (
    <header className="appHeader">
      <div className="appHeaderBrand">
        <div className="brand">Kobutsu Ledger</div>
        <p>古物台帳・EC販売・仕入れ管理</p>
      </div>

      <div className="appHeaderCurrent" aria-label="現在地">
        <span className="appHeaderLabel">現在地</span>
        <strong>{activeTab}</strong>
      </div>
    </header>
  );
}
