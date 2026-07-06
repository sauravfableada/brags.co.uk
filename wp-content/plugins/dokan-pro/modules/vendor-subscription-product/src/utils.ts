import { __ } from '@wordpress/i18n';
import '../../../src/definitions/window-types';

export const getStatusTranslated = ( status: string ): string => {
    switch ( status ) {
        case 'completed':
        case 'wc-completed':
            return __( 'Completed', 'dokan' );

        case 'active':
        case 'wc-active':
            return __( 'Active', 'dokan' );

        case 'expired':
        case 'wc-expired':
            return __( 'Expired', 'dokan' );

        case 'pending':
        case 'wc-pending':
            return __( 'Pending Payment', 'dokan' );

        case 'on-hold':
        case 'wc-on-hold':
            return __( 'On-hold', 'dokan' );

        case 'processing':
        case 'wc-processing':
            return __( 'Processing', 'dokan' );

        case 'refunded':
        case 'wc-refunded':
            return __( 'Refunded', 'dokan' );

        case 'cancelled':
        case 'wc-cancelled':
            return __( 'Cancelled', 'dokan' );

        case 'failed':
        case 'wc-failed':
            return __( 'Failed', 'dokan' );

        case 'pending-cancel':
        case 'wc-pending-cancel':
            return __( 'Pending Cancellation', 'dokan' );

        default:
            // @ts-ignore
            return window.wp.hooks.applyFilters(
                'dokan_vps_get_order_status_translated',
                '',
                status
            );
    }
};

/**
 * Get bootstrap label class based on order status
 *
 * @param {string} status
 * @return string
 */
export const getStatusClass = ( status: string ): string => {
    switch ( status ) {
        case 'completed':
        case 'wc-completed':
        case 'active':
        case 'wc-active':
            return 'success';

        case 'pending-cancel':
        case 'wc-pending-cancel':
        case 'pending':
        case 'wc-expired':
        case 'expired':
        case 'wc-failed':
        case 'failed':
        case 'wc-pending':
            return 'danger';

        case 'on-hold':
        case 'wc-on-hold':
            return 'warning';

        case 'processing':
        case 'wc-processing':
            return 'info';

        case 'refunded':
        case 'wc-cancelled':
        case 'cancelled':
        case 'wc-refunded':
            return 'info';

        default:
            // @ts-ignore
            return window.wp.hooks.applyFilters(
                'dokan_get_order_status_class',
                '',
                status
            );
    }
};
