import type { Metadata } from "next";
import { WordpressShellAuthBridge } from "@/app/components/WordpressShellAuthBridge";
import "./globals.css";

export const metadata: Metadata = {
  title: "古物台帳",
  description: "Next.js + WordPress の古物台帳システム",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="ja">
      <body>
        <WordpressShellAuthBridge />
        {children}
      </body>
    </html>
  );
}
