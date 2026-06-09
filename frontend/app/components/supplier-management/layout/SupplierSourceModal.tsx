"use client";

import type { FormEvent, ReactNode } from "react";

type SupplierSourceModalProps = {
  isOpen: boolean;
  onClose: () => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
  children: ReactNode;
};

export function SupplierSourceModal({
  isOpen,
  onClose,
  onSubmit,
  children,
}: SupplierSourceModalProps) {
  if (!isOpen) return null;

  return (
    <div className="modalOverlay" role="presentation">
      <section
        className="supplierModal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="supplier-modal-title"
      >
        <div className="modalHeader">
          <div>
            <h2 id="supplier-modal-title">新規仕入れ</h2>
            <span>仕入れ管理テーブルへ保存</span>
          </div>
          <button
            type="button"
            className="modalCloseButton"
            onClick={onClose}
            aria-label="閉じる"
          >
            ×
          </button>
        </div>
        <form className="supplierForm" onSubmit={onSubmit}>
          {children}
        </form>
      </section>
    </div>
  );
}
