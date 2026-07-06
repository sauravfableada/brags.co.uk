import { __ } from '@wordpress/i18n';

import { QuoteStatus, QuoteStatusCount } from '../types/quote';

/**
 * Returns the default status filter items with zero counts.
 * Used as initial state before the first API response is received.
 */
export const getDefaultStatusCounts = (): QuoteStatusCount[] => [
    { key: 'all', label: __( 'All', 'dokan' ), count: 0 },
    { key: 'pending', label: __( 'Pending', 'dokan' ), count: 0 },
    { key: 'approve', label: __( 'Approved', 'dokan' ), count: 0 },
    { key: 'updated', label: __( 'Updated', 'dokan' ), count: 0 },
    { key: 'accepted', label: __( 'Accepted', 'dokan' ), count: 0 },
    { key: 'expired', label: __( 'Expired', 'dokan' ), count: 0 },
    { key: 'reject', label: __( 'Rejected', 'dokan' ), count: 0 },
    { key: 'cancel', label: __( 'Cancelled', 'dokan' ), count: 0 },
    { key: 'converted', label: __( 'Converted', 'dokan' ), count: 0 },
    { key: 'trash', label: __( 'Trash', 'dokan' ), count: 0 },
];

/**
 * Returns the label for a given status key.
 */
export const getStatusLabel = ( status: QuoteStatus ): string => {
    const item = getDefaultStatusCounts().find( ( s ) => s.key === status );
    return item ? item.label : status;
};
