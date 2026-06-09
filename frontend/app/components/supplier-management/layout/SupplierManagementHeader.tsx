type SupplierManagementHeaderProps = {
  resultCount: number;
  onCreate: () => void;
};

export function SupplierManagementHeader({
  resultCount,
  onCreate,
}: SupplierManagementHeaderProps) {
  return (
    <section className="ledgerTop">
      <div>
        <h1>仕入れ管理</h1>
        <p>仕入れ元データと仕入れ表を統合した管理ビュー</p>
      </div>
      <div className="ledgerTopActions">
        <button type="button" onClick={onCreate}>
          新規仕入れ
        </button>
        <div className="resultCount">該当 {resultCount} 件</div>
      </div>
    </section>
  );
}
