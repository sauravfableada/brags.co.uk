<?php

namespace WeDevs\DokanPro\Modules\RequestForQuotation\Model;

/**
 * Quotation Status Class.
 *
 * @since 3.12.3
 */
class Quote {

    public const STATUS_TRASH = 'trash';
    public const STATUS_CANCEL = 'cancel';
    public const STATUS_REJECT = 'reject';
    public const STATUS_UPDATE = 'updated';
    public const STATUS_ACCEPT = 'accepted';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approve';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_DELETE = 'delete';
    public const STATUS_RESTORE = 'restore';

    /**
     * Get the quote statuses.
     *
     * @since 3.12.3
     *
     * This function returns the different statuses that a quote can have,
     * filtered through the 'dokan_get_quote_statuses' filter.
     *
     * @return array The array of quote statuses.
     */
    public static function get_quote_statuses() {
        return apply_filters(
            'dokan_get_quote_statuses',
            [
                static::STATUS_PENDING   => esc_html__( 'Pending', 'dokan' ),
                static::STATUS_APPROVED  => esc_html__( 'Approved', 'dokan' ),
                static::STATUS_EXPIRED   => esc_html__( 'Expired', 'dokan' ),
                static::STATUS_UPDATE    => esc_html__( 'Updated', 'dokan' ),
                static::STATUS_ACCEPT    => esc_html__( 'Accepted', 'dokan' ),
                static::STATUS_REJECT    => esc_html__( 'Rejected', 'dokan' ),
                static::STATUS_CONVERTED => esc_html__( 'Converted', 'dokan' ),
                static::STATUS_CANCEL    => esc_html__( 'Cancelled', 'dokan' ),
                static::STATUS_TRASH     => esc_html__( 'Trash', 'dokan' ),
            ]
        );
    }

    /**
     * Get all status keys for the request for quote.
     *
     * This method returns an array of all possible status keys, allowing filters
     * to modify the list of statuses if needed.
     *
     * @since 3.12.3
     *
     * @return array The list of all status keys.
     */
    public static function get_status_keys() {
        return apply_filters(
            'dokan_request_for_quote_all_statuses',
            array_keys( static::get_quote_statuses() )
        );
    }

    /**
     * Get the label for a given status.
     *
     * @since 3.12.3
     *
     * @param string $status
     *
     * @return string
     */
    public static function get_status_label( string $status ): string {
        $quote_statuses = self::get_quote_statuses();
        $status_label   = $quote_statuses[ $status ] ?? apply_filters( 'dokan_request_for_quote_default_status_label', '' );

        return apply_filters( 'dokan_pro_request_for_quote_status_label', $status_label, $status );
    }
}
