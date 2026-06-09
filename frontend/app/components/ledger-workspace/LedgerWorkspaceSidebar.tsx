import {
  categories,
  supplierOptions,
  tabs,
  type WorkspaceTab,
} from "@/app/components/ledger-workspace/ledgerWorkspaceConfig";

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

        <fieldset>
          <legend>商品カテゴリ</legend>
          {categories.map((category) => (
            <label key={category} className="checkRow">
              <input type="checkbox" />
              <span>{category}</span>
            </label>
          ))}
        </fieldset>

        <fieldset>
          <legend>基本情報</legend>
          <input
            value={query}
            onChange={(event) => onQueryChange(event.target.value)}
            placeholder="SKU / 商品名 / 仕入先"
          />
          <input placeholder="注文番号" />
          <input placeholder="buyer ID" />
        </fieldset>

        <fieldset>
          <legend>仕入先</legend>
          {supplierOptions.map((supplier) => (
            <label key={supplier} className="checkRow">
              <input type="checkbox" />
              <span>{supplier}</span>
            </label>
          ))}
        </fieldset>

        <button className="filterButton" type="button">
          絞り込む
        </button>
      </div>
    </aside>
  );
}
