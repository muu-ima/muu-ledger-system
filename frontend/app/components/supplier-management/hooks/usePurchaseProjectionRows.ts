"use client";

import { useEffect, useState } from "react";
import {
  createWordpressJsonHeaders,
  fetchWithWordpressNonceRetry,
  mergePurchaseProjectionRows,
  purchaseProjectionFromApi,
  purchaseProjectionToUpdatePayload,
  resolveWordpressBaseUrl,
  upsertPurchaseProjectionRow,
  wordpressRestUrl,
} from "@/lib/supplierSources";
import type {
  PurchaseProjectionApiRow,
  PurchaseProjectionRow,
  SupplierSource,
} from "@/types/supplier";

export function usePurchaseProjectionRows(sources: SupplierSource[]) {
  const [rows, setRows] = useState<PurchaseProjectionRow[]>(
    mergePurchaseProjectionRows(sources, []),
  );
  const [updateStatus, setUpdateStatus] = useState("");

  useEffect(() => {
    const baseUrl = resolveWordpressBaseUrl(
      process.env.NEXT_PUBLIC_WORDPRESS_URL || "",
    );
    let cancelled = false;

    async function loadItems() {
      try {
        const response = await fetch(wordpressRestUrl(baseUrl, "/kobutsu/v1/items"), {
          credentials: "include",
        });
        if (!response.ok) return;

        const data = (await response.json()) as PurchaseProjectionApiRow[];
        if (!cancelled) {
          setRows(
            mergePurchaseProjectionRows(
              sources,
              data.map(purchaseProjectionFromApi),
            ),
          );
        }
      } catch {
        if (!cancelled) {
          setRows(mergePurchaseProjectionRows(sources, []));
        }
      }
    }

    loadItems();

    return () => {
      cancelled = true;
    };
  }, [sources]);

  function updateRowField(
    sku: string,
    field: keyof PurchaseProjectionRow,
    value: string,
  ) {
    setRows((currentRows) =>
      currentRows.map((row) => (row.sku === sku ? { ...row, [field]: value } : row)),
    );
  }

  async function saveRow(row: PurchaseProjectionRow) {
    if (!/^\d+$/.test(row.itemId)) {
      setUpdateStatus(`${row.sku} はまだ同期前のため保存できません`);
      return;
    }

    setUpdateStatus(`${row.sku} を保存中`);
    const baseUrl = resolveWordpressBaseUrl(
      process.env.NEXT_PUBLIC_WORDPRESS_URL || "",
    );

    try {
      const response = await fetchWithWordpressNonceRetry(
        wordpressRestUrl(baseUrl, `/kobutsu/v1/items/${row.itemId}`),
        {
          method: "POST",
          credentials: "include",
          headers: createWordpressJsonHeaders(),
          body: JSON.stringify(purchaseProjectionToUpdatePayload(row)),
        },
      );

      if (!response.ok) {
        const data = (await response.json().catch(() => null)) as
          | { message?: string }
          | null;
        setUpdateStatus(data?.message || "保存できませんでした");
        return;
      }

      const savedRow = purchaseProjectionFromApi(
        (await response.json()) as PurchaseProjectionApiRow,
      );
      setRows((currentRows) => upsertPurchaseProjectionRow(currentRows, savedRow));
      setUpdateStatus(`${savedRow.sku} を保存しました`);
    } catch {
      setUpdateStatus("WordPressに接続できませんでした");
    }
  }

  return {
    purchaseProjectionRows: rows,
    purchaseProjectionStatus: updateStatus,
    savePurchaseProjectionRow: saveRow,
    updatePurchaseProjectionRow: updateRowField,
  };
}
