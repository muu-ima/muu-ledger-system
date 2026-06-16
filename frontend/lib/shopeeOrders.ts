import { wordpressRestUrl } from "@/lib/supplierSources";
import { resolveWordpressBaseUrl } from "@/lib/wordpressShellAuth";
import type {
  ShopeeOrder,
  ShopeeOrderApiRow,
  ShopeeOrderImportBatch,
  ShopeeOrderImportBatchApiRow,
  ShopeeOrdersApiResponse,
} from "@/types/shopeeOrders";

type SkipReasons = {
  db_error?: number;
  duplicate?: number;
  empty?: number;
  header?: number;
  missing_order_id?: number;
  note?: number;
};

const skipReasonLabels: Record<keyof SkipReasons, string> = {
  header: "ヘッダー行",
  empty: "空行",
  note: "メモ行",
  missing_order_id: "注文IDなし",
  duplicate: "重複",
  db_error: "DB保存失敗",
};

function text(value: string | number | null | undefined) {
  return String(value ?? "");
}

function numberValue(value: string | number | null | undefined) {
  const parsed = Number(value ?? 0);
  return Number.isFinite(parsed) ? parsed : 0;
}

function formatShopeeOrderMoney(
  amount: string | number | null | undefined,
  currency: string | number | null | undefined,
) {
  const numericAmount = numberValue(amount);
  const currencyLabel = text(currency);
  const formattedAmount = numericAmount.toLocaleString("ja-JP", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

  return currencyLabel ? `${currencyLabel} ${formattedAmount}` : formattedAmount;
}

function formatSkipDetails(notes: string | number | null | undefined) {
  if (!notes) return "";

  try {
    const parsed = JSON.parse(String(notes)) as {
      skip_reasons?: SkipReasons;
    };
    const skipReasons = parsed.skip_reasons ?? {};
    const parts = Object.entries(skipReasonLabels)
      .map(([key, label]) => {
        const count = Number(skipReasons[key as keyof SkipReasons] ?? 0);
        return count > 0 ? `${label} ${count} 件` : "";
      })
      .filter(Boolean);

    return parts.join("、");
  } catch {
    return "";
  }
}

export function normalizeShopeeOrder(row: ShopeeOrderApiRow): ShopeeOrder {
  const currency = text(row.currency);
  const grandTotal = row.grand_total || row.total_amount || row.gross_amount;

  return {
    id: text(row.id),
    orderNo: text(row.order_no),
    orderStatus: text(row.order_status),
    orderCreatedAt: text(row.order_created_at),
    orderPaidAt: text(row.order_paid_at),
    orderCompletedAt: text(row.order_completed_at),
    shipTime: text(row.ship_time),
    estimatedShipOutAt: text(row.estimated_ship_out_at),
    buyerUsername: text(row.buyer_username),
    country: text(row.country),
    parentSku: text(row.parent_sku),
    sku: text(row.sku),
    productName: text(row.product_name),
    variationName: text(row.variation_name),
    quantity: numberValue(row.quantity),
    returnedQuantity: numberValue(row.returned_quantity),
    grossAmount: formatShopeeOrderMoney(row.gross_amount, currency),
    totalAmount: formatShopeeOrderMoney(row.total_amount, currency),
    grandTotal: formatShopeeOrderMoney(row.grand_total, currency),
    displayAmount: formatShopeeOrderMoney(grandTotal, currency),
    currency,
    trackingNumber: text(row.tracking_number),
    shippingOption: text(row.shipping_option),
    shipmentMethod: text(row.shipment_method),
    cancelReason: text(row.cancel_reason),
    returnRefundStatus: text(row.return_refund_status),
    sourceLineNumber: text(row.source_line_number),
    createdAt: text(row.created_at),
  };
}

export function normalizeShopeeOrderImportBatch(
  row: ShopeeOrderImportBatchApiRow,
): ShopeeOrderImportBatch {
  return {
    id: text(row.id),
    filename: text(row.original_filename),
    status: text(row.status),
    importedRows: numberValue(row.imported_rows),
    skippedRows: numberValue(row.error_rows),
    skipDetails: formatSkipDetails(row.notes),
    createdAt: text(row.created_at),
    completedAt: text(row.completed_at),
  };
}

export async function fetchShopeeOrders() {
  const baseUrl = resolveWordpressBaseUrl(
    process.env.NEXT_PUBLIC_WORDPRESS_URL || "",
  );
  const response = await fetch(
    wordpressRestUrl(baseUrl, "/kobutsu/v1/shopee-orders"),
    {
      credentials: "include",
    },
  );

  if (!response.ok) {
    throw new Error("Shopeeオーダーを取得できませんでした");
  }

  const data = (await response.json()) as ShopeeOrdersApiResponse;

  return {
    orders: (data.orders ?? []).map(normalizeShopeeOrder),
    batches: (data.batches ?? []).map(normalizeShopeeOrderImportBatch),
  };
}
