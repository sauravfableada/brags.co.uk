export type PredefinedOrderStatus =
    | 'wc-processing'
    | 'wc-on-hold'
    | 'wc-completed'
    | 'wc-pending'
    | 'wc-checkout-draft'
    | 'wc-refunded'
    | 'wc-cancelled'
    | 'wc-failed';

export type OrderStatus = PredefinedOrderStatus | ( string & {} );

export interface OrderStatuses {
    vendor_id: number;
    export_statuses: OrderStatus[];
    shipped_status: OrderStatus;
}
