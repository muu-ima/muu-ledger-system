"use client";

import { useState } from "react";
import { EcSalesSummaryTabs } from "@/app/components/ec-sales/EcSalesSummaryTabs";
import { EcSalesTabs } from "@/app/components/ec-sales/EcSalesTabs";
import { EcSalesTable } from "@/app/components/ec-sales/EcSalesTable";
import { ecSalesSampleRecords } from "@/lib/ecSalesSamples";
import type { EcSalesSummaryView, EcSalesView } from "@/types/ecSales";

export default function EcSalesWorkspace() {
  const [activeView, setActiveView] = useState<EcSalesView>("集計ビュー");
  const [summaryView, setSummaryView] = useState<EcSalesSummaryView>("全体");

  return (
    <>
      <section className="ledgerTop">
        <div>
          <h1>EC販売</h1>
          <p>販売、精算、送料、損益を確認するビュー</p>
        </div>
        <div className="ledgerTopActions">
          <div className="resultCount">
            該当 {ecSalesSampleRecords.length} 件
          </div>
        </div>
      </section>

      <div className="ledgerSections">
        <section className="ledgerSection">
          <div className="sectionTitle">
            <h2>EC販売集計</h2>
            <span>仕入れ表、仕入れ元データ、ペイメント、為替の合成ビュー</span>
          </div>
          <EcSalesTabs activeView={activeView} onViewChange={setActiveView} />
          {activeView === "集計ビュー" ? (
            <>
              <EcSalesSummaryTabs
                activeView={summaryView}
                onViewChange={setSummaryView}
              />
              <div className="ledgerTableFrame">
                <EcSalesTable
                  records={ecSalesSampleRecords}
                  summaryView={summaryView}
                />
              </div>
            </>
          ) : (
            <div className="emptyTableState">
              {activeView} の明細表示を準備中です
            </div>
          )}
        </section>
      </div>
    </>
  );
}
