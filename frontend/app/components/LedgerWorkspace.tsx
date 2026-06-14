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
import ExchangeRateWorkspace from "@/app/components/ExchangeRateWorkspace";
import PaymentWorkspace from "@/app/components/PaymentWorkspace";
import SupplierManagement from "@/app/components/SupplierManagement";
import type { LedgerItem } from "@/types/ledger";

export default function LedgerWorkspace({ items }: { items: LedgerItem[] }) {
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [activeTab, setActiveTab] = useState<WorkspaceTab>("古物台帳");
  const [isCompactLayout, setIsCompactLayout] = useState(false);

  useEffect(() => {
    const mediaQuery = window.matchMedia("(max-width: 768px)");
    const syncLayout = () => setIsCompactLayout(mediaQuery.matches);

    syncLayout();
    mediaQuery.addEventListener("change", syncLayout);

    return () => mediaQuery.removeEventListener("change", syncLayout);
  }, []);

  useEffect(() => {
    const saved = window.localStorage.getItem("kobutsu:sidebar-open");
    if (saved) setSidebarOpen(saved === "1");
  }, []);

  useEffect(() => {
    window.localStorage.setItem("kobutsu:sidebar-open", sidebarOpen ? "1" : "0");
  }, [sidebarOpen]);

  useEffect(() => {
    if (isCompactLayout) {
      setSidebarOpen(false);
    }
  }, [isCompactLayout]);

  const resultCount = items.length;
  const handleTabChange = (tab: WorkspaceTab) => {
    setActiveTab(tab);
    if (isCompactLayout) {
      setSidebarOpen(false);
    }
  };

  return (
    <div className="workspace">
      <LedgerWorkspaceHeader
        activeTab={activeTab}
        onMenuToggle={() => setSidebarOpen((value) => !value)}
      />

      <div className="workArea">
        {isCompactLayout && sidebarOpen ? (
          <button
            className="sidebarBackdrop"
            type="button"
            aria-label="メニューを閉じる"
            onClick={() => setSidebarOpen(false)}
          />
        ) : null}

        <LedgerWorkspaceSidebar
          activeTab={activeTab}
          isOpen={sidebarOpen}
          onTabChange={handleTabChange}
          onToggle={() => setSidebarOpen((value) => !value)}
        />

        <main className="ledgerMain">
          {activeTab === "仕入れ管理" ? (
            <SupplierManagement />
          ) : activeTab === "EC販売" ? (
            <EcSalesWorkspace />
          ) : activeTab === "為替レート" ? (
            <ExchangeRateWorkspace />
          ) : activeTab === "ペイメント" ? (
            <PaymentWorkspace />
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
