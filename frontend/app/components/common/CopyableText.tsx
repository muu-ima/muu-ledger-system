"use client";

import { useEffect, useState, type MouseEvent } from "react";

type CopyableTextProps = {
  className?: string;
  label?: string;
  showValue?: boolean;
  value: string;
};

async function copyText(value: string) {
  if (navigator.clipboard?.writeText) {
    await navigator.clipboard.writeText(value);
    return;
  }

  const textarea = document.createElement("textarea");
  textarea.value = value;
  textarea.style.position = "fixed";
  textarea.style.opacity = "0";
  document.body.appendChild(textarea);
  textarea.select();
  document.execCommand("copy");
  document.body.removeChild(textarea);
}

export function CopyableText({
  className = "",
  label = "コピー",
  showValue = true,
  value,
}: CopyableTextProps) {
  const [copied, setCopied] = useState(false);

  useEffect(() => {
    if (!copied) return;

    const timeoutId = window.setTimeout(() => setCopied(false), 900);
    return () => window.clearTimeout(timeoutId);
  }, [copied]);

  const handleCopy = async (event: MouseEvent<HTMLButtonElement>) => {
    event.preventDefault();
    event.stopPropagation();
    if (!value) return;

    await copyText(value);
    setCopied(true);
  };

  return (
    <span className={`copyableText ${className}`.trim()}>
      {showValue ? <span className="copyableTextValue">{value}</span> : null}
      <button
        type="button"
        className="copyIconButton"
        aria-label={`${label}: ${value}`}
        title={copied ? "コピー済み" : label}
        onClick={handleCopy}
        onMouseDown={(event) => event.stopPropagation()}
        disabled={!value}
      >
        {copied ? "✓" : "⧉"}
      </button>
    </span>
  );
}
