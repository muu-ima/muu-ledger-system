import { getLedgerItems } from "@/lib/ledger";
import LedgerWorkspace from "@/app/components/LedgerWorkspace";

export default async function Home() {
  const items = await getLedgerItems();
  return <LedgerWorkspace items={items} />;
}
