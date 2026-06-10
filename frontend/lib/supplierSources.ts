import type {
  PurchaseProjectionApiRow,
  PurchaseProjectionRow,
  SupplierSource,
  SupplierSourceApiRow,
  SupplierSourceSubmitPayload,
} from "@/types/supplier";

export const supplierSourceSample = {
  id: "",
  rowNo: "",
  sku: "20251125_mizushima_02",
  orderNo: "25-13888-57021",
  account: "signpost",
  soldAt: "12/2",
  acquiredAt: "12/3",
  country: "アメリカ",
  mag: "",
  saleAmount: "$300.00",
  purchasedFlag: "FALSE",
  purchasePrice: "¥24,980",
  shippingCost: "¥10,735",
  points: "",
  note: "関税・手数料合算",
  packer: "小栁12/9",
  shippingSite: "elogi",
  actualWeight: "307",
  dimensionalWeight: "728",
  length: "32.5",
  width: "28",
  height: "4",
  size: "73",
  shippingChatAt: "",
  itemName: "Canon PowerShot SX620 HS Black 20.2MP 25x Zoom Compact digital camera Tested",
  supplier: "メルカリショップ",
  firstMailAt: "12/2",
  receiptPrintedAt: "",
  domesticTrackingNo: "",
  slsTrackingNo: "",
  yamatoSlipFlag: "FALSE",
  balanceCheckedFlag: "FALSE",
} satisfies SupplierSource;

export function createSupplierSourceDraft(
  source: SupplierSource = supplierSourceSample,
): SupplierSource {
  return { ...source, id: source.id || "", rowNo: source.rowNo || "" };
}

export function formatYen(value: number) {
  if (!value) return "";
  return `¥${value.toLocaleString("ja-JP")}`;
}

