import { useState, useCallback, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import type { AuctionActivity, AuctionActivityFilterState } from '../types';

interface UseAuctionActivityReturn {
    data: AuctionActivity[];
    isLoading: boolean;
    totalItems: number;
    totalPages: number;
    fetchActivity: () => void;
}

export const useAuctionActivity = (
    filterArgs: AuctionActivityFilterState
): UseAuctionActivityReturn => {
    const [ data, setData ] = useState< AuctionActivity[] >( [] );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ totalItems, setTotalItems ] = useState( 0 );
    const [ totalPages, setTotalPages ] = useState( 0 );

    const filterArgsRef = useRef( filterArgs );
    filterArgsRef.current = filterArgs;

    const fetchActivity = useCallback( async () => {
        setIsLoading( true );
        try {
            const args = filterArgsRef.current;
            const queryArgs: Record< string, unknown > = {
                per_page: args.per_page,
                page: args.page,
            };

            if ( args.search ) {
                queryArgs.search = args.search;
            }
            if ( args.start_date ) {
                queryArgs.start_date = args.start_date;
            }
            if ( args.end_date ) {
                queryArgs.end_date = args.end_date;
            }

            const response = ( await apiFetch( {
                path: addQueryArgs( '/dokan/v1/auction/activity', queryArgs ),
                parse: false,
            } ) ) as Response;

            const responseData: AuctionActivity[] = await response.json();
            const total = response.headers.get( 'X-WP-Total' );
            const totalPagesHeader = response.headers.get( 'X-WP-TotalPages' );

            setData( responseData );
            setTotalItems( parseInt( total ?? '0', 10 ) );
            setTotalPages( parseInt( totalPagesHeader ?? '0', 10 ) );
        } catch ( error ) {
            // eslint-disable-next-line no-console
            console.error( 'Error fetching auction activity:', error );
            setData( [] );
        } finally {
            setIsLoading( false );
        }
    }, [] );

    useEffect( () => {
        void fetchActivity();
    }, [
        fetchActivity,
        filterArgs.page,
        filterArgs.per_page,
        filterArgs.search,
        filterArgs.start_date,
        filterArgs.end_date,
    ] );

    return {
        data,
        isLoading,
        totalItems,
        totalPages,
        fetchActivity,
    };
};
