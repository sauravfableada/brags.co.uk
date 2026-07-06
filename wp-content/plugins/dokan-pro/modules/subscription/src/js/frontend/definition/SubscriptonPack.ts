export interface SubscriptionPack {
    /** Unique identifier for the subscription package */
    id: number;

    /** Name of the subscription package */
    title: string;

    /** Current price of the package */
    price: string;

    /** Regular price without any discount */
    regular_price: string;

    /** Sale price if any */
    sale_price: string;

    /** Number of products allowed (-1 for unlimited) */
    no_of_product: string;

    /** Validity period in days */
    pack_validity: string;

    /** Whether gallery restriction is enabled */
    gallery_restriction: 'yes' | 'no';

    /** Number of gallery items allowed if restricted */
    gallery_restriction_count: string;

    /** Whether recurring payment is enabled */
    recurring_payment: 'yes' | 'no';

    /** Interval for recurring payments */
    recurring_period_interval: string;

    /** Type of recurring period */
    recurring_period_type: 'day' | 'month';

    /** Length of recurring period (0 for unlimited) */
    recurring_period_length: string;

    /** Whether trial is allowed */
    allowed_trial: 'yes' | 'no';

    /** Duration of trial period */
    trial_period_range: string;

    /** Type of trial period */
    trial_period_types: 'day' | 'month';

    /** Number of advertisement slots (-1 for unlimited) */
    advertisement_slot_count: string;

    /** Validity period for advertisements (-1 for unlimited) */
    advertisement_validity: string;
}

export type TypeSubscriptionPacks = SubscriptionPack[];
