import { CopyableText } from "@/app/components/common/CopyableText";
import { DragScrollArea } from "@/app/components/common/DragScrollArea";
import type { LedgerItem } from "@/types/ledger";

function isSold(item: LedgerItem) {
  return item.status === "sold" || Boolean(item.soldAt);
}

function buyerAddress(item: LedgerItem) {
  return [item.buyerAddress1, item.buyerAddress2, item.buyerAddress3]
    .filter(Boolean)
    .join(" ");
}

function linkSourceLabel(source: string) {
  if (source === "sales") return "EC販売";
  if (source === "shopee_orders") return "Shopee補完";
  return "";
}

function matchTypeLabel(matchType: string) {
  if (matchType === "order_id") return "Order ID一致";
  if (matchType === "sku") return "SKU候補";
  return "";
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
            <col className="addressCol" />
            <col className="buyerCol" />
            <col className="buyerCol" />
            <col className="buyerCol" />
            <col className="buyerCol" />
            <col className="buyerCol" />
            <col className="addressCol" />
            <col className="sourceCol" />
            <col className="sourceCol" />
          </colgroup>
          <thead>
            <tr className="headerRow">
              <th>SKU</th>
              <th>仕入れ確認</th>
              <th>仕入れ相手方</th>
              <th>国名</th>
              <th>buyer ID</th>
              <th>氏名</th>
              <th>市</th>
              <th>州</th>
              <th>郵便番号</th>
              <th>送付先住所</th>
              <th>反映元</th>
              <th>照合</th>
            </tr>
          </thead>
          <tbody>
            {items.map((item) => {
              const sold = isSold(item);
              return (
                <tr key={item.id}>
                  <td className="selectedCell">
                    <CopyableText value={item.managementNo} />
                  </td>
                  <td>{item.sellerIdentification}</td>
                  <td>{item.sellerName || item.sellerAddress}</td>
                  <td>{sold ? item.buyerCountry : ""}</td>
                  <td>
                    {sold ? <CopyableText value={item.buyerId} /> : ""}
                  </td>
                  <td>{sold ? item.buyerName : ""}</td>
                  <td>{sold ? item.buyerCity : ""}</td>
                  <td>{sold ? item.buyerState : ""}</td>
                  <td>{sold ? item.buyerPostalCode : ""}</td>
                  <td>{sold ? buyerAddress(item) : ""}</td>
                  <td className={item.ledgerLinkSource === "shopee_orders" ? "supplementCell" : ""}>
                    {sold ? linkSourceLabel(item.ledgerLinkSource) : ""}
                  </td>
                  <td className={item.shopeeMatchType === "sku" ? "candidateCell" : ""}>
                    {sold ? matchTypeLabel(item.shopeeMatchType) : ""}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </DragScrollArea>
    </section>
  );
}
