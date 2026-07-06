import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

import {
    SupportStatus,
    SupportTicketStatus,
    SupportTicketStats,
    UseStatusFiltersReturn,
} from '../../types/store-support';

export const useStatusFilters = (): UseStatusFiltersReturn => {
    const [ isLoading, setIsLoading ] = useState( true );

    const defaultStatuses: SupportStatus[] = [
        { key: 'all', label: __( 'All', 'dokan' ), count: 0 },
        { key: 'open', label: __( 'Open', 'dokan' ), count: 0 },
        { key: 'closed', label: __( 'Closed', 'dokan' ), count: 0 },
    ];
    const [ statusCounts, setStatusCounts ] = useState< SupportStatus[] >( defaultStatuses );

    const fetchStatusCounts = async () => {
        setIsLoading( true );

        try {
            // Use the stats endpoint from the vendor support tickets controller
            const data = await apiFetch< SupportTicketStats >( {
                path: '/dokan/v1/vendor/support-tickets/stats',
            } );

            // Transform the API response into our status filter format
            const transformedData: SupportStatus[] = [
                {
                    key: 'all',
                    label: __( 'All', 'dokan' ),
                    count: data.total || 0,
                },
                {
                    key: 'open',
                    label: __( 'Open', 'dokan' ),
                    count: data.open || 0,
                },
                {
                    key: 'closed',
                    label: __( 'Closed', 'dokan' ),
                    count: data.closed || 0,
                },
            ];

            setStatusCounts( transformedData );
        } catch ( err ) {
            setStatusCounts( defaultStatuses );
        } finally {
            setIsLoading( false );
        }
    };

    const getCurrentStatusLabel = (
        statusName: SupportTicketStatus | string
    ): string => {
        const status = statusCounts.find( ( s ) => s.key === statusName );
        return status ? status.label : __( 'Unknown', 'dokan' );
    };

    return {
        statusCounts,
        isLoading,
        fetchStatusCounts,
        getCurrentStatusLabel,
    };
};
