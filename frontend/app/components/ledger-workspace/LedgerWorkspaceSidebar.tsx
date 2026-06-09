import {
  categories,
  supplierOptions,
  tabs,
  type WorkspaceTab,
} from "@/app/components/ledger-workspace/ledgerWorkspaceConfig";

type SidebarCheckboxSection = {
  options: readonly string[];
  title: string;
};

type SidebarInputConfig = {
  placeholder: string;
  type?: "controlled" | "static";
};

const sidebarCheckboxSections: SidebarCheckboxSection[] = [
  {
    title: "商品カテゴリ",
    options: categories,
  },
  {
    title: "仕入先",
    options: supplierOptions,
  },
];

const sidebarBasicInputs: SidebarInputConfig[] = [
  {
    placeholder: "SKU / 商品名 / 仕入先",
    type: "controlled",
  },
  {
    placeholder: "注文番号",
  },
  {
    placeholder: "buyer ID",
  },
];

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

        {sidebarCheckboxSections.map((section) => (
          <fieldset key={section.title}>
            <legend>{section.title}</legend>
            {section.options.map((option) => (
              <label key={option} className="checkRow">
                <input type="checkbox" />
                <span>{option}</span>
              </label>
            ))}
          </fieldset>
        ))}

        <fieldset>
          <legend>基本情報</legend>
          {sidebarBasicInputs.map((input) =>
            input.type === "controlled" ? (
              <input
                key={input.placeholder}
                value={query}
                onChange={(event) => onQueryChange(event.target.value)}
                placeholder={input.placeholder}
              />
            ) : (
              <input key={input.placeholder} placeholder={input.placeholder} />
            ),
          )}
        </fieldset>

        <button className="filterButton" type="button">
          絞り込む
        </button>
      </div>
    </aside>
  );
}
