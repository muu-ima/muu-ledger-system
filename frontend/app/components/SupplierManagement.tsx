"use client";

import { type FormEvent, useEffect, useState } from "react";
import { SupplierManagementHeader } from "@/app/components/supplier-management/SupplierManagementHeader";
import { SupplierSourceForm } from "@/app/components/supplier-management/SupplierSourceForm";
import { SupplierSourceModal } from "@/app/components/supplier-management/SupplierSourceModal";
import { SupplierSourceTables } from "@/app/components/supplier-management/SupplierSourceTables";
import {
  supplierSourceFromApi,
  supplierSourceSample,
  supplierSourceToSubmitPayload,
  upsertSupplierSource,
  wordpressRestUrl,
} from "@/lib/supplierSources";
import {
  type SupplierDataView,
  type SupplierSource,
  type SupplierSourceApiRow,
  type SupplierSourceView,
} from "@/types/supplier";

export default function SupplierManagement() {
  const [supplierForm, setSupplierForm] = useState(supplierSourceSample);
  const [supplierSources, setSupplierSources] = useState<SupplierSource[]>([
    supplierSourceSample,
  ]);
  const [supplierSourceView, setSupplierSourceView] =
    useState<SupplierSourceView>("要約");
  const [supplierDataView, setSupplierDataView] =
    useState<SupplierDataView>("仕入れ元データ");
  const [supplierModalOpen, setSupplierModalOpen] = useState(false);
  const [supplierSubmitStatus, setSupplierSubmitStatus] = useState("");

  useEffect(() => {
    const baseUrl = process.env.NEXT_PUBLIC_WORDPRESS_URL || "";
    let cancelled = false;

    async function loadSupplierSources() {
      try {
        const response = await fetch(
          wordpressRestUrl(baseUrl, "/kobutsu/v1/supplier-sources"),
          { credentials: "include" },
        );
        if (!response.ok) return;

        const data = (await response.json()) as SupplierSourceApiRow[];
        if (!cancelled && data.length) {
          setSupplierSources(data.map(supplierSourceFromApi));
        }
      } catch {
        // Use the bundled sample row when WordPress is unavailable.
      }
    }

    loadSupplierSources();

    return () => {
      cancelled = true;
    };
  }, []);

  function updateSupplierForm(field: keyof SupplierSource, value: string) {
    setSupplierForm((current) => ({ ...current, [field]: value }));
    setSupplierSubmitStatus("");
  }

  function reflectSupplierSource() {
    setSupplierSources((current) => upsertSupplierSource(current, supplierForm));
    setSupplierSubmitStatus("仕入元データへ反映しました");
  }

  async function submitSupplierSource(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSupplierSubmitStatus("保存中");

    const baseUrl = process.env.NEXT_PUBLIC_WORDPRESS_URL || "";
    const payload = supplierSourceToSubmitPayload(supplierForm);

    try {
      const response = await fetch(
        wordpressRestUrl(baseUrl, "/kobutsu/v1/supplier-sources"),
        {
          method: "POST",
          credentials: "include",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(payload),
        },
      );

      if (!response.ok) {
        const data = (await response.json().catch(() => null)) as
          | { message?: string }
          | null;
        setSupplierSubmitStatus(data?.message || "保存できませんでした");
        return;
      }

      const data = (await response.json()) as SupplierSourceApiRow;
      const savedSource = supplierSourceFromApi(data);
      setSupplierSources((current) => upsertSupplierSource(current, savedSource));
      setSupplierSubmitStatus("保存しました");
      setSupplierModalOpen(false);
    } catch {
      setSupplierSubmitStatus("WordPressに接続できませんでした");
    }
  }

  return (
    <>
      <SupplierManagementHeader
        resultCount={supplierSources.length}
        onCreate={() => setSupplierModalOpen(true)}
      />

      <div className="ledgerSections">
        <SupplierSourceModal
          isOpen={supplierModalOpen}
          onClose={() => setSupplierModalOpen(false)}
          onSubmit={submitSupplierSource}
        >
          <SupplierSourceForm
            form={supplierForm}
            submitStatus={supplierSubmitStatus}
            onFieldChange={updateSupplierForm}
            onReflect={reflectSupplierSource}
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
