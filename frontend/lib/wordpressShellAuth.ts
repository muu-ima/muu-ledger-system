type WordpressShellAuthPayload = {
  restBaseUrl?: string;
  restNonce?: string;
  wordpressOrigin?: string;
  canWrite?: boolean;
};

let wordpressShellAuth: WordpressShellAuthPayload | null = null;

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
    headers["X-WP-Nonce"] = nonce;
  }

  return headers;
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
