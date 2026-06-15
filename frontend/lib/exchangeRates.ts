import { wordpressRestUrl } from "@/lib/supplierSources";
import { resolveWordpressBaseUrl } from "@/lib/wordpressShellAuth";
import type {
  ExchangeRateApiRow,
  ExchangeRateFetchStatus,
  ExchangeRateRow,
  ExchangeRatesApiResponse,
} from "@/types/exchangeRates";

function text(value: string | number | boolean | null | undefined) {
  return String(value ?? "");
}

function numberValue(value: string | number | boolean | null | undefined) {
  const parsed = Number(value ?? 0);
  return Number.isFinite(parsed) ? parsed : 0;
}

function booleanValue(value: string | number | boolean | null | undefined) {
  return value === true || value === 1 || value === "1";
}

function formatRate(value: string | number | boolean | null | undefined) {
  const parsed = numberValue(value);
  if (!parsed) return "";

  return parsed.toLocaleString("ja-JP", {
    maximumFractionDigits: 6,
    minimumFractionDigits: 2,
  });
}

export function normalizeExchangeRateRow(
  row: ExchangeRateApiRow,
): ExchangeRateRow {
  const baseCurrency = text(row.base_currency);
  const quoteCurrency = text(row.quote_currency || "JPY");

  return {
    id: text(row.id),
    rateDate: text(row.rate_date),
    pair: `${baseCurrency}/${quoteCurrency}`,
    baseCurrency,
    quoteCurrency,
    rate: formatRate(row.rate),
    source: text(row.source),
    isManualOverride: booleanValue(row.is_manual_override),
    fetchedAt: text(row.fetched_at),
    notes: text(row.notes),
  };
}

export function normalizeExchangeRateFetchStatus(
  value: ExchangeRatesApiResponse["last_fetch"],
): ExchangeRateFetchStatus {
  return {
    ranAt: text(value?.ran_at),
    ok: booleanValue(value?.ok),
    date: text(value?.date),
    message: text(value?.message),
    saved: numberValue(value?.saved),
    skipped: numberValue(value?.skipped),
  };
}

export async function fetchExchangeRates() {
  const baseUrl = resolveWordpressBaseUrl(
    process.env.NEXT_PUBLIC_WORDPRESS_URL || "",
  );
  const response = await fetch(
    wordpressRestUrl(baseUrl, "/kobutsu/v1/exchange-rates"),
    { credentials: "include" },
  );

  if (!response.ok) {
    throw new Error("為替レートを取得できませんでした");
  }

  const data = (await response.json()) as ExchangeRatesApiResponse;

  return {
    rates: (data.rates ?? []).map(normalizeExchangeRateRow),
    lastFetch: normalizeExchangeRateFetchStatus(data.last_fetch),
    nextFetchAt: text(data.next_fetch_at),
  };
}
