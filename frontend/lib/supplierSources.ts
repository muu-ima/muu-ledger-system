import type {
  SupplierSource,
  SupplierSourceApiRow,
} from "@/types/supplier";

export const supplierSourceSample = {
  rowNo: "1",
  sku: "20251125_mizushima_02",
  orderNo: "25-13888-57021",
  account: "signpost",
  soldAt: "12/2",
  acquiredAt: "12/3",
  country: "アメリカ",
  mag: "",
  saleAmount: "$300.00",
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
  itemName: "Canon PowerShot SX620 HS Black 20.2MP 25x Zoom Compact digital camera Tested",
  supplier: "メルカリショップ",
  firstMailAt: "12/2",
  receiptPrintedAt: "",
} satisfies SupplierSource;

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
    rowNo: String(row.source_row_no || row.id || ""),
    sku: String(row.sku || ""),
    orderNo: String(row.order_no || ""),
    account: String(row.account_name || ""),
    soldAt: String(row.sold_at_raw || row.sold_at || ""),
    acquiredAt: String(row.acquired_at_raw || row.acquired_at || ""),
    country: String(row.buyer_country || ""),
    mag: String(row.mag || ""),
    saleAmount: formatMoneyAmount(row.sale_amount, row.sale_currency),
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
    itemName: String(row.item_name || ""),
    supplier: String(row.supplier_name_raw || ""),
    firstMailAt: String(row.first_mail_at_raw || ""),
    receiptPrintedAt: String(row.receipt_printed_at_raw || ""),
  };
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
