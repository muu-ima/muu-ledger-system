"use client";

import { useDeferredValue, useMemo, useState, type FormEvent } from "react";
import { SupplierSourceForm } from "@/app/components/supplier-management/form/SupplierSourceForm";
import { usePurchaseProjectionRows } from "@/app/components/supplier-management/hooks/usePurchaseProjectionRows";
import { useSupplierSourceForm } from "@/app/components/supplier-management/hooks/useSupplierSourceForm";
import { useSupplierManagementUI } from "@/app/components/supplier-management/hooks/useSupplierManagementUI";
import { useSupplierSources } from "@/app/components/supplier-management/hooks/useSupplierSources";
import { SupplierSourceModal } from "@/app/components/supplier-management/layout/SupplierSourceModal";
import { SupplierSourceTables } from "@/app/components/supplier-management/tables/SupplierSourceTables";
import { SupplierSourceTabs } from "@/app/components/supplier-management/tables/SupplierSourceTabs";
import {
  supplierDataViews,
  type PurchaseProjectionRow,
  type SupplierSource,
} from "@/types/supplier";

type SupplierManagementStatusView =
  | "all"
  | "purchased"
  | "unpurchased"
  | "sold"
  | "shipped";

const supplierManagementStatusViews: {
  label: string;
  value: SupplierManagementStatusView;
}[] = [
  { label: "すべて", value: "all" },
  { label: "購入済み", value: "purchased" },
  { label: "未購入", value: "unpurchased" },
  { label: "売却済み", value: "sold" },
  { label: "配送あり", value: "shipped" },
];

function matchesSupplierStatus(
  source: SupplierSource,
  statusView: SupplierManagementStatusView,
) {
  if (statusView === "purchased") return source.purchasedFlag === "TRUE";
  if (statusView === "unpurchased") return source.purchasedFlag !== "TRUE";
  if (statusView === "sold") return Boolean(source.soldAt);
  if (statusView === "shipped") {
    return Boolean(source.domesticTrackingNo || source.slsTrackingNo);
  }

  return true;
}

function matchesSupplierSearch(
  source: SupplierSource,
  searchQuery: string,
) {
  const normalizedSearch = searchQuery.trim().toLowerCase();

  if (
    normalizedSearch &&
    ![source.sku, source.orderNo, source.itemName, source.supplier, source.account]
      .join(" ")
      .toLowerCase()
      .includes(normalizedSearch)
  ) {
    return false;
  }

  return true;
}

function filterPurchaseProjectionRows(
  rows: PurchaseProjectionRow[],
  allowedSkus: Set<string>,
) {
  return rows.filter((row) => allowedSkus.has(row.sku));
}

