export interface ProductFilterState {
    page: number;
    per_page: number;
    status: string;
    search: string;
    category?: number | '';
    type?: string;
    year_month?: string;
    in_stock?: boolean;
    product_brand?: number | '';
    filter_by_other?: string;
    [ key: string ]: unknown;
}
