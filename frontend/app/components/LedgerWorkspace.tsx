"use client";

import { useEffect, useMemo, useState } from "react";
import { LedgerWorkspaceHeader } from "@/app/components/ledger-workspace/layout/LedgerWorkspaceHeader";
import { LedgerWorkspaceSidebar } from "@/app/components/ledger-workspace/layout/LedgerWorkspaceSidebar";
import { LedgerWorkspaceTop } from "@/app/components/ledger-workspace/layout/LedgerWorkspaceTop";
import {
  tabs,
  type WorkspaceTab,
} from "@/app/components/ledger-workspace/ledgerWorkspaceConfig";
import { LedgerRecordSections } from "@/app/components/ledger-workspace/sections/LedgerRecordSections";
import SupplierManagement from "@/app/components/SupplierManagement";
import type { LedgerItem } from "@/types/ledger";

export default function LedgerWorkspace({ items }: { items: LedgerItem[] }) {
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [query, setQuery] = useState("");
  const [activeTab, setActiveTab] = useState<WorkspaceTab>("古物台帳");

  useEffect(() => {
    const saved = window.localStorage.getItem("kobutsu:sidebar-open");
    if (saved) setSidebarOpen(saved === "1");
  }, []);

  useEffect(() => {
    window.localStorage.setItem("kobutsu:sidebar-open", sidebarOpen ? "1" : "0");
  }, [sidebarOpen]);

  const visibleItems = useMemo(() => {
    const needle = query.trim().toLowerCase();
    if (!needle) return items;

    return items.filter((item) =>
      [
        item.managementNo,
        item.category,
        item.itemName,
        item.acquiredFrom,
        item.soldTo,
      ]
        .join(" ")
        .toLowerCase()
        .includes(needle),
    );
  }, [items, query]);

  const resultCount = visibleItems.length;

  return (
    <div className="workspace">
      <LedgerWorkspaceHeader
        activeTab={activeTab}
        onTabChange={setActiveTab}
      />

      <div className="workArea">
        <LedgerWorkspaceSidebar
          activeTab={activeTab}
          isOpen={sidebarOpen}
          query={query}
          onQueryChange={setQuery}
          onTabChange={setActiveTab}
          onToggle={() => setSidebarOpen((value) => !value)}
        />

        <main className="ledgerMain">
          {activeTab === "仕入れ管理" ? (
            <SupplierManagement />
          ) : (
            <>
              <LedgerWorkspaceTop
                activeTab={activeTab}
                resultCount={resultCount}
              />
              <LedgerRecordSections items={visibleItems} />
            </>
          )}
        </main>
      </div>
    </div>
  );
}
