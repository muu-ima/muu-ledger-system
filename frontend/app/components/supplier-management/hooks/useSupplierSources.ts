"use client";

import { useEffect, useState } from "react";
import {
  createSupplierSourceDraft,
  supplierSourceFromApi,
  supplierSourceToSubmitPayload,
  supplierSourceToUpdatePayload,
  upsertSupplierSource,
  wordpressRestUrl,
} from "@/lib/supplierSources";
import type { SupplierSource, SupplierSourceApiRow } from "@/types/supplier";

type SaveSupplierSourceResult =
  | { ok: true; source: SupplierSource }
  | { ok: false };

export function useSupplierSources() {
  const [supplierSources, setSupplierSources] = useState<SupplierSource[]>([
    createSupplierSourceDraft(),
  ]);
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

  function clearSupplierSubmitStatus() {
    setSupplierSubmitStatus("");
  }

  function reflectSupplierSource(source: SupplierSource) {
    setSupplierSources((current) => upsertSupplierSource(current, source));
    setSupplierSubmitStatus("仕入元データへ反映しました");
  }

  function updateSupplierSourceField(
    sku: string,
    field: keyof SupplierSource,
    value: string,
  ) {
    setSupplierSubmitStatus("");
    setSupplierSources((current) =>
      current.map((source) =>
        source.sku === sku ? { ...source, [field]: value } : source,
      ),
    );
  }

  async function saveSupplierSource(
    source: SupplierSource,
  ): Promise<SaveSupplierSourceResult> {
    setSupplierSubmitStatus("保存中");

    const baseUrl = process.env.NEXT_PUBLIC_WORDPRESS_URL || "";
    const payload = supplierSourceToSubmitPayload(source);

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
        return { ok: false };
      }

      const data = (await response.json()) as SupplierSourceApiRow;
      const savedSource = supplierSourceFromApi(data);
      setSupplierSources((current) => upsertSupplierSource(current, savedSource));
      setSupplierSubmitStatus("保存しました");
      return { ok: true, source: savedSource };
    } catch {
      setSupplierSubmitStatus("WordPressに接続できませんでした");
      return { ok: false };
    }
  }

  async function updateSupplierSource(
    source: SupplierSource,
  ): Promise<SaveSupplierSourceResult> {
    if (!source.id) {
      setSupplierSubmitStatus("保存済みデータのみ更新できます");
      return { ok: false };
    }

    setSupplierSubmitStatus(`${source.sku} を保存中`);

    const baseUrl = process.env.NEXT_PUBLIC_WORDPRESS_URL || "";
    const payload = supplierSourceToUpdatePayload(source);

    try {
      const response = await fetch(
        wordpressRestUrl(baseUrl, `/kobutsu/v1/supplier-sources/${source.id}`),
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
        return { ok: false };
      }

      const data = (await response.json()) as SupplierSourceApiRow;
      const savedSource = supplierSourceFromApi(data);
      setSupplierSources((current) => upsertSupplierSource(current, savedSource));
      setSupplierSubmitStatus(`${savedSource.sku} を保存しました`);
      return { ok: true, source: savedSource };
    } catch {
      setSupplierSubmitStatus("WordPressに接続できませんでした");
      return { ok: false };
    }
  }

  return {
    clearSupplierSubmitStatus,
    reflectSupplierSource,
    saveSupplierSource,
    supplierSources,
    supplierSubmitStatus,
    updateSupplierSource,
    updateSupplierSourceField,
  };
}
