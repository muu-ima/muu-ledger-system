import { wordpressRestUrl } from "@/lib/supplierSources";
import { resolveWordpressBaseUrl } from "@/lib/wordpressShellAuth";
import type {
  PaymentImportBatch,
  PaymentImportBatchApiRow,
  PaymentTransaction,
  PaymentTransactionApiRow,
  PaymentsApiResponse,
} from "@/types/payments";

type SkipReasons = {
  db_error?: number;
  duplicate?: number;
  empty?: number;
  header?: number;
  missing_order_id?: number;
};

const skipReasonLabels: Record<keyof SkipReasons, string> = {
  header: "ヘッダー行",
  empty: "空行",
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

export function formatPaymentMoney(
  amount: string | number | null | undefined,
  currency: string | number | null | undefined,
) {
  const numericAmount = numberValue(amount);
  const currencyLabel = text(currency) || "PHP";

  return `${currencyLabel} ${numericAmount.toLocaleString("ja-JP", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
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

export function normalizePaymentTransaction(
  row: PaymentTransactionApiRow,
): PaymentTransaction {
  return {
    id: text(row.id),
    orderNo: text(row.order_no),
    transactionDate: text(row.transaction_date),
    payoutDate: text(row.payout_date),
    buyerUsername: text(row.buyer_username),
    grossAmount: formatPaymentMoney(
      row.gross_transaction_amount,
      row.transaction_currency || row.payout_currency,
    ),
    netAmount: formatPaymentMoney(row.net_amount, row.payout_currency),
    payoutCurrency: text(row.payout_currency || "PHP"),
    transactionCurrency: text(row.transaction_currency || "PHP"),
    payoutMethod: text(row.payout_method),
    payoutStatus: text(row.payout_status),
    createdAt: text(row.created_at),
  };
}

export function normalizePaymentImportBatch(
  row: PaymentImportBatchApiRow,
): PaymentImportBatch {
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

export async function fetchPayments() {
  const baseUrl = resolveWordpressBaseUrl(
    process.env.NEXT_PUBLIC_WORDPRESS_URL || "",
  );
  const response = await fetch(wordpressRestUrl(baseUrl, "/kobutsu/v1/payments"), {
    credentials: "include",
  });

  if (!response.ok) {
    throw new Error("ペイメントを取得できませんでした");
  }

  const data = (await response.json()) as PaymentsApiResponse;

  return {
    transactions: (data.transactions ?? []).map(normalizePaymentTransaction),
    batches: (data.batches ?? []).map(normalizePaymentImportBatch),
  };
}
