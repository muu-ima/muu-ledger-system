import { LedgerIntakeSection } from "@/app/components/ledger-workspace/sections/LedgerIntakeSection";
import { LedgerPartySection } from "@/app/components/ledger-workspace/sections/LedgerPartySection";
import { LedgerPayoutSection } from "@/app/components/ledger-workspace/sections/LedgerPayoutSection";
import type { LedgerItem } from "@/types/ledger";

export function LedgerRecordSections({
  items,
}: {
  items: LedgerItem[];
}) {
  return (
    <div className="ledgerSections">
      <LedgerIntakeSection items={items} />
      <LedgerPayoutSection items={items} />
      <LedgerPartySection items={items} />
    </div>
  );
}
