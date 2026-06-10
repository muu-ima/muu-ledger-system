"use client";

import { useState } from "react";
import type { ReactNode } from "react";
import type { SupplierSource } from "@/types/supplier";

type SupplierSourceFormSectionsProps = {
  form: SupplierSource;
  onFieldChange: (field: keyof SupplierSource, value: string) => void;
};

type SupplierSourceFieldType = "input" | "textarea" | "checkbox";

type SupplierSourceFieldProps = {
  field: keyof SupplierSource;
  form: SupplierSource;
  label: string;
  onFieldChange: (field: keyof SupplierSource, value: string) => void;
  wide?: boolean;
};

type SupplierSourceTextareaFieldProps = SupplierSourceFieldProps;

type SupplierSourceFieldConfig = {
  field: keyof SupplierSource;
  label: string;
  type?: SupplierSourceFieldType;
  wide?: boolean;
};

type SupplierSourceSectionConfig = {
  fields: SupplierSourceFieldConfig[];
  title: string;
};

type SupplierSourceSectionProps = {
  children: ReactNode;
  title: string;
};

const supplierSourceFormSections: SupplierSourceSectionConfig[] = [
  {
    title: "基本情報",
    fields: [
      { field: "sku", label: "SKU" },
      { field: "account", label: "アカウント" },
      { field: "orderNo", label: "Order no." },
      { field: "soldAt", label: "販売日" },
      { field: "acquiredAt", label: "仕入日" },
      { field: "country", label: "国" },
      { field: "saleAmount", label: "販売額" },
      { field: "purchasedFlag", label: "購入済み", type: "checkbox" },
      { field: "purchasePrice", label: "仕入れ" },
      { field: "shippingCost", label: "国内送料" },
      { field: "points", label: "ポイント加算" },
      { field: "note", label: "その他備考", type: "textarea", wide: true },
      { field: "itemName", label: "商品名", wide: true },
      { field: "supplier", label: "仕入れ先" },
    ],
  },
  {
    title: "発送・梱包",
    fields: [
      { field: "packer", label: "梱包者" },
      { field: "actualWeight", label: "実重g" },
      { field: "dimensionalWeight", label: "体積重g" },
      { field: "length", label: "縦cm" },
      { field: "width", label: "横cm" },
      { field: "height", label: "高さcm" },
      { field: "size", label: "サイズ" },
      { field: "shippingSite", label: "発送サイト" },
      { field: "shippingChatAt", label: "発送チャット" },
      { field: "firstMailAt", label: "初回メール" },
      { field: "receiptPrintedAt", label: "領収書印刷日" },
      { field: "domesticTrackingNo", label: "国内追跡番号" },
      { field: "slsTrackingNo", label: "SLS追跡番号" },
      { field: "yamatoSlipFlag", label: "ヤマト控え有無", type: "checkbox" },
      { field: "balanceCheckedFlag", label: "収支チェック", type: "checkbox" },
    ],
  },
  {
    title: "補助・原票",
    fields: [
      { field: "mag", label: "MAG" },
    ],
  },
];

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

function isIsoDateString(value: string) {
  return /^\d{4}-\d{2}-\d{2}$/.test(value);
}

function SupplierSourceAcquiredAtField({
  form,
  onFieldChange,
}: SupplierSourceFormSectionsProps) {
  const isInStock = form.acquiredAt === "有在庫";
  const dateValue = isIsoDateString(form.acquiredAt) ? form.acquiredAt : "";

  return (
    <label>
      <span>仕入日</span>
      <div className="stackedField">
        <select
          value={isInStock ? "in_stock" : "date"}
          onChange={(event) =>
            onFieldChange(
              "acquiredAt",
              event.target.value === "in_stock" ? "有在庫" : "",
            )
          }
        >
          <option value="date">日付入力</option>
          <option value="in_stock">有在庫</option>
        </select>
        {isInStock ? (
          <input value="有在庫" readOnly />
        ) : (
          <input
            type="date"
            value={dateValue}
            onChange={(event) => onFieldChange("acquiredAt", event.target.value)}
          />
        )}
      </div>
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

function SupplierSourceCheckboxField({
  field,
  form,
  label,
  onFieldChange,
}: SupplierSourceFieldProps) {
  const checked = form[field] === "TRUE";

  return (
    <label className="checkboxField">
      <span>{label}</span>
      <input
        type="checkbox"
        checked={checked}
        onChange={(event) =>
          onFieldChange(field, event.target.checked ? "TRUE" : "FALSE")
        }
      />
    </label>
  );
}

export function SupplierSourceFormSections({
  form,
  onFieldChange,
}: SupplierSourceFormSectionsProps) {
  const [activeSectionTitle, setActiveSectionTitle] = useState(
    supplierSourceFormSections[0]?.title ?? "",
  );
  const activeSection =
    supplierSourceFormSections.find(
      (section) => section.title === activeSectionTitle,
    ) ?? supplierSourceFormSections[0];

  return (
    <>
      <div
        className="tableTabs primaryTabs supplierFormTabs"
        role="tablist"
        aria-label="新規仕入れフォームタブ"
      >
        {supplierSourceFormSections.map((section) => (
          <button
            key={section.title}
            type="button"
            role="tab"
            aria-selected={activeSection.title === section.title}
            className={activeSection.title === section.title ? "active" : ""}
            onClick={() => setActiveSectionTitle(section.title)}
          >
            {section.title}
          </button>
        ))}
      </div>

      <SupplierSourceSection title={activeSection.title}>
        {activeSection.fields.map((field) =>
          field.field === "acquiredAt" ? (
            <SupplierSourceAcquiredAtField
              key={field.field}
              form={form}
              onFieldChange={onFieldChange}
            />
          ) : field.type === "textarea" ? (
            <SupplierSourceTextareaField
              key={field.field}
              field={field.field}
              form={form}
              label={field.label}
              onFieldChange={onFieldChange}
              wide={field.wide}
            />
          ) : field.type === "checkbox" ? (
            <SupplierSourceCheckboxField
              key={field.field}
              field={field.field}
              form={form}
              label={field.label}
              onFieldChange={onFieldChange}
            />
          ) : (
            <SupplierSourceInputField
              key={field.field}
              field={field.field}
              form={form}
              label={field.label}
              onFieldChange={onFieldChange}
              wide={field.wide}
            />
          ),
        )}
      </SupplierSourceSection>
    </>
  );
}
