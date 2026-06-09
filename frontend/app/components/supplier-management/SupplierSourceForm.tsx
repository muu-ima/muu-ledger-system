"use client";

import type { SupplierSource } from "@/types/supplier";

type SupplierSourceFormProps = {
  form: SupplierSource;
  submitStatus: string;
  onFieldChange: (field: keyof SupplierSource, value: string) => void;
  onReflect: () => void;
};

export function SupplierSourceForm({
  form,
  submitStatus,
  onFieldChange,
  onReflect,
}: SupplierSourceFormProps) {
  return (
    <>
      <fieldset className="formSection">
        <legend>必須入力</legend>
        <div className="formSectionGrid">
          <label>
            <span>SKU</span>
            <input value={form.sku} onChange={(event) => onFieldChange("sku", event.target.value)} />
          </label>
          <label>
            <span>Order no.</span>
            <input
              value={form.orderNo}
              onChange={(event) => onFieldChange("orderNo", event.target.value)}
            />
          </label>
          <label>
            <span>仕入日</span>
            <input
              value={form.acquiredAt}
              onChange={(event) => onFieldChange("acquiredAt", event.target.value)}
            />
          </label>
          <label>
            <span>仕入れ先</span>
            <input
              value={form.supplier}
              onChange={(event) => onFieldChange("supplier", event.target.value)}
            />
          </label>
          <label>
            <span>仕入れ</span>
            <input
              value={form.purchasePrice}
              onChange={(event) => onFieldChange("purchasePrice", event.target.value)}
            />
          </label>
          <label className="wideField">
            <span>商品名</span>
            <input
              value={form.itemName}
              onChange={(event) => onFieldChange("itemName", event.target.value)}
            />
          </label>
        </div>
      </fieldset>

      <fieldset className="formSection">
        <legend>よく使う入力</legend>
        <div className="formSectionGrid">
          <label>
            <span>アカウント</span>
            <input
              value={form.account}
              onChange={(event) => onFieldChange("account", event.target.value)}
            />
          </label>
          <label>
            <span>販売日</span>
            <input value={form.soldAt} onChange={(event) => onFieldChange("soldAt", event.target.value)} />
          </label>
          <label>
            <span>国</span>
            <input
              value={form.country}
              onChange={(event) => onFieldChange("country", event.target.value)}
            />
          </label>
          <label>
            <span>販売額</span>
            <input
              value={form.saleAmount}
              onChange={(event) => onFieldChange("saleAmount", event.target.value)}
            />
          </label>
          <label>
            <span>送料</span>
            <input
              value={form.shippingCost}
              onChange={(event) => onFieldChange("shippingCost", event.target.value)}
            />
          </label>
          <label>
            <span>発送サイト</span>
            <input
              value={form.shippingSite}
              onChange={(event) => onFieldChange("shippingSite", event.target.value)}
            />
          </label>
          <label>
            <span>梱包者</span>
            <input value={form.packer} onChange={(event) => onFieldChange("packer", event.target.value)} />
          </label>
          <label className="wideField">
            <span>備考</span>
            <textarea value={form.note} onChange={(event) => onFieldChange("note", event.target.value)} />
          </label>
        </div>
      </fieldset>

      <fieldset className="formSection">
        <legend>詳細入力</legend>
        <div className="formSectionGrid">
          <label>
            <span>MAG</span>
            <input value={form.mag} onChange={(event) => onFieldChange("mag", event.target.value)} />
          </label>
          <label>
            <span>ポイント加算</span>
            <input value={form.points} onChange={(event) => onFieldChange("points", event.target.value)} />
          </label>
          <label>
            <span>実重g</span>
            <input
              value={form.actualWeight}
              onChange={(event) => onFieldChange("actualWeight", event.target.value)}
            />
          </label>
          <label>
            <span>体積重g</span>
            <input
              value={form.dimensionalWeight}
              onChange={(event) => onFieldChange("dimensionalWeight", event.target.value)}
            />
          </label>
          <label>
            <span>縦cm</span>
            <input value={form.length} onChange={(event) => onFieldChange("length", event.target.value)} />
          </label>
          <label>
            <span>横cm</span>
            <input value={form.width} onChange={(event) => onFieldChange("width", event.target.value)} />
          </label>
          <label>
            <span>高さcm</span>
            <input value={form.height} onChange={(event) => onFieldChange("height", event.target.value)} />
          </label>
          <label>
            <span>初回メール</span>
            <input
              value={form.firstMailAt}
              onChange={(event) => onFieldChange("firstMailAt", event.target.value)}
            />
          </label>
          <label>
            <span>領収書印刷日</span>
            <input
              value={form.receiptPrintedAt}
              onChange={(event) => onFieldChange("receiptPrintedAt", event.target.value)}
            />
          </label>
        </div>
      </fieldset>

      <div className="formActions">
        <button type="button" onClick={onReflect}>
          仕入元データへ反映
        </button>
        <button type="submit">保存</button>
        <span>{submitStatus}</span>
      </div>
    </>
  );
}
