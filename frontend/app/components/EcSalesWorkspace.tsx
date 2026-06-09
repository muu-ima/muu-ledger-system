"use client";

import { EcSalesTable } from "@/app/components/ec-sales/EcSalesTable";
import { ecSalesSampleRecords } from "@/lib/ecSalesSamples";

export default function EcSalesWorkspace() {
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
          <div className="ledgerTableFrame">
            <EcSalesTable records={ecSalesSampleRecords} />
          </div>
        </section>
      </div>
    </>
  );
}