export default function SupplierManagement() {
  const { supplierForm, resetSupplierForm, updateSupplierForm } =
    useSupplierSourceForm();
  const {
    clearSupplierSubmitStatus,
    reflectSupplierSource,
    saveSupplierSource,
    supplierSources,
    supplierSubmitStatus,
    updateSupplierSource,
    updateSupplierSourceField,
  } = useSupplierSources();
  const {
    closeSupplierModal,
    openSupplierModal,
    setSupplierDataView,
    setSupplierSourceView,
    supplierDataView,
    supplierModalOpen,
    supplierSourceView,
  } = useSupplierManagementUI();
  const {
    purchaseProjectionRows,
    purchaseProjectionStatus,
    savePurchaseProjectionRow,
    updatePurchaseProjectionRow,
  } = usePurchaseProjectionRows(supplierSources);
  const [statusView, setStatusView] =
    useState<SupplierManagementStatusView>("all");
  const [searchQuery, setSearchQuery] = useState("");
  const deferredSearchQuery = useDeferredValue(searchQuery);

  const filteredSupplierSources = useMemo(
    () =>
      supplierSources.filter(
        (source) =>
          matchesSupplierStatus(source, statusView) &&
          matchesSupplierSearch(source, deferredSearchQuery),
      ),
    [deferredSearchQuery, statusView, supplierSources],
  );
  const allowedSkus = useMemo(
    () => new Set(filteredSupplierSources.map((source) => source.sku)),
    [filteredSupplierSources],
  );
  const filteredPurchaseProjectionRows = useMemo(
    () => filterPurchaseProjectionRows(purchaseProjectionRows, allowedSkus),
    [allowedSkus, purchaseProjectionRows],
  );
  const resultCount =
    supplierDataView === "仕入れ表への反映"
      ? filteredPurchaseProjectionRows.length
      : filteredSupplierSources.length;
  const totalCount =
    supplierDataView === "仕入れ表への反映"
      ? purchaseProjectionRows.length
      : supplierSources.length;

  function openSupplierSourceModal() {
    resetSupplierForm();
    clearSupplierSubmitStatus();
    openSupplierModal();
  }

  function closeSupplierSourceModal() {
    clearSupplierSubmitStatus();
    closeSupplierModal();
  }

  async function submitSupplierSource(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const result = await saveSupplierSource(supplierForm);
    if (result.ok) {
      closeSupplierSourceModal();
      resetSupplierForm();
    }
  }

  return (
    <>
      <div className="ledgerSections">
        <SupplierSourceModal
          isOpen={supplierModalOpen}
          onClose={closeSupplierSourceModal}
          onSubmit={submitSupplierSource}
        >
          <SupplierSourceForm
            form={supplierForm}
            submitStatus={supplierSubmitStatus}
            onFieldChange={(field, value) => {
              updateSupplierForm(field, value);
              clearSupplierSubmitStatus();
            }}
            onReflect={() => reflectSupplierSource(supplierForm)}
          />
        </SupplierSourceModal>

        <section className="ledgerSection">
          <div className="sectionTitle supplierSectionTitle">
            <div>
              <h2>仕入れ管理</h2>
              <span>フォーム入力とテーブル更新を切り替えながら管理するビュー</span>
            </div>
            <div className="ledgerTopActions compact">
              <button type="button" onClick={openSupplierSourceModal}>
                新規仕入れ
              </button>
              <div className="resultCount">
                該当 {resultCount} / {totalCount} 件
              </div>
            </div>
          </div>

          <div className="tableTabs primaryTabs" role="tablist" aria-label="仕入れ管理データ">
            {supplierDataViews.map((view) => (
              <button
                key={view}
                type="button"
                role="tab"
                aria-selected={supplierDataView === view}
                className={supplierDataView === view ? "active" : ""}
                onClick={() => setSupplierDataView(view)}
              >
                {view}
              </button>
            ))}
          </div>

          <div className="ecSalesListCard supplierListCard">
            <div className="ecSalesListToolbar">
              <div className="ecSalesStatusTabs" aria-label="仕入れ管理ステータス">
                {supplierManagementStatusViews.map((view) => (
                  <button
                    className={statusView === view.value ? "active" : ""}
                    key={view.value}
                    onClick={() => setStatusView(view.value)}
                    type="button"
                  >
                    {view.label}
                  </button>
                ))}
              </div>
              <label className="ecSalesSearch">
                <span>検索</span>
                <input
                  placeholder="SKU / Order no. / 商品名"
                  type="search"
                  value={searchQuery}
                  onChange={(event) => setSearchQuery(event.target.value)}
                />
              </label>
            </div>
            {supplierDataView === "仕入れ元データ" ? (
              <SupplierSourceTabs
                sourceView={supplierSourceView}
                onSourceViewChange={setSupplierSourceView}
              />
            ) : null}
            <SupplierSourceTables
              dataView={supplierDataView}
              purchaseProjectionRows={filteredPurchaseProjectionRows}
              purchaseProjectionStatus={purchaseProjectionStatus}
              onPurchaseProjectionRowChange={updatePurchaseProjectionRow}
              onPurchaseProjectionRowSave={savePurchaseProjectionRow}
              sourceView={supplierSourceView}
              sources={filteredSupplierSources}
              onSourceRowChange={updateSupplierSourceField}
              onSourceRowSave={updateSupplierSource}
              sourceStatusMessage={supplierSubmitStatus}
            />
          </div>
        </section>
      </div>
    </>
  );
}
