import { DragScrollArea } from "@/app/components/common/DragScrollArea";
import type { LedgerItem } from "@/types/ledger";

function isSold(item: LedgerItem) {
  return item.status === "sold" || Boolean(item.soldAt);
}

export function LedgerPartySection({
  items,
}: {
  items: LedgerItem[];
}) {
  return (
    <section className="ledgerSection">
      <div className="sectionTitle">
        <h2>相手方・確認</h2>
        <span>本人確認・買主情報</span>
      </div>
      <DragScrollArea>
        <table className="ledgerGrid partyGrid">
          <colgroup>
            <col className="skuCol" />
            <col className="verifyCol" />
            <col className="buyerCol" />
            <col className="buyerCol" />
            <col className="addressCol" />
          </colgroup>
          <thead>
            <tr className="headerRow">
              <th>SKU</th>
              <th>仕入れ確認</th>
              <th>国名</th>
              <th>buyer ID</th>
              <th>送付先住所</th>
            </tr>
          </thead>
          <tbody>
            {items.map((item) => {
              const sold = isSold(item);
              return (
                <tr key={item.id}>
                  <td className="selectedCell">{item.managementNo}</td>
                  <td>{item.sellerIdentification}</td>
                  <td>{sold ? "アメリカ" : ""}</td>
                  <td>{sold ? "buyer_sample" : ""}</td>
                  <td>{sold ? "Sample address, city, country" : ""}</td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </DragScrollArea>
    </section>
  );
}
