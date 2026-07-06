import { useCallback, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import type { Booking, StatusCount } from '../types';

export interface BookingBookingsQuery {
    view: {
        perPage: number;
        page: number;
        search: string;
        status: string;
        type: string;
    };
}

export function useBookingBookings( { view }: BookingBookingsQuery ) {
    const [data, setData] = useState< Booking[] >( [] );
    const [isLoading, setIsLoading] = useState( false );
    const [totalItems, setTotalItems] = useState( 0 );
    const [statusCounts, setStatusCounts] = useState< StatusCount[] >( [
        { key: 'all', label: __( 'All', 'dokan' ), count: 0 },
    ] );

    const fetchBookingStatusCounts = useCallback( async () => {
        try {
            const response = ( await apiFetch( {
                path: '/dokan/v1/booking/bookings/status-counts',
                method: 'GET',
            } ) ) as StatusCount[];

            if ( response && response.length > 0 ) {
                setStatusCounts( response );
            }
        } catch {
            // keep default counts
        }
    }, [] );

    const fetchBookings = useCallback( async () => {
        setIsLoading( true );

        try {
            const queryArgs: Record< string, any > = {
                per_page: view.perPage,
                page: view.page,
            };

            if ( view.status !== 'all' ) {
                queryArgs.status = view.status;
            }
            if ( view.search ) {
                queryArgs.search = view.search;
            }

            const response = ( await apiFetch( {
                path: addQueryArgs( '/dokan/v1/booking/bookings', queryArgs ),
                method: 'GET',
                parse: false,
            } ) ) as Response;

            const bookings = await response.json();
            const total = parseInt( response.headers.get( 'X-WP-Total' ) ?? '0', 10 );

            setData( bookings );
            setTotalItems( total );
        } catch {
            setData( [] );
            setTotalItems( 0 );
        } finally {
            setIsLoading( false );
        }
    }, [ view.perPage, view.page, view.status, view.search ] );

    useEffect( () => {
        void fetchBookingStatusCounts();
    }, [ fetchBookingStatusCounts ] );

    useEffect( () => {
        void fetchBookings();
    }, [ fetchBookings ] );

    return {
        data,
        isLoading,
        totalItems,
        statusCounts,
        fetchBookings,
        fetchBookingStatusCounts,
    };
}
