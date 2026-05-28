import type { LedgerItem } from "@/types/ledger";

const fallbackItems: LedgerItem[] = [
  {
    id: 1,
    managementNo: "KB-2026-0001",
    category: "時計",
    itemName: "腕時計",
    description: "型番、シリアル、状態をここに記録",
    acquiredAt: "2026-05-28",
    acquiredFrom: "山田 太郎",
    sellerIdentification: "運転免許証",
    purchasePrice: 12000,
    soldAt: "",
    soldTo: "",
    salePrice: 0,
    status: "in_stock",
  },
];

function normalizeItem(item: Record<string, unknown>): LedgerItem {
  return {
    id: Number(item.id ?? 0),
    managementNo: String(item.management_no ?? item.managementNo ?? ""),
    category: String(item.category ?? ""),
    itemName: String(item.item_name ?? item.itemName ?? ""),
    description: String(item.description ?? ""),
    acquiredAt: String(item.acquired_at ?? item.acquiredAt ?? ""),
    acquiredFrom: String(item.acquired_from ?? item.acquiredFrom ?? ""),
    sellerIdentification: String(
      item.seller_identification ?? item.sellerIdentification ?? "",
    ),
    purchasePrice: Number(item.purchase_price ?? item.purchasePrice ?? 0),
    soldAt: String(item.sold_at ?? item.soldAt ?? ""),
    soldTo: String(item.sold_to ?? item.soldTo ?? ""),
    salePrice: Number(item.sale_price ?? item.salePrice ?? 0),
    status: String(item.status ?? "in_stock") as LedgerItem["status"],
  };
}

export async function getLedgerItems(): Promise<LedgerItem[]> {
  const baseUrl =
    process.env.WORDPRESS_INTERNAL_URL ||
    process.env.NEXT_PUBLIC_WORDPRESS_URL ||
    "http://localhost:8080";

  try {
    const response = await fetch(`${baseUrl}/wp-json/kobutsu/v1/items`, {
      next: { revalidate: 10 },
    });

    if (!response.ok) {
      return fallbackItems;
    }

    const data = (await response.json()) as Record<string, unknown>[];
    return data.map(normalizeItem);
  } catch {
    return fallbackItems;
  }
}
