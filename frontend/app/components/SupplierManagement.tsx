"use client";

import { type FormEvent, useState } from "react";
import { SupplierManagementHeader } from "@/app/components/supplier-management/SupplierManagementHeader";
import { SupplierSourceForm } from "@/app/components/supplier-management/SupplierSourceForm";
import { useSupplierSourceForm } from "@/app/components/supplier-management/hooks/useSupplierSourceForm";
import { useSupplierSources } from "@/app/components/supplier-management/hooks/useSupplierSources";
import { SupplierSourceModal } from "@/app/components/supplier-management/SupplierSourceModal";
import { SupplierSourceTables } from "@/app/components/supplier-management/SupplierSourceTables";
import {
  type SupplierDataView,
  type SupplierSourceView,
} from "@/types/supplier";

export default function SupplierManagement() {
  const [supplierSourceView, setSupplierSourceView] =
    useState<SupplierSourceView>("要約");
  const [supplierDataView, setSupplierDataView] =
    useState<SupplierDataView>("仕入れ元データ");
  const [supplierModalOpen, setSupplierModalOpen] = useState(false);
  const { supplierForm, resetSupplierForm, updateSupplierForm } =
    useSupplierSourceForm();
  const {
    clearSupplierSubmitStatus,
    reflectSupplierSource,
    saveSupplierSource,
    supplierSources,
    supplierSubmitStatus,
  } = useSupplierSources();

  function openSupplierModal() {
    resetSupplierForm();
    clearSupplierSubmitStatus();
    setSupplierModalOpen(true);
  }

  function closeSupplierModal() {
    clearSupplierSubmitStatus();
    setSupplierModalOpen(false);
  }

  async function submitSupplierSource(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const result = await saveSupplierSource(supplierForm);
    if (result.ok) {
      closeSupplierModal();
      resetSupplierForm();
    }
  }

  return (
    <>
      <SupplierManagementHeader
        resultCount={supplierSources.length}
        onCreate={openSupplierModal}
      />

      <div className="ledgerSections">
        <SupplierSourceModal
          isOpen={supplierModalOpen}
          onClose={closeSupplierModal}
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
