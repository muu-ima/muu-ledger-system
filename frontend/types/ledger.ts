export type LedgerStatus = "in_stock" | "sold" | "returned" | "disposed";

export type LedgerItem = {
  id: number;
  managementNo: string;
  category: string;
  itemName: string;
  description: string;
  acquiredAt: string;
  acquiredFrom: string;
  sellerIdentification: string;
  purchasePrice: number;
  soldAt: string;
  soldTo: string;
  salePrice: number;
  status: LedgerStatus;
};
