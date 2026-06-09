"use client";

import { useState } from "react";
import {
  type SupplierDataView,
  type SupplierSourceView,
} from "@/types/supplier";

export function useSupplierManagementUI() {
  const [supplierSourceView, setSupplierSourceView] =
    useState<SupplierSourceView>("要約");
  const [supplierDataView, setSupplierDataView] =
    useState<SupplierDataView>("仕入れ元データ");
  const [supplierModalOpen, setSupplierModalOpen] = useState(false);

  function openSupplierModal() {
    setSupplierModalOpen(true);
  }

  function closeSupplierModal() {
    setSupplierModalOpen(false);
  }

  return {
    closeSupplierModal,
    openSupplierModal,
    setSupplierDataView,
    setSupplierSourceView,
    supplierDataView,
    supplierModalOpen,
    supplierSourceView,
  };
}