export function formatMoneyAmount(
  value: string | number | null | undefined,
  currency: string | number | null | undefined,
) {
  const amount = Number(value || 0);
  if (!amount) return "";

  const currencyLabel = String(currency || "USD");
  if (currencyLabel === "JPY") return formatYen(amount);

  return `${currencyLabel} ${amount.toLocaleString("ja-JP", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
}

export function normalizeSampleDate(value: string) {
  if (!value) return "";
  if (/^\d{4}-\d{2}-\d{2}$/.test(value)) return value;

  const match = value.match(/^(\d{1,2})[/.](\d{1,2})$/);
  if (!match) return value;

  const [, month, day] = match;
  return `2025-${month.padStart(2, "0")}-${day.padStart(2, "0")}`;
}

export function wordpressRestUrl(baseUrl: string, route: string) {
  return `${baseUrl.replace(/\/$/, "")}/index.php?rest_route=${route}`;
}

export function supplierSourceFromApi(row: SupplierSourceApiRow): SupplierSource {
  return {
    id: String(row.id || ""),
    rowNo: String(row.source_row_no || row.id || ""),
    sku: String(row.sku || ""),
    orderNo: String(row.order_no || ""),
    account: String(row.account_name || ""),
    soldAt: String(row.sold_at_raw || row.sold_at || ""),
    acquiredAt: String(row.acquired_at_raw || row.acquired_at || ""),
    country: String(row.buyer_country || ""),
    mag: String(row.mag || ""),
    saleAmount: formatMoneyAmount(row.sale_amount, row.sale_currency),
    purchasedFlag: String(row.purchased_flag || "FALSE"),
    purchasePrice: formatYen(Number(row.purchase_price_jpy || 0)),
    shippingCost: formatYen(Number(row.shipping_cost_jpy || 0)),
    points: String(row.points || ""),
    note: String(row.notes || ""),
    packer: String(row.packer || ""),
    shippingSite: String(row.shipping_site || ""),
    actualWeight: String(row.actual_weight_g || ""),
    dimensionalWeight: String(row.dimensional_weight_g || ""),
    length: String(row.package_length_cm || ""),
    width: String(row.package_width_cm || ""),
    height: String(row.package_height_cm || ""),
    size: String(row.size_memo || ""),
    shippingChatAt: String(row.shipping_chat_at_raw || ""),
    itemName: String(row.item_name || ""),
    supplier: String(row.supplier_name_raw || ""),
    firstMailAt: String(row.first_mail_at_raw || ""),
    receiptPrintedAt: String(row.receipt_printed_at_raw || ""),
    domesticTrackingNo: String(row.domestic_tracking_no || ""),
    slsTrackingNo: String(row.sls_tracking_no || ""),
    yamatoSlipFlag: String(row.yamato_slip_flag || ""),
    balanceCheckedFlag: String(row.balance_checked_flag || ""),
  };
}

export function supplierSourceToSubmitPayload(
  source: SupplierSource,
): SupplierSourceSubmitPayload {
  return {
    source_row_no: Number(source.rowNo) || 0,
    sku: source.sku,
    order_no: source.orderNo,
    account_name: source.account,
    sold_at: normalizeSampleDate(source.soldAt),
    acquired_at: normalizeSampleDate(source.acquiredAt),
    buyer_country: source.country,
    mag: source.mag,
    sale_amount: source.saleAmount,
    purchased_flag: source.purchasedFlag,
    purchase_price: source.purchasePrice,
    shipping_cost: source.shippingCost,
    points: source.points,
    shipping_note: source.note,
    packer: source.packer,
    shipping_site: source.shippingSite,
    actual_weight_g: Number(source.actualWeight) || 0,
    dimensional_weight_g: Number(source.dimensionalWeight) || 0,
    package_length_cm: source.length,
    package_width_cm: source.width,
    package_height_cm: source.height,
    size_memo: source.size,
    shipping_chat_at: source.shippingChatAt,
    item_name: source.itemName,
    acquired_from: source.supplier,
    first_mail_at: source.firstMailAt,
    receipt_printed_at: source.receiptPrintedAt,
    domestic_tracking_no: source.domesticTrackingNo,
    sls_tracking_no: source.slsTrackingNo,
    yamato_slip_flag: source.yamatoSlipFlag,
    balance_checked_flag: source.balanceCheckedFlag,
    sold_to: "ebay",
    status: source.soldAt ? "sold" : "in_stock",
  };
}

export function supplierSourceToUpdatePayload(
  source: SupplierSource,
): SupplierSourceSubmitPayload {
  return supplierSourceToSubmitPayload(source);
}

export function upsertSupplierSource(
  rows: SupplierSource[],
  nextRow: SupplierSource,
): SupplierSource[] {
  const key = nextRow.sku.trim();
  if (!key) return rows;

  const existingIndex = rows.findIndex((row) => row.sku.trim() === key);
  if (existingIndex === -1) return [nextRow, ...rows];

  return rows.map((row, index) => (index === existingIndex ? nextRow : row));
}

export function purchaseProjectionFromApi(
  row: PurchaseProjectionApiRow,
): PurchaseProjectionRow {
  return {
    itemId: String(row.id || ""),
    sku: String(row.sku || ""),
    orderNo: String(row.order_no || ""),
    acquiredAt: String(row.acquired_at || ""),
    supplier: String(row.acquired_from || ""),
    purchasePrice: formatYen(Number(row.purchase_price || 0)),
    category: String(row.category || ""),
    accessories: String(row.accessories || ""),
    conditionLabel: String(row.condition_label || ""),
    description: String(row.description || ""),
    photoUrl: String(row.photo_url || ""),
    itemName: String(row.item_name || ""),
    soldAt: String(row.sold_at || ""),
    soldTo: String(row.sold_to || ""),
    saleAmount: formatMoneyAmount(row.sale_amount, row.sale_currency),
  };
}

export function mergePurchaseProjectionRows(
  sources: SupplierSource[],
  itemRows: PurchaseProjectionRow[],
): PurchaseProjectionRow[] {
  const itemRowsBySku = new Map(itemRows.map((row) => [row.sku, row]));

  return sources.map((source) => {
    const itemRow = itemRowsBySku.get(source.sku);
    if (!itemRow) {
      return {
        itemId: "",
        sku: source.sku,
        orderNo: source.orderNo,
        acquiredAt: source.acquiredAt,
        supplier: source.supplier,
        purchasePrice: source.purchasePrice,
        category: "",
        accessories: "",
        conditionLabel: "",
        description: "",
        photoUrl: "",
        itemName: source.itemName,
        soldAt: source.soldAt,
        soldTo: "ebay",
        saleAmount: source.saleAmount,
      };
    }

    return {
      ...itemRow,
      orderNo: itemRow.orderNo || source.orderNo,
      acquiredAt: itemRow.acquiredAt || source.acquiredAt,
      supplier: itemRow.supplier || source.supplier,
      purchasePrice: itemRow.purchasePrice || source.purchasePrice,
      itemName: itemRow.itemName || source.itemName,
      soldAt: itemRow.soldAt || source.soldAt,
      saleAmount: itemRow.saleAmount || source.saleAmount,
    };
  });
}

export function purchaseProjectionToUpdatePayload(
  row: PurchaseProjectionRow,
) {
  return {
    category: row.category,
    accessories: row.accessories,
    condition_label: row.conditionLabel,
    description: row.description,
    photo_url: row.photoUrl,
    sold_to: row.soldTo,
  };
}

export function upsertPurchaseProjectionRow(
  rows: PurchaseProjectionRow[],
  nextRow: PurchaseProjectionRow,
): PurchaseProjectionRow[] {
  return rows.map((row) => (row.sku === nextRow.sku ? nextRow : row));
}
