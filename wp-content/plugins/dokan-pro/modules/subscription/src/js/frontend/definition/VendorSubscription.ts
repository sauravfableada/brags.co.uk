export interface VendorSubscription {
    /** Unique identifier for the subscription record */
    id: number;

    /** Name of the store */
    store_name: string;

    /** Link to the order, if available */
    order_link: string | null;

    /** Unique identifier for the order */
    order_id: string;

    /** Unique identifier for the subscription */
    subscription_id: string;

    /** Title of the subscription plan */
    subscription_title: string;

    /** Indicates if there's a pending subscription update */
    has_pending_subscription: boolean;

    /** Indicates if the store can post new products */
    can_post_product: boolean;

    /** Maximum number of products allowed under this subscription */
    no_of_allowed_products: string;

    /** Number of days the subscription package is valid for */
    pack_validity_days: string;

    /** Indicates if the subscription is in trial period */
    is_on_trial: boolean;

    /** Duration of the trial period */
    trial_range: string;

    /** Unit of time for trial period (e.g., 'day') */
    trial_period_type: string;

    /** End date of trial period, if applicable */
    subscription_trial_until: string | null;

    /** Subscription start date */
    start_date: string;

    /** Subscription end date */
    end_date: string;

    /** Current date at the time of response */
    current_date: string;

    /** Indicates if the subscription is active */
    status: boolean;

    /** Indicates if the subscription automatically renews */
    is_recurring: boolean;

    /** Duration of the recurring period */
    recurring_interval: string;

    /** Unit of time for recurring period (e.g., 'day') */
    recurring_period_type: string;

    /** Indicates if there's an active but cancelled subscription */
    has_active_cancelled_sub: boolean;
}
