export interface ProductImage {
    id: number;
    src: string;
    name?: string;
    alt?: string;
}

export interface ProductCategory {
    id: number;
    name: string;
    slug: string;
}

export interface ProductAdvertisement {
    already_advertised: boolean;
    expire_date?: string;
    advertise_url?: string;
}

/**
 * Booking product item — mirrors the dokan-lite ProductItem shape
 * so the shared filter/action hooks work unchanged.
 */
export interface BookingProduct {
    id: number;
    name: string;
    slug: string;
    type: string;
    status: string;
    sku: string;
    price: string;
    regular_price: string;
    sale_price: string;
    price_html: string;
    on_sale: boolean;
    manage_stock: boolean;
    stock_quantity: number | null;
    in_stock: boolean;
    total_sales: number;
    virtual: boolean;
    downloadable: boolean;
    categories: ProductCategory[];
    images: ProductImage[];
    date_created: string;
    date_modified: string;
    permalink: string;
    earning: number | null;
    page_view: number;
    edit_url?: string;
    advertisement?: ProductAdvertisement | null;
}

export type BookingProductStatus = 'all' | 'publish' | 'draft' | 'pending';

export interface BookingProductFilterState {
    page: number;
    per_page: number;
    status: string;
    search: string;
    category?: number | '';
    year_month?: string;
    in_stock?: boolean;
    product_brand?: number | '';
    filter_by_other?: string;
    [ key: string ]: unknown;
}

export interface BookingProductStatusCount {
    value: string;
    label: string;
    count: number;
}

export interface BookingProductMonthOption {
    value: string;
    label: string;
}

export interface SubscriptionRemaining {
    remaining_products: true | number;
    can_post_product: boolean;
}

export interface BookingProductSummary {
    post_counts: Record< string, number >;
    instock_count: number;
    outofstock_count: number;
    months?: BookingProductMonthOption[];
    subscription_remaining?: SubscriptionRemaining;
    low_stock_threshold?: number;
}

export interface Booking {
    id: number;
    status: string;
    product_id: number;
    product_title: string;
    product_edit_url: string;
    resource_id: number;
    resource_title: string;
    customer_name: string;
    customer_email: string;
    persons: number;
    order_id: number;
    order_status: string;
    order_url: string;
    start_date: string;
    end_date: string;
    details_url: string;
}

export interface BookingResource {
    id: number;
    name: string;
    parent_products: {
        id: number;
        title: string;
        url: string;
    }[];
    edit_url: string;
}

export interface CalendarEvent {
    id: number;
    title: string;
    start: string;
    end: string;
    allDay: boolean;
    backgroundColor: string;
    borderColor: string;
    textColor: string;
    url: string;
    info: {
        body: string;
    };
}

export interface CalendarFilterGroup {
    label: string;
    options: {
        value: number;
        label: string;
    }[];
}

export type ViewType = 'products' | 'bookings' | 'calendar' | 'resources';

export interface StatusCount {
    key: string;
    label: string;
    count: number;
}
