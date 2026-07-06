export interface VendorSubscription {
    id: number;
    store_name: string;
    order_link: string | null;
    order_id: string;
    subscription_id: string;
    subscription_title: string;
    has_pending_subscription: boolean;
    can_post_product: boolean;
    no_of_allowed_products: string;
    pack_validity_days: string;
    is_on_trial: boolean;
    trial_range: string;
    trial_period_type: string;
    subscription_trial_until: string | null;
    start_date: string;
    end_date: string;
    current_date: string; // Date string e.g., "2025-06-25"
    status: boolean;
    is_recurring: boolean;
    recurring_interval: number;
    recurring_period_type: string;
    has_active_cancelled_sub: boolean;
}
