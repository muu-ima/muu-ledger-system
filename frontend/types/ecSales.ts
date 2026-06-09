type ApiValue = string | number | null | undefined;

export const ecSalesViews = ["集計ビュー", "ペイメント", "為替レート"] as const;
export const ecSalesSummaryViews = [
  "全体",
  "収益",
  "手数料・為替",
  "配送・日付",
] as const;

export type EcSalesView = (typeof ecSalesViews)[number];
export type EcSalesSummaryView = (typeof ecSalesSummaryViews)[number];

export type MarketplaceRegion = "sg" | "ph" | "unknown";

export type CurrencyCode =
  | "JPY"
  | "SGD"
  | "PHP"
  | "USD"
  | "GBP"
  | "EUR"
  | "CAD"
  | "AUD"
  | "BRL"
  | "UNKNOWN";

export type ShopeeOrder = {
  orderId: string;
  orderStatus: string;
  returnRefundStatus: string;
  trackingNumber: string;
  shippingOption: string;
  shipmentMethod: string;
  estimatedShipOutAt: string;
  shippedAt: string;
  orderCreatedAt: string;
  orderPaidAt: string;
  sku: string;
  productName: string;
  variationName: string;
  originalPrice: string;
  dealPrice: string;
  quantity: string;
  returnedQuantity: string;
  buyerPaidAmount: string;
  buyerPaidShippingFee: string;
  shippingRebateEstimate: string;
  reverseShippingFee: string;
  serviceFee: string;
  commissionFee: string;
  transactionFee: string;
  grandTotal: string;
  estimatedShippingFee: string;
  buyerUsername: string;
  receiverName: string;
  phoneNumber: string;
  deliveryAddress: string;
  town: string;
  district: string;
  city: string;
  province: string;
  country: string;
  zipCode: string;
  orderCompletedAt: string;
  note: string;
  region: MarketplaceRegion;
  currency: CurrencyCode;
};

export type ShopeeOrderApiRow = {
  order_id?: ApiValue;
  order_status?: ApiValue;
  return_refund_status?: ApiValue;
  tracking_number?: ApiValue;
  shipping_option?: ApiValue;
  shipment_method?: ApiValue;
  estimated_ship_out_at?: ApiValue;
  shipped_at?: ApiValue;
  order_created_at?: ApiValue;
  order_paid_at?: ApiValue;
  sku?: ApiValue;
  product_name?: ApiValue;
  variation_name?: ApiValue;
  original_price?: ApiValue;
  deal_price?: ApiValue;
  quantity?: ApiValue;
  returned_quantity?: ApiValue;
  buyer_paid_amount?: ApiValue;
  buyer_paid_shipping_fee?: ApiValue;
  shipping_rebate_estimate?: ApiValue;
  reverse_shipping_fee?: ApiValue;
  service_fee?: ApiValue;
  commission_fee?: ApiValue;
  transaction_fee?: ApiValue;
  grand_total?: ApiValue;
  estimated_shipping_fee?: ApiValue;
  buyer_username?: ApiValue;
  receiver_name?: ApiValue;
  phone_number?: ApiValue;
  delivery_address?: ApiValue;
  town?: ApiValue;
  district?: ApiValue;
  city?: ApiValue;
  province?: ApiValue;
  country?: ApiValue;
  zip_code?: ApiValue;
  order_completed_at?: ApiValue;
  note?: ApiValue;
  region?: ApiValue;
  currency?: ApiValue;
};

export type ShopeePayment = {
  orderId: string;
  buyerUsername: string;
  orderCreatedAt: string;
  paymentMethod: string;
  paymentMethodDetails: string;
  installmentPlan: string;
  transactionFeeRate: string;
  payoutCompletedAt: string;
  originalProductPrice: string;
  sellerPromotion: string;
  refundAmount: string;
  shopeeRebate: string;
  sellerVoucher: string;
  cofundVoucher: string;
  sellerCoinCashback: string;
  cofundCoinCashback: string;
  buyerPaidShippingFee: string;
  shippingFeeRebate: string;
  definedShippingFee: string;
  reverseShippingFee: string;
  returnToSellerShippingFee: string;
  shippingFeeSupportSavings: string;
  amsCommissionFee: string;
  commissionFee: string;
  logisticsFailDeliveryFee: string;
  logisticsReturnRefundFee: string;
  serviceFee: string;
  supportProgramFee: string;
  transactionFee: string;
  fbsFee: string;
  totalReleasedAmount: string;
  sellerVoucherCode: string;
  lostCompensation: string;
  estimatedOrderWeight: string;
  sellerShippingPromotion: string;
  shippingProvider: string;
  courierName: string;
  cashRefundToBuyerAmount: string;
  returnRefundCoinOffset: string;
  returnRefundShopeeVoucherOffset: string;
  returnRefundBankPromoOffset: string;
  returnRefundPaymentChannelPromoOffset: string;
  region: MarketplaceRegion;
  currency: CurrencyCode;
};

