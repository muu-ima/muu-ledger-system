import {
  tabDescriptions,
  type WorkspaceTab,
} from "@/app/components/ledger-workspace/ledgerWorkspaceConfig";

type LedgerWorkspaceTopProps = {
  activeTab: WorkspaceTab;
  resultCount: number;
};

export function LedgerWorkspaceTop({
  activeTab,
  resultCount,
}: LedgerWorkspaceTopProps) {
  return (
    <section className="ledgerTop">
      <div>
        <h1>{activeTab}</h1>
        <p>{tabDescriptions[activeTab]}</p>
      </div>
      <div className="ledgerTopActions">
        <div className="resultCount">該当 {resultCount} 件</div>
      </div>
    </section>
  );
}
