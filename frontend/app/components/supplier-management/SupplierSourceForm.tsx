"use client";

import { SupplierSourceFormSections } from "@/app/components/supplier-management/SupplierSourceFormSections";
import type { SupplierSource } from "@/types/supplier";

type SupplierSourceFormProps = {
  form: SupplierSource;
  submitStatus: string;
  onFieldChange: (field: keyof SupplierSource, value: string) => void;
  onReflect: () => void;
};

export function SupplierSourceForm({
  form,
  submitStatus,
  onFieldChange,
  onReflect,
}: SupplierSourceFormProps) {
  return (
    <>
      <SupplierSourceFormSections
        form={form}
        onFieldChange={onFieldChange}
      />

      <div className="formActions">
        <button type="button" onClick={onReflect}>
          仕入元データへ反映
        </button>
        <button type="submit">保存</button>
        <span>{submitStatus}</span>
      </div>
    </>
  );
}