export type ShopeePaymentApiRow = {
  order_id?: ApiValue;
  buyer_username?: ApiValue;
  order_created_at?: ApiValue;
  payment_method?: ApiValue;
  payment_method_details?: ApiValue;
  installment_plan?: ApiValue;
  transaction_fee_rate?: ApiValue;
  payout_completed_at?: ApiValue;
  original_product_price?: ApiValue;
  seller_promotion?: ApiValue;
  refund_amount?: ApiValue;
  shopee_rebate?: ApiValue;
  seller_voucher?: ApiValue;
  cofund_voucher?: ApiValue;
  seller_coin_cashback?: ApiValue;
  cofund_coin_cashback?: ApiValue;
  buyer_paid_shipping_fee?: ApiValue;
  shipping_fee_rebate?: ApiValue;
  defined_shipping_fee?: ApiValue;
  reverse_shipping_fee?: ApiValue;
  return_to_seller_shipping_fee?: ApiValue;
  shipping_fee_support_savings?: ApiValue;
  ams_commission_fee?: ApiValue;
  commission_fee?: ApiValue;
  logistics_fail_delivery_fee?: ApiValue;
  logistics_return_refund_fee?: ApiValue;
  service_fee?: ApiValue;
  support_program_fee?: ApiValue;
  transaction_fee?: ApiValue;
  fbs_fee?: ApiValue;
  total_released_amount?: ApiValue;
  seller_voucher_code?: ApiValue;
  lost_compensation?: ApiValue;
  estimated_order_weight?: ApiValue;
  seller_shipping_promotion?: ApiValue;
  shipping_provider?: ApiValue;
  courier_name?: ApiValue;
  cash_refund_to_buyer_amount?: ApiValue;
  return_refund_coin_offset?: ApiValue;
  return_refund_shopee_voucher_offset?: ApiValue;
  return_refund_bank_promo_offset?: ApiValue;
  return_refund_payment_channel_promo_offset?: ApiValue;
  region?: ApiValue;
  currency?: ApiValue;
};

export type ShopeePurchase = {
  sku: string;
  account: string;
  orderNo: string;
  soldAt: string;
  firstChatAt: string;
  purchasedAt: string;
  listedAt: string;
  country: string;
  saleAmount: string;
  purchasedFlag: string;
  purchasePrice: string;
  domesticShippingFee: string;
  slsShippingFee: string;
  points: string;
  note: string;
  packer: string;
  actualWeightG: string;
  lengthCm: string;
  widthCm: string;
  heightCm: string;
  sizeMemo: string;
  shippingChatAt: string;
  itemName: string;
  supplierName: string;
  receiptPrintedAt: string;
  domesticTrackingNo: string;
  slsTrackingNo: string;
  yamatoSlipFlag: string;
  bundledWith: string;
  balanceCheckedFlag: string;
};

export type ShopeePurchaseApiRow = {
  sku?: ApiValue;
  account?: ApiValue;
  order_no?: ApiValue;
  sold_at?: ApiValue;
  first_chat_at?: ApiValue;
  purchased_at?: ApiValue;
  listed_at?: ApiValue;
  country?: ApiValue;
  sale_amount?: ApiValue;
  purchased_flag?: ApiValue;
  purchase_price?: ApiValue;
  domestic_shipping_fee?: ApiValue;
  sls_shipping_fee?: ApiValue;
  points?: ApiValue;
  note?: ApiValue;
  packer?: ApiValue;
  actual_weight_g?: ApiValue;
  length_cm?: ApiValue;
  width_cm?: ApiValue;
  height_cm?: ApiValue;
  size_memo?: ApiValue;
  shipping_chat_at?: ApiValue;
  item_name?: ApiValue;
  supplier_name?: ApiValue;
  receipt_printed_at?: ApiValue;
  domestic_tracking_no?: ApiValue;
  sls_tracking_no?: ApiValue;
  yamato_slip_flag?: ApiValue;
  bundled_with?: ApiValue;
  balance_checked_flag?: ApiValue;
};

