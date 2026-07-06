import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

import { SupportTicket, UseTicketReturn } from '../../types/store-support';

export const useTicket = ( ticketId: string ): UseTicketReturn => {
    const [ ticket, setTicket ] = useState< SupportTicket | null >( null );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ isNotFound, setIsNotFound ] = useState( false );
    const [ isNotPermitted, setIsNotPermitted ] = useState( false );

    const fetchTicket = async () => {
        setIsLoading( true );

        try {
            const requestData = await apiFetch< SupportTicket >( {
                path: `/dokan/v1/vendor/support-tickets/${ ticketId }`,
                method: 'GET',
            } );
            setTicket( requestData );
        } catch ( err ) {
            if ( err.code === 'dokan_rest_ticket_not_found' ) {
                setIsNotFound( true );
            } else if ( err.code === 'dokan_rest_cannot_view' ) {
                setIsNotPermitted( true );
            }
            setTicket( null );
        } finally {
            setIsLoading( false );
        }
    };

    return {
        ticket,
        setTicket,
        isLoading,
        fetchTicket,
        isNotFound,
        isNotPermitted,
    };
};
