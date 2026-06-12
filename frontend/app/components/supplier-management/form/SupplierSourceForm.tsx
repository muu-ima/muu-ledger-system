"use client";

import { SupplierSourceFormSections } from "@/app/components/supplier-management/form/SupplierSourceFormSections";
import type { SupplierSource } from "@/types/supplier";

type SupplierSourceFormProps = {
  form: SupplierSource;
  submitStatus: string;
  onFieldChange: (field: keyof SupplierSource, value: string) => void;
};

export function SupplierSourceForm({
  form,
  submitStatus,
  onFieldChange,
}: SupplierSourceFormProps) {
  return (
    <>
      <SupplierSourceFormSections
        form={form}
        onFieldChange={onFieldChange}
      />

      <div className="formActions">
        <button className="primaryActionButton" type="submit">
          保存
        </button>
        <span>{submitStatus}</span>
      </div>
    </>
  );
}
