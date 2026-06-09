"use client";

import { type FormEvent } from "react";
import { SupplierManagementHeader } from "@/app/components/supplier-management/SupplierManagementHeader";
import { SupplierSourceForm } from "@/app/components/supplier-management/SupplierSourceForm";
import { useSupplierSourceForm } from "@/app/components/supplier-management/hooks/useSupplierSourceForm";
import { useSupplierManagementUI } from "@/app/components/supplier-management/hooks/useSupplierManagementUI";
import { useSupplierSources } from "@/app/components/supplier-management/hooks/useSupplierSources";
import { SupplierSourceModal } from "@/app/components/supplier-management/SupplierSourceModal";
import { SupplierSourceTables } from "@/app/components/supplier-management/SupplierSourceTables";

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
