type ApiValue = string | number | null | undefined;

export const paymentViews = ["ペイメント原票", "取り込み履歴"] as const;

export type PaymentView = (typeof paymentViews)[number];

export type PaymentTransaction = {
  id: string;
  orderNo: string;
  transactionDate: string;
  payoutDate: string;
  buyerUsername: string;
  grossAmount: string;
  netAmount: string;
  payoutCurrency: string;
  transactionCurrency: string;
  payoutMethod: string;
  payoutStatus: string;
  createdAt: string;
};

export type PaymentImportBatch = {
  id: string;
  filename: string;
  status: string;
  importedRows: number;
  skippedRows: number;
  skipDetails: string;
  createdAt: string;
  completedAt: string;
};

export type PaymentTransactionApiRow = {
  id?: ApiValue;
  transaction_date?: ApiValue;
  order_no?: ApiValue;
  buyer_username?: ApiValue;
  net_amount?: ApiValue;
  payout_currency?: ApiValue;
  payout_date?: ApiValue;
  payout_method?: ApiValue;
  payout_status?: ApiValue;
  gross_transaction_amount?: ApiValue;
  transaction_currency?: ApiValue;
  created_at?: ApiValue;
};

export type PaymentImportBatchApiRow = {
  id?: ApiValue;
  original_filename?: ApiValue;
  status?: ApiValue;
  imported_rows?: ApiValue;
  error_rows?: ApiValue;
  notes?: ApiValue;
  created_at?: ApiValue;
  completed_at?: ApiValue;
};

export type PaymentsApiResponse = {
  transactions?: PaymentTransactionApiRow[];
  batches?: PaymentImportBatchApiRow[];
};
