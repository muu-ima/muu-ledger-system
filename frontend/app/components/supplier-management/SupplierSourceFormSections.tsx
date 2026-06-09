"use client";

import type { ReactNode } from "react";
import type { SupplierSource } from "@/types/supplier";

type SupplierSourceFormSectionsProps = {
  form: SupplierSource;
  onFieldChange: (field: keyof SupplierSource, value: string) => void;
};

type SupplierSourceFieldProps = {
  field: keyof SupplierSource;
  form: SupplierSource;
  label: string;
  onFieldChange: (field: keyof SupplierSource, value: string) => void;
  wide?: boolean;
};

type SupplierSourceTextareaFieldProps = SupplierSourceFieldProps;

type SupplierSourceSectionProps = {
  children: ReactNode;
  title: string;
};

function SupplierSourceSection({
  children,
  title,
}: SupplierSourceSectionProps) {
  return (
    <fieldset className="formSection">
      <legend>{title}</legend>
      <div className="formSectionGrid">{children}</div>
    </fieldset>
  );
}

function SupplierSourceInputField({
  field,
  form,
  label,
  onFieldChange,
  wide = false,
}: SupplierSourceFieldProps) {
  return (
    <label className={wide ? "wideField" : undefined}>
      <span>{label}</span>
      <input
        value={form[field]}
        onChange={(event) => onFieldChange(field, event.target.value)}
      />
    </label>
  );
}

function SupplierSourceTextareaField({
  field,
  form,
  label,
  onFieldChange,
  wide = false,
}: SupplierSourceTextareaFieldProps) {
  return (
    <label className={wide ? "wideField" : undefined}>
      <span>{label}</span>
      <textarea
        value={form[field]}
        onChange={(event) => onFieldChange(field, event.target.value)}
      />
    </label>
  );
}

export function SupplierSourceFormSections({
  form,
  onFieldChange,
}: SupplierSourceFormSectionsProps) {
  return (
    <>
      <SupplierSourceSection title="必須入力">
        <SupplierSourceInputField
          field="sku"
          form={form}
          label="SKU"
          onFieldChange={onFieldChange}
        />
        <SupplierSourceInputField
          field="orderNo"
          form={form}
          label="Order no."
          onFieldChange={onFieldChange}
        />
        <SupplierSourceInputField
          field="acquiredAt"
          form={form}
          label="仕入日"
          onFieldChange={onFieldChange}
        />
        <SupplierSourceInputField
          field="supplier"
          form={form}
          label="仕入れ先"
          onFieldChange={onFieldChange}
        />
        <SupplierSourceInputField
          field="purchasePrice"
          form={form}
          label="仕入れ"
          onFieldChange={onFieldChange}
        />
        <SupplierSourceInputField
          field="itemName"
          form={form}
          label="商品名"
          onFieldChange={onFieldChange}
          wide
        />
      </SupplierSourceSection>

      <SupplierSourceSection title="よく使う入力">
        <SupplierSourceInputField
          field="account"
          form={form}
          label="アカウント"
          onFieldChange={onFieldChange}
        />
        <SupplierSourceInputField
          field="soldAt"
          form={form}
          label="販売日"
          onFieldChange={onFieldChange}
        />
        <SupplierSourceInputField
          field="country"
          form={form}
          label="国"
          onFieldChange={onFieldChange}
        />
        <SupplierSourceInputField
          field="saleAmount"
          form={form}
          label="販売額"
          onFieldChange={onFieldChange}
        />
        <SupplierSourceInputField
          field="shippingCost"
          form={form}
          label="送料"
          onFieldChange={onFieldChange}
        />
        <SupplierSourceInputField
          field="shippingSite"
          form={form}
          label="発送サイト"
          onFieldChange={onFieldChange}
        />
        <SupplierSourceInputField
          field="packer"
          form={form}
          label="梱包者"
          onFieldChange={onFieldChange}
        />
        <SupplierSourceTextareaField
          field="note"
          form={form}
          label="備考"
          onFieldChange={onFieldChange}
          wide
        />
      </SupplierSourceSection>

      <SupplierSourceSection title="詳細入力">
        <SupplierSourceInputField
          field="mag"
          form={form}
          label="MAG"
          onFieldChange={onFieldChange}
        />
        <SupplierSourceInputField
          field="points"
          form={form}
          label="ポイント加算"
          onFieldChange={onFieldChange}
        />
        <SupplierSourceInputField
          field="actualWeight"
          form={form}
          label="実重g"
          onFieldChange={onFieldChange}
        />
        <SupplierSourceInputField
          field="dimensionalWeight"
          form={form}
          label="体積重g"
          onFieldChange={onFieldChange}
        />
        <SupplierSourceInputField
          field="length"
          form={form}
          label="縦cm"
          onFieldChange={onFieldChange}
        />
        <SupplierSourceInputField
          field="width"
          form={form}
          label="横cm"
          onFieldChange={onFieldChange}
        />
        <SupplierSourceInputField
          field="height"
          form={form}
          label="高さcm"
          onFieldChange={onFieldChange}
        />
        <SupplierSourceInputField
          field="firstMailAt"
          form={form}
          label="初回メール"
          onFieldChange={onFieldChange}
        />
        <SupplierSourceInputField
          field="receiptPrintedAt"
          form={form}
          label="領収書印刷日"
          onFieldChange={onFieldChange}
        />
      </SupplierSourceSection>
    </>
  );
}
