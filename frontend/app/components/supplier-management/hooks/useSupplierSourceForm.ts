"use client";

import { useState } from "react";
import {
  createSupplierSourceDraft,
  supplierSourceSample,
} from "@/lib/supplierSources";
import type { SupplierSource } from "@/types/supplier";

export function useSupplierSourceForm(
  initialSource: SupplierSource = supplierSourceSample,
) {
  const [supplierForm, setSupplierForm] = useState<SupplierSource>(() =>
    createSupplierSourceDraft(initialSource),
  );

  function updateSupplierForm(field: keyof SupplierSource, value: string) {
    setSupplierForm((current) => ({ ...current, [field]: value }));
  }

  function resetSupplierForm() {
    setSupplierForm(createSupplierSourceDraft(initialSource));
  }

  return {
    supplierForm,
    resetSupplierForm,
    updateSupplierForm,
  };
}
