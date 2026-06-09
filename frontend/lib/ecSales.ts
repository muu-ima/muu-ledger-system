import type {
  CurrencyCode,
  EcSalesRecord,
  EcSalesRecordApiRow,
  MarketplaceRegion,
  ShopeeExchangeRate,
  ShopeeExchangeRateApiRow,
  ShopeeOrder,
  ShopeeOrderApiRow,
  ShopeePayment,
  ShopeePaymentApiRow,
  ShopeePurchase,
  ShopeePurchaseApiRow,
  ShopeeSupplier,
  ShopeeSupplierApiRow,
} from "@/types/ecSales";

type ApiValue = string | number | null | undefined;

const emptyMarkers = new Set(["", "#N/A", "#VALUE!", "#REF!", "N/A"]);

function normalizeText(value: ApiValue) {
  const normalized = String(value ?? "").replace(/\r?\n/g, " ").trim();
  return emptyMarkers.has(normalized) ? "" : normalized;
}

export function normalizeKey(value: ApiValue) {
  return normalizeText(value);
}

export function normalizeFlag(value: ApiValue) {
  const normalized = normalizeText(value).toUpperCase();
  if (normalized === "TRUE") return "TRUE";
  if (normalized === "FALSE") return "FALSE";
  return normalizeText(value);
}

export function normalizeDate(
  value: ApiValue,
  options?: { fallbackYear?: number },
) {
  const normalized = normalizeText(value);
  if (!normalized) return "";

  if (/^\d{4}-\d{2}-\d{2}$/.test(normalized)) return normalized;

  const slashDate = normalized.match(/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/);
  if (slashDate) {
    const [, year, month, day] = slashDate;
    return `${year}-${month.padStart(2, "0")}-${day.padStart(2, "0")}`;
  }

  const dottedDate = normalized.match(/^(\d{1,2})[/.](\d{1,2})$/);
  if (dottedDate && options?.fallbackYear) {
    const [, month, day] = dottedDate;
    return `${String(options.fallbackYear)}-${month.padStart(2, "0")}-${day.padStart(2, "0")}`;
  }

  return normalized;
}

export function parseNumberLike(value: ApiValue) {
  if (typeof value === "number") return Number.isFinite(value) ? value : 0;

  const normalized = normalizeText(value);
  if (!normalized) return 0;

  const numeric = normalized
    .replace(/[¥₱,$£€￡]/g, "")
    .replace(/SGD|PHP|JPY|USD|GBP|EUR|CAD|AUD|BRL|SDG/gi, "")
    .replace(/\s+/g, "")
    .replace(/,/g, "");

  const parsed = Number(numeric);
  return Number.isFinite(parsed) ? parsed : 0;
}

export function normalizeCurrency(
  value: ApiValue,
  fallbackRegion?: MarketplaceRegion,
) {
  const normalized = normalizeText(value).toUpperCase();

  if (normalized === "JPY" || normalized.includes("¥")) return "JPY";
  if (normalized === "PHP" || normalized.includes("₱")) return "PHP";
  if (normalized === "SGD" || normalized === "SDG") return "SGD";
  if (normalized === "USD" || normalized.includes("$")) return "USD";
  if (normalized === "GBP" || normalized.includes("£") || normalized.includes("￡")) {
    return "GBP";
  }
  if (normalized === "EUR" || normalized.includes("€")) return "EUR";
  if (normalized === "CAD") return "CAD";
  if (normalized === "AUD") return "AUD";
  if (normalized === "BRL") return "BRL";

  if (fallbackRegion === "ph") return "PHP";
  if (fallbackRegion === "sg") return "SGD";

  return "UNKNOWN";
}

export function normalizeRegion(
  value: ApiValue,
  country?: ApiValue,
  currency?: ApiValue,
): MarketplaceRegion {
  const raw = [normalizeText(value), normalizeText(country), normalizeText(currency)]
    .join(" ")
    .toLowerCase();

  if (
    raw.includes("ph") ||
    raw.includes("philippines") ||
    raw.includes("フィリピン") ||
    raw.includes("php")
  ) {
    return "ph";
  }

  if (
    raw.includes("sg") ||
    raw.includes("singapore") ||
    raw.includes("シンガポール") ||
    raw.includes("sgd")
  ) {
    return "sg";
  }

  return "unknown";
}