export type ShopeeExchangeRate = {
  rateDate: string;
  usd: number;
  gbp: number;
  eur: number;
  cad: number;
  aud: number;
  php: number;
  sgd: number;
  brl: number;
};

export type ShopeeExchangeRateApiRow = {
  rate_date?: ApiValue;
  usd?: ApiValue;
  gbp?: ApiValue;
  eur?: ApiValue;
  cad?: ApiValue;
  aud?: ApiValue;
  php?: ApiValue;
  sgd?: ApiValue;
  brl?: ApiValue;
};

export type ShopeeSupplier = {
  rowNo: string;
  sku: string;
  orderNo: string;
  account: string;
  soldAt: string;
  purchasedAt: string;
  country: string;
  mag: string;
  saleAmount: string;
  purchasePrice: string;
  shippingActualYen: string;
  pointsNote: string;
  shippingNote: string;
  packer: string;
  shippingService: string;
  weightG: string;
  appliedWeightG: string;
  lengthCm: string;
  widthCm: string;
  heightCm: string;
  itemName: string;
  supplierName: string;
  firstMailAt: string;
  receiptPrintedAt: string;
};

export type ShopeeSupplierApiRow = {
  row_no?: ApiValue;
  sku?: ApiValue;
  order_no?: ApiValue;
  account?: ApiValue;
  sold_at?: ApiValue;
  purchased_at?: ApiValue;
  country?: ApiValue;
  mag?: ApiValue;
  sale_amount?: ApiValue;
  purchase_price?: ApiValue;
  shipping_actual_yen?: ApiValue;
  points_note?: ApiValue;
  shipping_note?: ApiValue;
  packer?: ApiValue;
  shipping_service?: ApiValue;
  weight_g?: ApiValue;
  applied_weight_g?: ApiValue;
  length_cm?: ApiValue;
  width_cm?: ApiValue;
  height_cm?: ApiValue;
  item_name?: ApiValue;
  supplier_name?: ApiValue;
  first_mail_at?: ApiValue;
  receipt_printed_at?: ApiValue;
};

export type EcSalesRecord = {
  bundledFlag: string;
  sku: string;
  orderNo: string;
  purchaseDate: string;
  listedAt: string;
  soldAt: string;
  payoutAt: string;
  itemName: string;
  purchasePriceJpy: string;
  saleAmountRaw: string;
  saleAmountJpy: string;
  totalFeesRaw: string;
  adFeeRaw: string;
  marketplaceFeeRaw: string;
  payoutAmountRaw: string;
  saleExchangeRate: string;
  payoutExchangeRate: string;
  receivedAmountJpy: string;
  overseasShippingYen: string;
  feeTaxRefundJpy: string;
  purchaseTaxRefundJpy: string;
  profitJpy: string;
  profitRate: string;
  daysToSell: string;
  domesticTrackingNo: string;
  slsTrackingNo: string;
  settlementNote: string;
};

export type EcSalesRecordApiRow = {
  bundled_flag?: ApiValue;
  sku?: ApiValue;
  order_no?: ApiValue;
  purchase_date?: ApiValue;
  listed_at?: ApiValue;
  sold_at?: ApiValue;
  payout_at?: ApiValue;
  item_name?: ApiValue;
  purchase_price_jpy?: ApiValue;
  sale_amount_raw?: ApiValue;
  sale_amount_jpy?: ApiValue;
  total_fees_raw?: ApiValue;
  ad_fee_raw?: ApiValue;
  marketplace_fee_raw?: ApiValue;
  payout_amount_raw?: ApiValue;
  sale_exchange_rate?: ApiValue;
  payout_exchange_rate?: ApiValue;
  received_amount_jpy?: ApiValue;
  overseas_shipping_yen?: ApiValue;
  fee_tax_refund_jpy?: ApiValue;
  purchase_tax_refund_jpy?: ApiValue;
  profit_jpy?: ApiValue;
  profit_rate?: ApiValue;
  days_to_sell?: ApiValue;
  domestic_tracking_no?: ApiValue;
  sls_tracking_no?: ApiValue;
  settlement_note?: ApiValue;
};
