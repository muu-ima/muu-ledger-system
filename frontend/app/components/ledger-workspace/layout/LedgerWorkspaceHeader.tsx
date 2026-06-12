import type { WorkspaceTab } from "@/app/components/ledger-workspace/ledgerWorkspaceConfig";

type LedgerWorkspaceHeaderProps = {
  activeTab: WorkspaceTab;
  onMenuToggle: () => void;
};

export function LedgerWorkspaceHeader({
  activeTab,
  onMenuToggle,
}: LedgerWorkspaceHeaderProps) {
  return (
    <header className="appHeader">
      <button
        className="mobileMenuButton"
        type="button"
        onClick={onMenuToggle}
        aria-label="メニューを開く"
      >
        ☰
      </button>

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