export function formatMoneyAmount(value: ApiValue, currency: CurrencyCode) {
  if (currency === "UNKNOWN") return normalizeText(value);

  const amount = parseNumberLike(value);
  if (!amount) return "";

  if (currency === "JPY") {
    return `¥${amount.toLocaleString("ja-JP")}`;
  }

  return `${currency}${amount.toLocaleString("ja-JP", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
}

export function normalizeMoneyAmount(
  value: ApiValue,
  explicitCurrency?: ApiValue,
  fallbackRegion?: MarketplaceRegion,
) {
  const currency = normalizeCurrency(
    explicitCurrency || value,
    fallbackRegion,
  );

  if (currency === "UNKNOWN") return normalizeText(value);
  return formatMoneyAmount(value, currency);
}

function formatRate(value: number) {
  if (!value) return "";
  return value.toLocaleString("ja-JP", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

function formatRatio(value: number) {
  if (!Number.isFinite(value)) return "";
  return value.toLocaleString("ja-JP", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

function moneyCurrencyFromRaw(value: string) {
  return normalizeCurrency(value);
}

function exchangeRateForCurrency(
  rate: ShopeeExchangeRate | null,
  currency: CurrencyCode,
) {
  if (!rate) return 0;

  switch (currency) {
    case "USD":
      return rate.usd;
    case "GBP":
      return rate.gbp;
    case "EUR":
      return rate.eur;
    case "CAD":
      return rate.cad;
    case "AUD":
      return rate.aud;
    case "PHP":
      return rate.php;
    case "SGD":
      return rate.sgd;
    case "BRL":
      return rate.brl;
    case "JPY":
    case "UNKNOWN":
    default:
      return 0;
  }
}

function findExchangeRateByDate(
  exchangeRates: ShopeeExchangeRate[],
  date: string,
) {
  if (!date) return null;
  return exchangeRates.find((rate) => rate.rateDate === date) ?? null;
}

function findPaymentByOrderNo(
  payments: ShopeePayment[],
  orderNo: string,
) {
  if (!orderNo) return null;
  return payments.find((payment) => payment.orderId === orderNo) ?? null;
}

export function normalizeExchangeRateRow(
  row: ShopeeExchangeRateApiRow,
): ShopeeExchangeRate {
  return {
    rateDate: normalizeDate(row.rate_date),
    usd: parseNumberLike(row.usd),
    gbp: parseNumberLike(row.gbp),
    eur: parseNumberLike(row.eur),
    cad: parseNumberLike(row.cad),
    aud: parseNumberLike(row.aud),
    php: parseNumberLike(row.php),
    sgd: parseNumberLike(row.sgd),
    brl: parseNumberLike(row.brl),
  };
}

export function normalizeShopeePurchase(
  row: ShopeePurchaseApiRow,
): ShopeePurchase {
  return {
    sku: normalizeKey(row.sku),
    account: normalizeText(row.account),
    orderNo: normalizeKey(row.order_no),
    soldAt: normalizeDate(row.sold_at, { fallbackYear: 2026 }),
    firstChatAt: normalizeDate(row.first_chat_at, { fallbackYear: 2026 }),
    purchasedAt: normalizeDate(row.purchased_at, { fallbackYear: 2026 }),
    country: normalizeText(row.country),
    saleAmount: normalizeMoneyAmount(row.sale_amount),
    purchasedFlag: normalizeFlag(row.purchased_flag),
    purchasePrice: normalizeMoneyAmount(row.purchase_price, "JPY"),
    domesticShippingFee: normalizeMoneyAmount(row.domestic_shipping_fee, "JPY"),
    slsShippingFee: normalizeText(row.sls_shipping_fee),
    points: normalizeText(row.points),
    note: normalizeText(row.note),
    packer: normalizeText(row.packer),
    actualWeightG: normalizeText(row.actual_weight_g),
    lengthCm: normalizeText(row.length_cm),
    widthCm: normalizeText(row.width_cm),
    heightCm: normalizeText(row.height_cm),
    sizeMemo: normalizeText(row.size_memo),
    shippingChatAt: normalizeDate(row.shipping_chat_at, { fallbackYear: 2026 }),
    itemName: normalizeText(row.item_name),
    supplierName: normalizeText(row.supplier_name),
    receiptPrintedAt: normalizeDate(row.receipt_printed_at, {
      fallbackYear: 2026,
    }),
    domesticTrackingNo: normalizeText(row.domestic_tracking_no),
    slsTrackingNo: normalizeText(row.sls_tracking_no),
    yamatoSlipFlag: normalizeFlag(row.yamato_slip_flag),
    bundledWith: normalizeText(row.bundled_with),
    balanceCheckedFlag: normalizeFlag(row.balance_checked_flag),
  };
}

export function normalizeShopeeSupplier(
  row: ShopeeSupplierApiRow,
): ShopeeSupplier {
  const region = normalizeRegion(undefined, row.country, row.sale_amount);

  return {
    rowNo: normalizeText(row.row_no),
    sku: normalizeKey(row.sku),
    orderNo: normalizeKey(row.order_no),
    account: normalizeText(row.account),
    soldAt: normalizeDate(row.sold_at, { fallbackYear: 2026 }),
    purchasedAt: normalizeDate(row.purchased_at, { fallbackYear: 2026 }),
    country: normalizeText(row.country),
    mag: normalizeText(row.mag),
    saleAmount: normalizeMoneyAmount(row.sale_amount, undefined, region),
    purchasePrice: normalizeMoneyAmount(row.purchase_price, "JPY"),
    shippingActualYen: normalizeMoneyAmount(row.shipping_actual_yen, "JPY"),
    pointsNote: normalizeText(row.points_note),
    shippingNote: normalizeText(row.shipping_note),
    packer: normalizeText(row.packer),
    shippingService: normalizeText(row.shipping_service),
    weightG: normalizeText(row.weight_g),
    appliedWeightG: normalizeText(row.applied_weight_g),
    lengthCm: normalizeText(row.length_cm),
    widthCm: normalizeText(row.width_cm),
    heightCm: normalizeText(row.height_cm),
    itemName: normalizeText(row.item_name),
    supplierName: normalizeText(row.supplier_name),
    firstMailAt: normalizeDate(row.first_mail_at, { fallbackYear: 2026 }),
    receiptPrintedAt: normalizeDate(row.receipt_printed_at, {
      fallbackYear: 2026,
    }),
  };
}

export function normalizeShopeeOrder(row: ShopeeOrderApiRow): ShopeeOrder {
  const region = normalizeRegion(row.region, row.country, row.currency);
  const currency = normalizeCurrency(row.currency, region);

  return {
    orderId: normalizeKey(row.order_id),
    orderStatus: normalizeText(row.order_status),
    returnRefundStatus: normalizeText(row.return_refund_status),
    trackingNumber: normalizeText(row.tracking_number),
    shippingOption: normalizeText(row.shipping_option),
    shipmentMethod: normalizeText(row.shipment_method),
    estimatedShipOutAt: normalizeDate(row.estimated_ship_out_at),
    shippedAt: normalizeDate(row.shipped_at),
    orderCreatedAt: normalizeDate(row.order_created_at),
    orderPaidAt: normalizeDate(row.order_paid_at),
    sku: normalizeKey(row.sku),
    productName: normalizeText(row.product_name),
    variationName: normalizeText(row.variation_name),
    originalPrice: formatMoneyAmount(row.original_price, currency),
    dealPrice: formatMoneyAmount(row.deal_price, currency),
    quantity: normalizeText(row.quantity),
    returnedQuantity: normalizeText(row.returned_quantity),
    buyerPaidAmount: formatMoneyAmount(row.buyer_paid_amount, currency),
    buyerPaidShippingFee: formatMoneyAmount(row.buyer_paid_shipping_fee, currency),
    shippingRebateEstimate: formatMoneyAmount(row.shipping_rebate_estimate, currency),
    reverseShippingFee: formatMoneyAmount(row.reverse_shipping_fee, currency),
    serviceFee: formatMoneyAmount(row.service_fee, currency),
    commissionFee: formatMoneyAmount(row.commission_fee, currency),
    transactionFee: formatMoneyAmount(row.transaction_fee, currency),
    grandTotal: formatMoneyAmount(row.grand_total, currency),
    estimatedShippingFee: formatMoneyAmount(row.estimated_shipping_fee, currency),
    buyerUsername: normalizeText(row.buyer_username),
    receiverName: normalizeText(row.receiver_name),
    phoneNumber: normalizeText(row.phone_number),
    deliveryAddress: normalizeText(row.delivery_address),
    town: normalizeText(row.town),
    district: normalizeText(row.district),
    city: normalizeText(row.city),
    province: normalizeText(row.province),
    country: normalizeText(row.country),
    zipCode: normalizeText(row.zip_code),
    orderCompletedAt: normalizeDate(row.order_completed_at),
    note: normalizeText(row.note),
    region,
    currency,
  };
}

export function normalizeShopeePayment(
  row: ShopeePaymentApiRow,
): ShopeePayment {
  const region = normalizeRegion(row.region, undefined, row.currency);
  const currency = normalizeCurrency(row.currency, region);

  return {
    orderId: normalizeKey(row.order_id),
    buyerUsername: normalizeText(row.buyer_username),
    orderCreatedAt: normalizeDate(row.order_created_at),
    paymentMethod: normalizeText(row.payment_method),
    paymentMethodDetails: normalizeText(row.payment_method_details),
    installmentPlan: normalizeText(row.installment_plan),
    transactionFeeRate: normalizeText(row.transaction_fee_rate),
    payoutCompletedAt: normalizeDate(row.payout_completed_at),
    originalProductPrice: formatMoneyAmount(row.original_product_price, currency),
    sellerPromotion: formatMoneyAmount(row.seller_promotion, currency),
    refundAmount: formatMoneyAmount(row.refund_amount, currency),
    shopeeRebate: formatMoneyAmount(row.shopee_rebate, currency),
    sellerVoucher: formatMoneyAmount(row.seller_voucher, currency),
    cofundVoucher: formatMoneyAmount(row.cofund_voucher, currency),
    sellerCoinCashback: formatMoneyAmount(row.seller_coin_cashback, currency),
    cofundCoinCashback: formatMoneyAmount(row.cofund_coin_cashback, currency),
    buyerPaidShippingFee: formatMoneyAmount(row.buyer_paid_shipping_fee, currency),
    shippingFeeRebate: formatMoneyAmount(row.shipping_fee_rebate, currency),
    definedShippingFee: formatMoneyAmount(row.defined_shipping_fee, currency),
    reverseShippingFee: formatMoneyAmount(row.reverse_shipping_fee, currency),
    returnToSellerShippingFee: formatMoneyAmount(
      row.return_to_seller_shipping_fee,
      currency,
    ),
    shippingFeeSupportSavings: formatMoneyAmount(
      row.shipping_fee_support_savings,
      currency,
    ),
    amsCommissionFee: formatMoneyAmount(row.ams_commission_fee, currency),
    commissionFee: formatMoneyAmount(row.commission_fee, currency),
    logisticsFailDeliveryFee: formatMoneyAmount(
      row.logistics_fail_delivery_fee,
      currency,
    ),
    logisticsReturnRefundFee: formatMoneyAmount(
      row.logistics_return_refund_fee,
      currency,
    ),
    serviceFee: formatMoneyAmount(row.service_fee, currency),
    supportProgramFee: formatMoneyAmount(row.support_program_fee, currency),
    transactionFee: formatMoneyAmount(row.transaction_fee, currency),
    fbsFee: formatMoneyAmount(row.fbs_fee, currency),
    totalReleasedAmount: formatMoneyAmount(row.total_released_amount, currency),
    sellerVoucherCode: normalizeText(row.seller_voucher_code),
    lostCompensation: formatMoneyAmount(row.lost_compensation, currency),
    estimatedOrderWeight: normalizeText(row.estimated_order_weight),
    sellerShippingPromotion: formatMoneyAmount(
      row.seller_shipping_promotion,
      currency,
    ),
    shippingProvider: normalizeText(row.shipping_provider),
    courierName: normalizeText(row.courier_name),
    cashRefundToBuyerAmount: formatMoneyAmount(
      row.cash_refund_to_buyer_amount,
      currency,
    ),
    returnRefundCoinOffset: formatMoneyAmount(
      row.return_refund_coin_offset,
      currency,
    ),
    returnRefundShopeeVoucherOffset: formatMoneyAmount(
      row.return_refund_shopee_voucher_offset,
      currency,
    ),
    returnRefundBankPromoOffset: formatMoneyAmount(
      row.return_refund_bank_promo_offset,
      currency,
    ),
    returnRefundPaymentChannelPromoOffset: formatMoneyAmount(
      row.return_refund_payment_channel_promo_offset,
      currency,
    ),
    region,
    currency,
  };
}

export function normalizeEcSalesRecord(row: EcSalesRecordApiRow): EcSalesRecord {
  return {
    bundledFlag: normalizeFlag(row.bundled_flag),
    sku: normalizeKey(row.sku),
    orderNo: normalizeKey(row.order_no),
    purchaseDate: normalizeDate(row.purchase_date, { fallbackYear: 2026 }),
    soldAt: normalizeDate(row.sold_at, { fallbackYear: 2026 }),
    payoutAt: normalizeDate(row.payout_at, { fallbackYear: 2026 }),
    itemName: normalizeText(row.item_name),
    purchasePriceJpy: normalizeMoneyAmount(row.purchase_price_jpy, "JPY"),
    saleAmountRaw: normalizeText(row.sale_amount_raw),
    saleAmountJpy: normalizeMoneyAmount(row.sale_amount_jpy, "JPY"),
    totalFeesRaw: normalizeText(row.total_fees_raw),
    adFeeRaw: normalizeText(row.ad_fee_raw),
    marketplaceFeeRaw: normalizeText(row.marketplace_fee_raw),
    payoutAmountRaw: normalizeText(row.payout_amount_raw),
    saleExchangeRate: normalizeText(row.sale_exchange_rate),
    payoutExchangeRate: normalizeText(row.payout_exchange_rate),
    receivedAmountJpy: normalizeMoneyAmount(row.received_amount_jpy, "JPY"),
    overseasShippingYen: normalizeMoneyAmount(row.overseas_shipping_yen, "JPY"),
    feeTaxRefundJpy: normalizeMoneyAmount(row.fee_tax_refund_jpy, "JPY"),
    purchaseTaxRefundJpy: normalizeMoneyAmount(
      row.purchase_tax_refund_jpy,
      "JPY",
    ),
    profitJpy: normalizeMoneyAmount(row.profit_jpy, "JPY"),
    profitRate: normalizeText(row.profit_rate),
    daysToSell: normalizeText(row.days_to_sell),
    domesticTrackingNo: normalizeText(row.domestic_tracking_no),
    slsTrackingNo: normalizeText(row.sls_tracking_no),
    settlementNote: normalizeText(row.settlement_note),
  };
}

function choosePreferredValue(...values: string[]) {
  return values.find((value) => normalizeText(value)) ?? "";
}

function inferBundledFlag(purchase: ShopeePurchase) {
  if (purchase.bundledWith || purchase.domesticTrackingNo.includes("↑同梱")) {
    return "〇";
  }
  return "";
}

function calculateDaysToSell(purchasedAt: string, soldAt: string) {
  if (!purchasedAt || !soldAt) return "";

  const purchasedMs = Date.parse(purchasedAt);
  const soldMs = Date.parse(soldAt);
  if (Number.isNaN(purchasedMs) || Number.isNaN(soldMs)) return "";

  const diffDays = Math.round((soldMs - purchasedMs) / 86400000);
  return String(diffDays);
}

function findSupplierBySkuOrOrder(
  suppliers: ShopeeSupplier[],
  purchase: ShopeePurchase,
) {
  return (
    suppliers.find(
      (supplier) =>
        supplier.sku === purchase.sku ||
        (supplier.orderNo && supplier.orderNo === purchase.orderNo),
    ) ?? null
  );
}

export function buildEcSalesIntermediateRecord(
  purchase: ShopeePurchase,
  supplier?: ShopeeSupplier | null,
): EcSalesRecord {
  const normalizedSupplier = supplier ?? null;
  const purchasedAt = choosePreferredValue(
    purchase.purchasedAt,
    normalizedSupplier?.purchasedAt ?? "",
  );
  const soldAt = choosePreferredValue(
    purchase.soldAt,
    normalizedSupplier?.soldAt ?? "",
  );

  return {
    bundledFlag: inferBundledFlag(purchase),
    sku: purchase.sku,
    orderNo: choosePreferredValue(purchase.orderNo, normalizedSupplier?.orderNo ?? ""),
    purchaseDate: purchasedAt,
    soldAt,
    payoutAt: "",
    itemName: choosePreferredValue(purchase.itemName, normalizedSupplier?.itemName ?? ""),
    purchasePriceJpy: choosePreferredValue(
      purchase.purchasePrice,
      normalizedSupplier?.purchasePrice ?? "",
    ),
    saleAmountRaw: choosePreferredValue(
      purchase.saleAmount,
      normalizedSupplier?.saleAmount ?? "",
    ),
    saleAmountJpy: "",
    totalFeesRaw: "",
    adFeeRaw: "",
    marketplaceFeeRaw: "",
    payoutAmountRaw: "",
    saleExchangeRate: "",
    payoutExchangeRate: "",
    receivedAmountJpy: "",
    overseasShippingYen: normalizedSupplier?.shippingActualYen ?? "",
    feeTaxRefundJpy: "",
    purchaseTaxRefundJpy: "",
    profitJpy: "",
    profitRate: "",
    daysToSell: calculateDaysToSell(purchasedAt, soldAt),
    domesticTrackingNo: purchase.domesticTrackingNo,
    slsTrackingNo: purchase.slsTrackingNo,
    settlementNote: choosePreferredValue(
      purchase.note,
      normalizedSupplier?.shippingNote ?? "",
    ),
  };
}

export function buildEcSalesIntermediateRecords(
  purchases: ShopeePurchase[],
  suppliers: ShopeeSupplier[] = [],
) {
  return purchases.map((purchase) =>
    buildEcSalesIntermediateRecord(
      purchase,
      findSupplierBySkuOrOrder(suppliers, purchase),
    ),
  );
}

export function attachPaymentsToEcSalesRecord(
  record: EcSalesRecord,
  payment?: ShopeePayment | null,
) {
  if (!payment) return record;

  return {
    ...record,
    payoutAt: choosePreferredValue(record.payoutAt, payment.payoutCompletedAt),
    totalFeesRaw: choosePreferredValue(
      record.totalFeesRaw,
      payment.commissionFee,
    ),
    marketplaceFeeRaw: choosePreferredValue(
      record.marketplaceFeeRaw,
      payment.commissionFee,
    ),
    payoutAmountRaw: choosePreferredValue(
      record.payoutAmountRaw,
      payment.totalReleasedAmount,
    ),
  };
}

export function attachExchangeRatesToEcSalesRecord(
  record: EcSalesRecord,
  exchangeRates: ShopeeExchangeRate[],
) {
  const saleCurrency = moneyCurrencyFromRaw(record.saleAmountRaw);
  const payoutCurrency = moneyCurrencyFromRaw(record.payoutAmountRaw);
  const saleRateRow = findExchangeRateByDate(exchangeRates, record.soldAt);
  const payoutRateRow = findExchangeRateByDate(exchangeRates, record.payoutAt);

  const saleExchangeRate = exchangeRateForCurrency(saleRateRow, saleCurrency);
  const payoutExchangeRate = exchangeRateForCurrency(
    payoutRateRow,
    payoutCurrency,
  );

  const saleAmountJpy = saleExchangeRate
    ? formatMoneyAmount(
        parseNumberLike(record.saleAmountRaw) * saleExchangeRate,
        "JPY",
      )
    : record.saleAmountJpy;

  const receivedAmountJpy = payoutExchangeRate
    ? formatMoneyAmount(
        parseNumberLike(record.payoutAmountRaw) * payoutExchangeRate,
        "JPY",
      )
    : record.receivedAmountJpy;

  const profitValue =
    parseNumberLike(receivedAmountJpy) +
    parseNumberLike(record.purchaseTaxRefundJpy) +
    parseNumberLike(record.feeTaxRefundJpy) -
    parseNumberLike(record.purchasePriceJpy) -
    parseNumberLike(record.overseasShippingYen);

  const purchaseBase =
    parseNumberLike(record.purchasePriceJpy) +
    parseNumberLike(record.overseasShippingYen);
  const profitRate = purchaseBase ? (profitValue / purchaseBase) * 100 : 0;

  return {
    ...record,
    saleAmountJpy,
    saleExchangeRate: choosePreferredValue(
      record.saleExchangeRate,
      formatRate(saleExchangeRate),
    ),
    payoutExchangeRate: choosePreferredValue(
      record.payoutExchangeRate,
      formatRate(payoutExchangeRate),
    ),
    receivedAmountJpy,
    profitJpy: profitValue ? formatMoneyAmount(profitValue, "JPY") : "",
    profitRate: profitRate ? formatRatio(profitRate) : record.profitRate,
  };
}

export function buildEcSalesViewRecords(
  purchases: ShopeePurchase[],
  suppliers: ShopeeSupplier[] = [],
  payments: ShopeePayment[] = [],
  exchangeRates: ShopeeExchangeRate[] = [],
) {
  return buildEcSalesIntermediateRecords(purchases, suppliers).map((record) => {
    const payment = findPaymentByOrderNo(payments, record.orderNo);
    const withPayment = attachPaymentsToEcSalesRecord(record, payment);
    return attachExchangeRatesToEcSalesRecord(withPayment, exchangeRates);
  });
}
