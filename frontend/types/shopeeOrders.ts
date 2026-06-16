type ApiValue = string | number | null | undefined;

export const shopeeOrderViews = ["オーダー原票", "取り込み履歴"] as const;

export type ShopeeOrderView = (typeof shopeeOrderViews)[number];

export type ShopeeOrder = {
  id: string;
  orderNo: string;
  orderStatus: string;
  orderCreatedAt: string;
  orderPaidAt: string;
  orderCompletedAt: string;
  shipTime: string;
  estimatedShipOutAt: string;
  buyerUsername: string;
  country: string;
  parentSku: string;
  sku: string;
  productName: string;
  variationName: string;
  quantity: number;
  returnedQuantity: number;
  grossAmount: string;
  totalAmount: string;
  grandTotal: string;
  displayAmount: string;
  currency: string;
  trackingNumber: string;
  shippingOption: string;
  shipmentMethod: string;
  cancelReason: string;
  returnRefundStatus: string;
  sourceLineNumber: string;
  createdAt: string;
};

export type ShopeeOrderImportBatch = {
  id: string;
  filename: string;
  status: string;
  importedRows: number;
  skippedRows: number;
  skipDetails: string;
  createdAt: string;
  completedAt: string;
};

export type ShopeeOrderApiRow = {
  id?: ApiValue;
  order_no?: ApiValue;
  order_status?: ApiValue;
  order_created_at?: ApiValue;
  order_paid_at?: ApiValue;
  order_completed_at?: ApiValue;
  ship_time?: ApiValue;
  estimated_ship_out_at?: ApiValue;
  buyer_username?: ApiValue;
  country?: ApiValue;
  parent_sku?: ApiValue;
  sku?: ApiValue;
  product_name?: ApiValue;
  variation_name?: ApiValue;
  quantity?: ApiValue;
  returned_quantity?: ApiValue;
  gross_amount?: ApiValue;
  total_amount?: ApiValue;
  grand_total?: ApiValue;
  currency?: ApiValue;
  tracking_number?: ApiValue;
  shipping_option?: ApiValue;
  shipment_method?: ApiValue;
  cancel_reason?: ApiValue;
  return_refund_status?: ApiValue;
  source_line_number?: ApiValue;
  created_at?: ApiValue;
};

export type ShopeeOrderImportBatchApiRow = {
  id?: ApiValue;
  original_filename?: ApiValue;
  status?: ApiValue;
  imported_rows?: ApiValue;
  error_rows?: ApiValue;
  notes?: ApiValue;
  created_at?: ApiValue;
  completed_at?: ApiValue;
};

export type ShopeeOrdersApiResponse = {
  orders?: ShopeeOrderApiRow[];
  batches?: ShopeeOrderImportBatchApiRow[];
};
