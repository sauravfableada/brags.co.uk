import { useState, useCallback, useEffect } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';

import type {
    TabKey,
    ApiFetchResponse,
} from '../types/analytics';

interface UseAnalyticsReturn {
    data: any;
    isLoading: boolean;
    totalItems: number;
    totalPages: number;
    refetch: () => void;
}

interface UseAnalyticsArgs {
    tab: TabKey;
    startDate: string;
    endDate: string;
    page: number;
    perPage: number;
}

export const useAnalytics = ( args: UseAnalyticsArgs ): UseAnalyticsReturn => {
    const [ data, setData ] = useState< any >( null );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ totalItems, setTotalItems ] = useState( 0 );
    const [ totalPages, setTotalPages ] = useState( 0 );
    const [ fetchKey, setFetchKey ] = useState( 0 );

    const { tab, startDate, endDate, page, perPage } = args;

    // Auto-fetch when query parameters change.
    useEffect( () => {
        let cancelled = false;

        const doFetch = async () => {
            setIsLoading( true );

            try {
                const queryParams: Record< string, any > = {
                    tab,
                    page,
                    per_page: perPage,
                };

                if ( startDate ) {
                    queryParams.start_date = startDate;
                }

                if ( endDate ) {
                    queryParams.end_date = endDate;
                }

                const response = ( await apiFetch( {
                    path: addQueryArgs(
                        '/dokan/v1/vendor/analytics',
                        queryParams
                    ),
                    parse: false,
                } ) ) as ApiFetchResponse;

                if ( cancelled ) {
                    return;
                }

                const responseData = await response.json();
                setData( responseData );

                setTotalItems(
                    parseInt(
                        response.headers.get( 'X-WP-Total' ) ?? '0',
                        10
                    )
                );
                setTotalPages(
                    parseInt(
                        response.headers.get( 'X-WP-TotalPages' ) ?? '1',
                        10
                    )
                );
            } catch {
                if ( ! cancelled ) {
                    setData( null );
                    setTotalItems( 0 );
                    setTotalPages( 0 );
                }
            } finally {
                if ( ! cancelled ) {
                    setIsLoading( false );
                }
            }
        };

        void doFetch();

        return () => {
            cancelled = true;
        };
    }, [ tab, startDate, endDate, page, perPage, fetchKey ] );

    const refetch = useCallback( () => {
        setFetchKey( ( prev ) => prev + 1 );
    }, [] );

    return {
        data,
        isLoading,
        totalItems,
        totalPages,
        refetch,
    };
};
