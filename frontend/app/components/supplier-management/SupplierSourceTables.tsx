import { PurchaseProjectionTable } from "@/app/components/supplier-management/PurchaseProjectionTable";
import { SupplierSourceTabs } from "@/app/components/supplier-management/SupplierSourceTabs";
import {
  type SupplierDataView,
  type SupplierSource,
  type SupplierSourceView,
} from "@/types/supplier";

type SupplierSourceTablesProps = {
  dataView: SupplierDataView;
  sourceView: SupplierSourceView;
  sources: SupplierSource[];
  onDataViewChange: (view: SupplierDataView) => void;
  onSourceViewChange: (view: SupplierSourceView) => void;
};

export function SupplierSourceTables({
  dataView,
  sourceView,
  sources,
  onDataViewChange,
  onSourceViewChange,
}: SupplierSourceTablesProps) {
  return (
    <section className="ledgerSection">
      <div className="sectionTitle">
        <h2>仕入れ管理データ</h2>
        <span>保存済みデータと仕入れ表への反映内容</span>
      </div>
      <SupplierSourceTabs
        dataView={dataView}
        sourceView={sourceView}
        onDataViewChange={onDataViewChange}
        onSourceViewChange={onSourceViewChange}
      />
      <div className="ledgerTableFrame">
        {dataView === "仕入れ元データ" && sourceView === "要約" ? (
          <SupplierSourceSummaryTable sources={sources} />
        ) : null}
        {dataView === "仕入れ元データ" && sourceView === "発送・梱包" ? (
          <SupplierSourceShippingTable sources={sources} />
        ) : null}
        {dataView === "仕入れ元データ" && sourceView === "詳細・原票" ? (
          <SupplierSourceDetailTable sources={sources} />
        ) : null}
        {dataView === "仕入れ表への反映" ? (
          <PurchaseProjectionTable sources={sources} />
        ) : null}
      </div>
    </section>
  );
}

function SupplierSourceSummaryTable({ sources }: { sources: SupplierSource[] }) {
  return (
    <table className="ledgerGrid supplierSourceGrid">
      <colgroup>
        <col className="rowNoCol" />
        <col className="skuCol" />
        <col className="verifyCol" />
        <col className="dateCol" />
        <col className="sourceCol" />
        <col className="moneyCol" />
        <col className="nameCol" />
        <col className="dateCol" />
        <col className="moneyCol" />
        <col className="moneyCol" />
      </colgroup>
      <thead>
        <tr className="headerRow">
          <th>No</th>
          <th>SKU</th>
          <th>Order no.</th>
          <th>仕入日</th>
          <th>仕入れ先</th>
          <th>仕入れ</th>
          <th>商品名</th>
          <th>販売日</th>
          <th>販売額</th>
          <th>送料</th>
        </tr>
      </thead>
      <tbody>
        {sources.map((source) => (
          <tr key={source.sku}>
            <td>{source.rowNo}</td>
            <td className="selectedCell">{source.sku}</td>
            <td>{source.orderNo}</td>
            <td>{source.acquiredAt}</td>
            <td>{source.supplier}</td>
            <td className="numberCell">{source.purchasePrice}</td>
            <td className="nameCell">{source.itemName}</td>
            <td>{source.soldAt}</td>
            <td className="numberCell">{source.saleAmount}</td>
            <td className="numberCell">{source.shippingCost}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}

function SupplierSourceShippingTable({ sources }: { sources: SupplierSource[] }) {
  return (
    <table className="ledgerGrid supplierSourceGrid">
      <colgroup>
        <col className="skuCol" />
        <col className="sourceCol" />
        <col className="sourceCol" />
        <col className="weightCol" />
        <col className="weightCol" />
        <col className="sizeCol" />
        <col className="sizeCol" />
        <col className="sizeCol" />
        <col className="dateCol" />
        <col className="dateCol" />
      </colgroup>
      <thead>
        <tr className="headerRow">
          <th>SKU</th>
          <th>発送サイト</th>
          <th>梱包者</th>
          <th>実重g</th>
          <th>体積重g</th>
          <th>縦cm</th>
          <th>横cm</th>
          <th>高さcm</th>
          <th>初回メール</th>
          <th>領収書印刷日</th>
        </tr>
      </thead>
      <tbody>
        {sources.map((source) => (
          <tr key={source.sku}>
            <td className="selectedCell">{source.sku}</td>
            <td>{source.shippingSite}</td>
            <td>{source.packer}</td>
            <td className="numberCell">{source.actualWeight}</td>
            <td className="numberCell">{source.dimensionalWeight}</td>
            <td className="numberCell">{source.length}</td>
            <td className="numberCell">{source.width}</td>
            <td className="numberCell">{source.height}</td>
            <td>{source.firstMailAt}</td>
            <td>{source.receiptPrintedAt}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}

function SupplierSourceDetailTable({ sources }: { sources: SupplierSource[] }) {
  return (
    <table className="ledgerGrid supplierSourceGrid">
      <colgroup>
        <col className="rowNoCol" />
        <col className="skuCol" />
        <col className="sourceCol" />
        <col className="buyerCol" />
        <col className="typeCol" />
        <col className="moneyCol" />
        <col className="noteCol" />
        <col className="noteCol" />
      </colgroup>
      <thead>
        <tr className="headerRow">
          <th>No</th>
          <th>SKU</th>
          <th>アカウント</th>
          <th>国</th>
          <th>MAG</th>
          <th>ポイント</th>
          <th>備考</th>
          <th>商品名</th>
        </tr>
      </thead>
      <tbody>
        {sources.map((source) => (
          <tr key={source.sku}>
            <td>{source.rowNo}</td>
            <td className="selectedCell">{source.sku}</td>
            <td>{source.account}</td>
            <td>{source.country}</td>
            <td>{source.mag}</td>
            <td className="numberCell">{source.points}</td>
            <td>{source.note}</td>
            <td className="nameCell">{source.itemName}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}
