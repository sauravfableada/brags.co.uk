export interface OrderAction {
    /** URL for performing the action */
    url: string;

    /** Display name of the action */
    name: string;

    /** Accessibility label for the action */
    'aria-label': string;
}

export interface OrderActions {
    /** Action to view order details */
    view: OrderAction;

    /** Action to pay for the order (available on pending/failed orders) */
    pay?: OrderAction;

    /** Action to cancel the order (available on pending/failed orders) */
    cancel?: OrderAction;
}

export interface SubscriptionOrder {
    /** Unique identifier for the order */
    id: number;

    /** Current status of the order */
    status: string;

    /** Total amount of the order */
    total: string;

    /** Date when order was created */
    date_created: string;

    /** Date when order was created in GMT */
    date_created_gmt: string;

    /** Available actions for the order */
    actions: OrderActions;
}

export type SubscriptionOrders = SubscriptionOrder[];
