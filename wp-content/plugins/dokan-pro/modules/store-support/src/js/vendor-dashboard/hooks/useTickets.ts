import { useState } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';

import {
    ApiFetchResponse,
    FilterArgs,
    StatusCounts,
    SupportTicket,
    SupportTicketStatus,
    UseTicketsReturn,
} from '../../types/store-support';

export const useTickets = ( args: FilterArgs ): UseTicketsReturn => {
    const [ tickets, setTickets ] = useState< SupportTicket[] >( [] );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ totalItems, setTotalItems ] = useState( 0 );
    const [ totalPages, setTotalPages ] = useState( 0 );
    const [ currentPage, setCurrentPage ] = useState( 1 );
    const [ statusCounts, setStatusCounts ] = useState< StatusCounts >( {
        open: 0,
        closed: 0,
        total: 0,
    } );

    const fetchTickets = async ( status?: SupportTicketStatus ) => {
        setIsLoading( true );

        try {
            const queryParams = {
                ...args,
                status: status || args.status || 'open',
            };

            const response = await apiFetch< ApiFetchResponse >( {
                path: addQueryArgs(
                    '/dokan/v1/vendor/support-tickets',
                    queryParams
                ),
                parse: false,
            } );

            const requests = await response.json();

            setTickets( requests );
            setTotalItems(
                parseInt( response.headers.get( 'X-WP-Total' ), 10 )
            );
            setTotalPages(
                parseInt( response.headers.get( 'X-WP-TotalPages' ), 10 )
            );
            setCurrentPage(
                parseInt( response.headers.get( 'X-WP-CurrentPage' ), 10 )
            );

            const open = parseInt(
                response.headers.get( 'X-Status-Open' ),
                10
            );
            const closed = parseInt(
                response.headers.get( 'X-Status-Closed' ),
                10
            );

            setStatusCounts( {
                open,
                closed,
                total: open + closed,
            } );
        } catch ( err ) {
            setTickets( [] );
        } finally {
            setIsLoading( false );
        }
    };

    const getTotalStats = async (): Promise< StatusCounts > => {
        try {
            const stats = await apiFetch< any >( {
                path: '/dokan/v1/vendor/support-tickets/stats',
                method: 'GET',
            } );

            setStatusCounts( stats );
            return stats;
        } catch ( err ) {
            throw err;
        }
    };

    const updateStatus = async ( ticket: SupportTicket, status: string ) => {
        setIsLoading( true );

        try {
            const updatedTicket = await apiFetch< SupportTicket >( {
                path: `/dokan/v1/vendor/support-tickets/${ ticket.id }/status`,
                method: 'POST',
                data: { status },
            } );

            // Update local tickets list
            setTickets( prev => {
                return prev.map( t => {
                    if(t.id === ticket.id) {
                        return {
                            ...t,
                            status: updatedTicket.status,
                        }
                    }
                    return t;
                } ) as SupportTicket [];
            } );
        } catch ( err ) {
            throw err;
        } finally {
            setIsLoading( false );
        }
    };


    return {
        tickets,
        isLoading,
        fetchTickets,
        updateStatus,
        getTotalStats,
        totalItems,
        totalPages,
        currentPage,
        statusCounts,
    };
};
