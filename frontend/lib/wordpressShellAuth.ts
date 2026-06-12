type WordpressShellAuthPayload = {
  restBaseUrl?: string;
  restNonce?: string;
  wordpressOrigin?: string;
  canWrite?: boolean;
};

let wordpressShellAuth: WordpressShellAuthPayload | null = null;
let refreshPromise: Promise<WordpressShellAuthPayload | null> | null = null;

export function setWordpressShellAuth(payload: WordpressShellAuthPayload) {
  wordpressShellAuth = payload;
}

export function getWordpressShellAuth() {
  return wordpressShellAuth;
}

export function resolveWordpressBaseUrl(defaultBaseUrl: string) {
  return wordpressShellAuth?.restBaseUrl || defaultBaseUrl;
}

export function createWordpressJsonHeaders() {
  const headers: Record<string, string> = {
    "Content-Type": "application/json",
  };

  const nonce = wordpressShellAuth?.restNonce?.trim();
  if (nonce) {
    headers["X-Kobutsu-Nonce"] = nonce;
  }

  return headers;
}

function normalizeHeaders(headers?: HeadersInit): Record<string, string> {
  if (!headers) return {};

  if (headers instanceof Headers) {
    return Object.fromEntries(headers.entries());
  }

  if (Array.isArray(headers)) {
    return Object.fromEntries(headers);
  }

  return { ...headers };
}

function createRetryHeaders(headers?: HeadersInit): Record<string, string> {
  const nextHeaders = normalizeHeaders(headers);
  const nonce = wordpressShellAuth?.restNonce?.trim();

  if (nonce) {
    nextHeaders["X-Kobutsu-Nonce"] = nonce;
  }

  return nextHeaders;
}

export function refreshWordpressShellAuth(timeoutMs = 3000) {
  if (typeof window === "undefined" || window.parent === window) {
    return Promise.resolve(wordpressShellAuth);
  }

  if (refreshPromise) {
    return refreshPromise;
  }

  refreshPromise = new Promise<WordpressShellAuthPayload | null>((resolve) => {
    const defaultBaseUrl = process.env.NEXT_PUBLIC_WORDPRESS_URL || "";
    const wordpressOrigin =
      wordpressShellAuth?.wordpressOrigin || resolveWordpressOrigin(defaultBaseUrl);
    const targetOrigin = wordpressOrigin || "*";

    const cleanup = () => {
      window.removeEventListener("message", handleMessage);
      window.clearTimeout(timeoutId);
      refreshPromise = null;
    };

    const handleMessage = (event: MessageEvent) => {
      if (wordpressOrigin && event.origin !== wordpressOrigin) {
        return;
      }

      if (event.data?.type !== "kobutsu-ledger-shell-auth") {
        return;
      }

      const payload = event.data.payload || {};
      setWordpressShellAuth(payload);
      cleanup();
      resolve(payload);
    };

    const timeoutId = window.setTimeout(() => {
      cleanup();
      resolve(wordpressShellAuth);
    }, timeoutMs);

    window.addEventListener("message", handleMessage);
    window.parent.postMessage({ type: "kobutsu-ledger-auth-request" }, targetOrigin);
  });

  return refreshPromise;
}

export async function fetchWithWordpressNonceRetry(
  input: RequestInfo | URL,
  init: RequestInit = {},
) {
  const response = await fetch(input, init);

  if (response.status !== 401) {
    return response;
  }

  await refreshWordpressShellAuth();

  return fetch(input, {
    ...init,
    headers: createRetryHeaders(init.headers),
  });
}

export function resolveWordpressOrigin(defaultBaseUrl: string) {
  if (wordpressShellAuth?.wordpressOrigin) {
    return wordpressShellAuth.wordpressOrigin;
  }

  try {
    return new URL(defaultBaseUrl).origin;
  } catch {
    return "";
  }
}
