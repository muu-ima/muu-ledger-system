import {
  categories,
  supplierOptions,
} from "@/app/components/ledger-workspace/ledgerWorkspaceConfig";

export type SidebarCheckboxSection = {
  options: readonly string[];
  title: string;
};

export type SidebarInputConfig = {
  placeholder: string;
  type?: "controlled" | "static";
};

export const sidebarCheckboxSections: SidebarCheckboxSection[] = [
  {
    title: "商品カテゴリ",
    options: categories,
  },
  {
    title: "仕入先",
    options: supplierOptions,
  },
];

export const sidebarBasicInputs: SidebarInputConfig[] = [
  {
    placeholder: "SKU / 商品名 / 仕入先",
    type: "controlled",
  },
  {
    placeholder: "注文番号",
  },
  {
    placeholder: "buyer ID",
  },
];
