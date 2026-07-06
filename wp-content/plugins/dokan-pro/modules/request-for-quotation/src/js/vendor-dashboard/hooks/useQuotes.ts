import { useState } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';

import {
    Quote,
    QuoteStatus,
    QuoteStatusCount,
    FilterState,
    ApiFetchResponse,
} from '../types/quote';
import { getDefaultStatusCounts } from './useStatusFilters';

/**
 * Maps a status key to its corresponding X-Status-* response header name.
 */
const STATUS_HEADER_MAP: Record< string, string > = {
    all: 'X-Status-All',
    pending: 'X-Status-Pending',
    approve: 'X-Status-Approved',
    updated: 'X-Status-Updated',
    accepted: 'X-Status-Accepted',
    expired: 'X-Status-Expired',
    reject: 'X-Status-Rejected',
    cancel: 'X-Status-Cancelled',
    converted: 'X-Status-Converted',
    trash: 'X-Status-Trash',
};

interface UseQuotesReturn {
    quotes: Quote[];
    isLoading: boolean;
    totalItems: number;
    totalPages: number;
    statusCounts: QuoteStatusCount[];
    fetchQuotes: ( status?: QuoteStatus ) => Promise< void >;
}

export const useQuotes = ( args: FilterState ): UseQuotesReturn => {
    const [ quotes, setQuotes ] = useState< Quote[] >( [] );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ totalItems, setTotalItems ] = useState( 0 );
    const [ totalPages, setTotalPages ] = useState( 0 );
    const [ statusCounts, setStatusCounts ] = useState< QuoteStatusCount[] >(
        []
    );

    const fetchQuotes = async ( status?: QuoteStatus ) => {
        setIsLoading( true );

        try {
            const queryParams: Record< string, any > = {
                ...args,
                status: status || args.status || 'all',
            };

            // Remove 'all' status from query (backend treats empty as all)
            if ( queryParams.status === 'all' ) {
                delete queryParams.status;
            }

            if ( ! queryParams.search ) {
                delete queryParams.search;
            }

            const response = await apiFetch< ApiFetchResponse >( {
                path: addQueryArgs(
                    '/dokan/v1/vendor/request-for-quote',
                    queryParams
                ),
                parse: false,
            } );

            const data = await response.json();
            setQuotes( data );

            setTotalItems(
                parseInt( response.headers.get( 'X-WP-Total' ) ?? '0', 10 )
            );
            setTotalPages(
                parseInt( response.headers.get( 'X-WP-TotalPages' ) ?? '1', 10 )
            );

            // Build status counts from getDefaultStatusCounts(), updating
            // each entry's count from the corresponding response header.
            setStatusCounts(
                getDefaultStatusCounts().map( ( item ) => ( {
                    ...item,
                    count: parseInt(
                        response.headers.get(
                            STATUS_HEADER_MAP[ item.key ] ?? ''
                        ) ?? '0',
                        10
                    ),
                } ) )
            );
        } catch ( err ) {
            setQuotes( [] );
        } finally {
            setIsLoading( false );
        }
    };

    return {
        quotes,
        isLoading,
        totalItems,
        totalPages,
        statusCounts,
        fetchQuotes,
    };
};
