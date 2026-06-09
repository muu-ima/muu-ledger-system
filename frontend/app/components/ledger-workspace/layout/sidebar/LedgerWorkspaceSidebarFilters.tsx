import type {
  SidebarCheckboxSection,
  SidebarInputConfig,
} from "@/app/components/ledger-workspace/layout/sidebar/ledgerWorkspaceSidebarConfig";

type LedgerWorkspaceSidebarFiltersProps = {
  checkboxSections: SidebarCheckboxSection[];
  inputConfigs: SidebarInputConfig[];
  query: string;
  onQueryChange: (value: string) => void;
};

export function LedgerWorkspaceSidebarFilters({
  checkboxSections,
  inputConfigs,
  query,
  onQueryChange,
}: LedgerWorkspaceSidebarFiltersProps) {
  return (
    <>
      {checkboxSections.map((section) => (
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
        {inputConfigs.map((input) =>
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
    </>
  );
}
