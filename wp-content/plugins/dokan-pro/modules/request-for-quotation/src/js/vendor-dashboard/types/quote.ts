export type QuoteStatus =
    | 'all'
    | 'pending'
    | 'approve'
    | 'expired'
    | 'updated'
    | 'accepted'
    | 'reject'
    | 'converted'
    | 'cancel'
    | 'trash';

export interface Quote {
    id: number;
    title: string;
    status: QuoteStatus;
    created_at: string;
    customer_name: string;
    store_name: string;
    order_url: string;
    expiry_date: number;
    expiry_display: string;
}


export interface QuoteStatusCount {
    key: QuoteStatus;
    label: string;
    count: number;
}

export interface FilterState {
    page: number;
    per_page: number;
    status: QuoteStatus;
    search: string;
}

export type QuoteListProps = Record< string, never >;

export type ApiFetchResponse = {
    json: () => Quote[];
    headers: Response[ 'headers' ] & {
        get: (
            key:
                | 'X-WP-Total'
                | 'X-WP-TotalPages'
                | 'X-Status-All'
                | 'X-Status-Pending'
                | 'X-Status-Approved'
                | 'X-Status-Expired'
                | 'X-Status-Updated'
                | 'X-Status-Accepted'
                | 'X-Status-Rejected'
                | 'X-Status-Converted'
                | 'X-Status-Cancelled'
                | 'X-Status-Trash'
        ) => string | null;
    };
};
