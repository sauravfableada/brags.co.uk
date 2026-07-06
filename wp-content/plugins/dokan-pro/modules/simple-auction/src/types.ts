export interface ProductImage {
    id: number;
    src: string;
    name: string;
    alt: string;
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

export interface AuctionProduct {
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
    virtual: boolean;
    downloadable: boolean;
    categories: ProductCategory[];
    images: ProductImage[];
    date_created: string;
    date_modified: string;
    permalink: string;
    page_view: number;
    edit_url: string;
    advertisement: ProductAdvertisement | null;
    // Auction-specific — from WC_Product_Auction getters.
    auction_is_closed: boolean;
    auction_fail_reason: '' | '1' | '2';
    auction_is_payed: boolean;
    auction_closed_val: string;
    auction_start_date: string;
    auction_end_date: string;
    auction_current_bid?: string;
    auction_bid_count?: number;
}

export type AuctionStatus = 'all' | 'publish' | 'draft' | 'pending' | 'reject';

export interface AuctionFilterState {
    page: number;
    per_page: number;
    status: AuctionStatus;
    search: string;
    start_date: string;
    end_date: string;
}

export interface AuctionStatusCount {
    value: string;
    label: string;
    count: number;
}

export interface SubscriptionRemaining {
    remaining_products: true | number;
    can_post_product: boolean;
}

export interface AuctionSummary {
    post_counts: Record< string, number >;
    subscription_remaining?: SubscriptionRemaining;
}

export interface AuctionActivity {
    post_id: number;
    post_title: string;
    user_nicename: string;
    user_email: string;
    bid: string;
    date: string;
    proxy: boolean;
}

export interface AuctionActivityFilterState {
    page: number;
    per_page: number;
    search: string;
    start_date: string;
    end_date: string;
}

export interface AuctionListingConfig {
    can_add_product?: boolean;
    can_duplicate_product?: boolean;
    new_product_url?: string;
    activity_url?: string;
    auction_url?: string;
    can_see_customer_info?: boolean;
    subscription?: {
        remaining_products: true | number;
        can_post_product: boolean;
        subscription_url?: string;
    };
}
