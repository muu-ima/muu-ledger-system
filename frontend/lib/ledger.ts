import type { LedgerItem } from "@/types/ledger";

type ApiValue = string | number | null | undefined;

type LedgerItemApiRow = {
  id?: ApiValue;
  management_no?: ApiValue;
  managementNo?: ApiValue;
  source_order_no?: ApiValue;
  order_no?: ApiValue;
  category?: ApiValue;
  item_name?: ApiValue;
  itemName?: ApiValue;
  quantity?: ApiValue;
  description?: ApiValue;
  acquired_at?: ApiValue;
  acquiredAt?: ApiValue;
  purchase_type?: ApiValue;
  acquired_from?: ApiValue;
  acquiredFrom?: ApiValue;
  seller_identification?: ApiValue;
  sellerIdentification?: ApiValue;
  seller_address?: ApiValue;
  seller_name?: ApiValue;
  seller_age?: ApiValue;
  seller_occupation?: ApiValue;
  purchase_price?: ApiValue;
  purchasePrice?: ApiValue;
  sold_at?: ApiValue;
  soldAt?: ApiValue;
  sold_to?: ApiValue;
  soldTo?: ApiValue;
  sale_type?: ApiValue;
  sale_price?: ApiValue;
  salePrice?: ApiValue;
  sale_amount?: ApiValue;
  sale_currency?: ApiValue;
  buyer_country?: ApiValue;
  buyer_id?: ApiValue;
  buyer_name?: ApiValue;
  buyer_city?: ApiValue;
  buyer_state?: ApiValue;
  buyer_postal_code?: ApiValue;
  buyer_address1?: ApiValue;
  buyer_address2?: ApiValue;
  buyer_address3?: ApiValue;
  tracking_no?: ApiValue;
  shipping_site?: ApiValue;
  shopee_order_status?: ApiValue;
  ledger_link_source?: ApiValue;
  status?: ApiValue;
};

const fallbackItems: LedgerItem[] = [
  {
    id: 1,
    managementNo: "KB-2026-0001",
    sourceOrderNo: "",
    saleOrderNo: "",
    category: "時計",
    itemName: "腕時計",
    description: "型番、シリアル、状態をここに記録",
    quantity: 1,
    acquiredAt: "2026-05-28",
    purchaseType: "買受",
    acquiredFrom: "山田 太郎",
    sellerIdentification: "運転免許証",
    sellerAddress: "",
    sellerName: "",
    sellerAge: "",
    sellerOccupation: "",
    purchasePrice: 12000,
    soldAt: "",
    soldTo: "",
    saleType: "",
    salePrice: 0,
    saleAmount: 0,
    saleCurrency: "",
    buyerCountry: "",
    buyerId: "",
    buyerName: "",
    buyerCity: "",
    buyerState: "",
    buyerPostalCode: "",
    buyerAddress1: "",
    buyerAddress2: "",
    buyerAddress3: "",
    trackingNo: "",
    shippingSite: "",
    shopeeOrderStatus: "",
    ledgerLinkSource: "",
    status: "in_stock",
  },
];

function normalizeItem(item: LedgerItemApiRow): LedgerItem {
  return {
    id: Number(item.id ?? 0),
    managementNo: String(item.management_no ?? item.managementNo ?? ""),
    sourceOrderNo: String(item.source_order_no ?? ""),
    saleOrderNo: String(item.order_no ?? ""),
    category: String(item.category ?? ""),
    itemName: String(item.item_name ?? item.itemName ?? ""),
    description: String(item.description ?? ""),
    quantity: Number(item.quantity ?? 1),
    acquiredAt: String(item.acquired_at ?? item.acquiredAt ?? ""),
    purchaseType: String(item.purchase_type ?? ""),
    acquiredFrom: String(item.acquired_from ?? item.acquiredFrom ?? ""),
    sellerIdentification: String(
      item.seller_identification ?? item.sellerIdentification ?? "",
    ),
    sellerAddress: String(item.seller_address ?? ""),
    sellerName: String(item.seller_name ?? ""),
    sellerAge: String(item.seller_age ?? ""),
    sellerOccupation: String(item.seller_occupation ?? ""),
    purchasePrice: Number(item.purchase_price ?? item.purchasePrice ?? 0),
    soldAt: String(item.sold_at ?? item.soldAt ?? ""),
    soldTo: String(item.sold_to ?? item.soldTo ?? ""),
    saleType: String(item.sale_type ?? ""),
    salePrice: Number(item.sale_price ?? item.salePrice ?? 0),
    saleAmount: Number(item.sale_amount ?? 0),
    saleCurrency: String(item.sale_currency ?? ""),
    buyerCountry: String(item.buyer_country ?? ""),
    buyerId: String(item.buyer_id ?? ""),
    buyerName: String(item.buyer_name ?? ""),
    buyerCity: String(item.buyer_city ?? ""),
    buyerState: String(item.buyer_state ?? ""),
    buyerPostalCode: String(item.buyer_postal_code ?? ""),
    buyerAddress1: String(item.buyer_address1 ?? ""),
    buyerAddress2: String(item.buyer_address2 ?? ""),
    buyerAddress3: String(item.buyer_address3 ?? ""),
    trackingNo: String(item.tracking_no ?? ""),
    shippingSite: String(item.shipping_site ?? ""),
    shopeeOrderStatus: String(item.shopee_order_status ?? ""),
    ledgerLinkSource: String(item.ledger_link_source ?? ""),
    status: String(item.status ?? "in_stock") as LedgerItem["status"],
  };
}

function wordpressRestUrl(baseUrl: string, route: string) {
  return `${baseUrl.replace(/\/$/, "")}/index.php?rest_route=${route}`;
}

export async function getLedgerItems(): Promise<LedgerItem[]> {
  const baseUrl =
    process.env.WORDPRESS_INTERNAL_URL ||
    process.env.NEXT_PUBLIC_WORDPRESS_URL ||
    "http://localhost:8080";

  try {
    const response = await fetch(
      wordpressRestUrl(baseUrl, "/kobutsu/v1/items"),
      {
        next: { revalidate: 10 },
      },
    );

    if (!response.ok) {
      return fallbackItems;
    }

    const data = (await response.json()) as LedgerItemApiRow[];
    return data.map(normalizeItem);
  } catch {
    return fallbackItems;
  }
}
