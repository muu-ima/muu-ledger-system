"use client";

import { useEffect, useState } from "react";
import { LedgerWorkspaceHeader } from "@/app/components/ledger-workspace/layout/LedgerWorkspaceHeader";
import { LedgerWorkspaceSidebar } from "@/app/components/ledger-workspace/layout/LedgerWorkspaceSidebar";
import { LedgerWorkspaceTop } from "@/app/components/ledger-workspace/layout/LedgerWorkspaceTop";
import {
  tabs,
  type WorkspaceTab,
} from "@/app/components/ledger-workspace/ledgerWorkspaceConfig";
import { LedgerRecordSections } from "@/app/components/ledger-workspace/sections/LedgerRecordSections";
import EcSalesWorkspace from "@/app/components/EcSalesWorkspace";
import SupplierManagement from "@/app/components/SupplierManagement";
import type { LedgerItem } from "@/types/ledger";

export default function LedgerWorkspace({ items }: { items: LedgerItem[] }) {
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [activeTab, setActiveTab] = useState<WorkspaceTab>("古物台帳");

  useEffect(() => {
    const saved = window.localStorage.getItem("kobutsu:sidebar-open");
    if (saved) setSidebarOpen(saved === "1");
  }, []);

  useEffect(() => {
    window.localStorage.setItem("kobutsu:sidebar-open", sidebarOpen ? "1" : "0");
  }, [sidebarOpen]);

  const resultCount = items.length;

  return (
    <div className="workspace">
      <LedgerWorkspaceHeader
        activeTab={activeTab}
      />

      <div className="workArea">
        <LedgerWorkspaceSidebar
          activeTab={activeTab}
          isOpen={sidebarOpen}
          onTabChange={setActiveTab}
          onToggle={() => setSidebarOpen((value) => !value)}
        />

        <main className="ledgerMain">
          {activeTab === "仕入れ管理" ? (
            <SupplierManagement />
          ) : activeTab === "EC販売" ? (
            <EcSalesWorkspace />
          ) : (
            <>
              <LedgerWorkspaceTop
                activeTab={activeTab}
                resultCount={resultCount}
              />
              <LedgerRecordSections items={items} />
            </>
          )}
        </main>
      </div>
    </div>
  );
}
