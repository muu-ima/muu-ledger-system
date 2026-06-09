"use client";

import { type FormEvent } from "react";
import { SupplierSourceForm } from "@/app/components/supplier-management/form/SupplierSourceForm";
import { useSupplierSourceForm } from "@/app/components/supplier-management/hooks/useSupplierSourceForm";
import { useSupplierManagementUI } from "@/app/components/supplier-management/hooks/useSupplierManagementUI";
import { useSupplierSources } from "@/app/components/supplier-management/hooks/useSupplierSources";
import { SupplierManagementHeader } from "@/app/components/supplier-management/layout/SupplierManagementHeader";
import { SupplierSourceModal } from "@/app/components/supplier-management/layout/SupplierSourceModal";
import { SupplierSourceTables } from "@/app/components/supplier-management/tables/SupplierSourceTables";

export default function SupplierManagement() {
  const { supplierForm, resetSupplierForm, updateSupplierForm } =
    useSupplierSourceForm();
  const {
    clearSupplierSubmitStatus,
    reflectSupplierSource,
    saveSupplierSource,
    supplierSources,
    supplierSubmitStatus,
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
      <SupplierManagementHeader
        resultCount={supplierSources.length}
        onCreate={openSupplierSourceModal}
      />

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

        <SupplierSourceTables
          dataView={supplierDataView}
          sourceView={supplierSourceView}
          sources={supplierSources}
          onDataViewChange={setSupplierDataView}
          onSourceViewChange={setSupplierSourceView}
        />
      </div>
    </>
  );
}
