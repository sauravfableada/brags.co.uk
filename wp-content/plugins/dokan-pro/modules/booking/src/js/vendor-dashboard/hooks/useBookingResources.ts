import { useCallback, useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import type { BookingResource } from '../types';

export interface BookingResourcesQuery {
    view: {
        perPage: number;
        page: number;
        search: string;
        status: string;
        type: string;
    };
}

export function useBookingResources( { view }: BookingResourcesQuery ) {
    const [data, setData] = useState< BookingResource[] >( [] );
    const [isLoading, setIsLoading] = useState( false );
    const [totalItems, setTotalItems] = useState( 0 );

    const fetchResources = useCallback( async () => {
        setIsLoading( true );

        try {
            const queryArgs: Record< string, any > = {
                per_page: view.perPage,
                page: view.page,
            };

            if ( view.search ) {
                queryArgs.search = view.search;
            }

            const response = ( await apiFetch( {
                path: addQueryArgs( '/dokan/v1/booking/resources', queryArgs ),
                method: 'GET',
                parse: false,
            } ) ) as Response;

            const resources = await response.json();
            const total = parseInt( response.headers.get( 'X-WP-Total' ) ?? '0', 10 );

            setData( resources );
            setTotalItems( total );
        } catch {
            setData( [] );
            setTotalItems( 0 );
        } finally {
            setIsLoading( false );
        }
    }, [ view.perPage, view.page, view.search ] );

    useEffect( () => {
        void fetchResources();
    }, [ fetchResources ] );

    return {
        data,
        isLoading,
        totalItems,
        fetchResources,
    };
}
