import {
    Order,
    LineItem as OrderLineItem,
} from '../../../src/Definitions/Order';
import { SubscriptionNote, SubscriptionNotes } from './hooks/useNotes';

export interface LineItem extends OrderLineItem {
    parent_name: string | null;
}

export interface DateType {
    internal_date_key: string;
    date_label: string;
    can_date_be_updated: boolean;
    get_date_to_display: string;
    date_site: string;
    date_key: string;
}

export interface PeriodIntervalStrings {
    [ key: string ]: string;
}

export interface PeriodStrings {
    day: string;
    week: string;
    month: string;
    year: string;
}

export interface SubscriptionSettings {
    can_date_be_updated_next_payment: boolean;
    period_interval_strings: PeriodIntervalStrings;
    period_strings: PeriodStrings;
    date_types: DateType[];
}

export interface DokanSubscription extends Order {
    payment_url: string;
    is_editable: boolean;
    needs_payment: boolean;
    needs_processing: boolean;
    billing_period: string;
    billing_interval: string;
    start_date: string;
    start_date_gmt: string;
    trial_end_date: string;
    trial_end_date_gmt: string;
    next_payment_date: string;
    next_payment_date_gmt: string;
    last_payment_date: string;
    last_payment_date_gmt: string;
    cancelled_date: string;
    cancelled_date_gmt: string;
    end_date: string;
    end_date_gmt: string;
    resubscribed_from: string;
    resubscribed_subscription: string;
    removed_line_items: any[];
    settings: SubscriptionSettings;
    recurring_string: string;
}

export interface ApiResponse {
    body: any; // Replace 'any' with your actual response type
}

export interface FetchError {
    message: string;
    code?: string;
}

// API Parameter Types
export interface SubscriptionOrdersQueryArgs {
    context?: 'view' | 'edit';
    page?: number;
    per_page?: number;
    search?: string;
    after?: string;
    before?: string;
    modified_after?: string;
    modified_before?: string;
    dates_are_gmt?: boolean;
    exclude?: number[];
    include?: number[];
    offset?: number;
    order?: 'asc' | 'desc';
    orderby?: 'date' | 'id' | 'include' | 'title' | 'slug' | 'modified';
    parent?: number[];
    parent_exclude?: number[];
    status?:
        | 'any'
        | 'trash'
        | 'pending'
        | 'active'
        | 'on-hold'
        | 'cancelled'
        | 'switched'
        | 'expired'
        | 'pending-cancel';
    customer?: number;
    product?: number;
    dp?: number;
    order_item_display_meta?: boolean;
    include_meta?: string[];
    exclude_meta?: string[];
}

// State interface
export interface SubscriptionOrdersState {
    orders: Order[];
    isLoading: boolean;
    error: Error | null;
    totalItems: number;
    totalPages: number;
    status: string | number;
}

export interface CombinedSubscriptionData {
    subscription: DokanSubscription | null;
    orders: Order[];
    ordersStatus: string | number;
    statuses: SubscriptionStatuses | null;
    isLoading: boolean;
    error: Error | null;
    totalOrders: number;
    totalPages: number;
    refresh: () => void;
    downloadableProducts: DownloadPermissionsResponse;
    downloadableProductsError: Error | null;
    refreshDownloadsProducts: () => void;
    subscriptionNotes: SubscriptionNotes;
    subscriptionNotesLoading: boolean;
    subscriptionNotesError: Error | null;
    refreshNotes: () => void;
    deleteSubscriptionNotes: (
        note: SubscriptionNote
    ) => Promise< ApiResponse >;
    setNotes: ( notes: SubscriptionNotes ) => any;
    createSubscriptionNote: (
        payload: Record< any, any >
    ) => Promise< ApiResponse >;
    refreshSub: () => void;
    setSubscription: ( data: DokanSubscription ) => void;
    getPermittedStatuses: (
        currentStatus: string
    ) => SubscriptionStatuses | null;
}

// Types for subscription statuses
export interface SubscriptionStatuses {
    'wc-pending': string;
    'wc-active': string;
    'wc-on-hold': string;
    'wc-cancelled': string;
    'wc-switched': string;
    'wc-expired': string;
    'wc-pending-cancel': string;
    [ key: string ]: string; // For any additional statuses
}

export interface SubscriptionStatusHookReturn {
    statuses: SubscriptionStatuses | null;
    isLoading: boolean;
    error: Error | null;
    refresh: () => void;
    getPermittedStatuses: (
        currentStatus: string
    ) => SubscriptionStatuses | null;
}

// Types for the order relation hook
export type OrderType = 'renewal' | 'parent' | 'resubscribe' | string;

export interface WCSubscription {
    id: number;
    order_type: string;
    // Flag to identify WC_Subscription instances
    is_wc_subscription: true;
}

export type SubscriptionOrOrder = WCSubscription | Order;

export interface DownloadPermission {
    permission_id: string;
    download_id: string;
    product_id: string;
    order_id: string;
    order_key: string;
    user_email: string;
    user_id: string;
    downloads_remaining: string; // Empty string for unlimited
    access_granted: string; // ISO 8601 date string
    access_expires: string | null;
    download_count: string; // String representation of number
    product: {
        id: number;
        name: string;
        slug: string;
        link: string;
    };
    file_data: {
        id: number;
        name: string;
        file: string;
        enabled: boolean;
        file_title: string;
    };
}

export type DownloadPermissionsResponse = DownloadPermission[];
