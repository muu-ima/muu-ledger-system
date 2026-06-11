"use client";

import { useEffect } from "react";
import {
  resolveWordpressOrigin,
  setWordpressShellAuth,
} from "@/lib/wordpressShellAuth";

export function WordpressShellAuthBridge() {
  useEffect(() => {
    const defaultBaseUrl = process.env.NEXT_PUBLIC_WORDPRESS_URL || "";
    const wordpressOrigin = resolveWordpressOrigin(defaultBaseUrl);

    function handleMessage(event: MessageEvent) {
      if (wordpressOrigin && event.origin !== wordpressOrigin) {
        return;
      }

      if (event.data?.type !== "kobutsu-ledger-shell-auth") {
        return;
      }

      setWordpressShellAuth(event.data.payload || {});
    }

    window.addEventListener("message", handleMessage);

    if (window.parent !== window) {
      window.parent.postMessage(
        { type: "kobutsu-ledger-auth-request" },
        wordpressOrigin || "*",
      );
    }

    return () => {
      window.removeEventListener("message", handleMessage);
    };
  }, []);

  return null;
}
