type ApiValue = string | number | boolean | null | undefined;

export const exchangeRateViews = ["レート一覧", "取得状況"] as const;

export type ExchangeRateView = (typeof exchangeRateViews)[number];

export type ExchangeRateRow = {
  id: string;
  rateDate: string;
  pair: string;
  baseCurrency: string;
  quoteCurrency: string;
  rate: string;
  source: string;
  isManualOverride: boolean;
  fetchedAt: string;
  notes: string;
};

export type ExchangeRateFetchStatus = {
  ranAt: string;
  ok: boolean;
  date: string;
  message: string;
  saved: number;
  skipped: number;
};

export type ExchangeRateApiRow = {
  id?: ApiValue;
  rate_date?: ApiValue;
  base_currency?: ApiValue;
  quote_currency?: ApiValue;
  rate?: ApiValue;
  source?: ApiValue;
  is_manual_override?: ApiValue;
  fetched_at?: ApiValue;
  notes?: ApiValue;
};

export type ExchangeRatesApiResponse = {
  rates?: ExchangeRateApiRow[];
  last_fetch?: {
    ran_at?: ApiValue;
    ok?: ApiValue;
    date?: ApiValue;
    message?: ApiValue;
    saved?: ApiValue;
    skipped?: ApiValue;
  };
  next_fetch_at?: ApiValue;
};
