import { __ } from '@wordpress/i18n';
import { useMemo, useState } from '@wordpress/element';
import { DataViews, DokanBadge } from '@dokan/components';
import { useToast } from '@getdokan/dokan-ui';
import apiFetch from '@wordpress/api-fetch';
import type { Booking } from '../types';
import { useBookingBookings } from '../hooks';
import { decodeEntity } from 'html-entities';
import { decodeEntities } from '@wordpress/html-entities';

const BookingsTab = () => {
    const [ view, setView ] = useState( {
        perPage: 10,
        page: 1,
        search: '',
        type: 'table',
        status: 'all',
        layout: {
            styles: {
                status: {
                    width: '11%',
                },
                id: {
                    width: '11%',
                },
                product_title: {
                    width: '17%',
                },
                customer_name: {
                    width: '17%',
                },
                persons: {
                    width: '11%',
                },
                order_id: {
                    width: '11%',
                },
                start_date: {
                    width: '11%',
                },
                end_date: {
                    width: '11%',
                },
            },
        },
    } );
    const {
        data,
        isLoading,
        totalItems,
        statusCounts,
        fetchBookings,
        fetchBookingStatusCounts,
    } = useBookingBookings( { view } );
    const toast = useToast();
    const bookingUrl =
        typeof window !== 'undefined'
            ? window.dokanBooking?.bookingUrl || ''
            : '';

    const handleConfirmBooking = async ( bookingId: number ) => {
        try {
            await apiFetch( {
                path: `/dokan/v1/booking/bookings/${ bookingId }/confirm`,
                method: 'POST',
            } );

            await fetchBookings();
            await fetchBookingStatusCounts();

            toast( {
                type: 'success',
                title: __( 'Booking confirmed successfully', 'dokan' ),
            } );
        } catch ( error: any ) {
            toast( {
                type: 'error',
                title:
                    error.message || __( 'Failed to confirm booking', 'dokan' ),
            } );
        }
    };

    const actions = useMemo(
        () => [
            {
                id: 'booking-view',
                label: () => __( 'View', 'dokan' ),
                callback: ( [ item ]: Booking[] ) => {
                    window.location.href =
                        bookingUrl + 'booking-details/?booking_id=' + item.id;
                },
            },
            {
                id: 'booking-confirm',
                isEligible: ( item: Booking ) =>
                    item.status === 'pending-confirmation',
                label: () => __( 'Confirm', 'dokan' ),
                callback: ( [ item ]: Booking[] ) => {
                    void handleConfirmBooking( item.id );
                },
            },
        ],
        [ bookingUrl ]
    );

    const bookingTabs = useMemo(
        () => ( {
            items: statusCounts.map( ( status ) => ( {
                ...status,
                value: status.key,
            } ) ),
            onSelect: ( status: string ) => {
                setView( ( prev ) => ( {
                    ...prev,
                    page: 1,
                    status,
                } ) );
            },
        } ),
        [ statusCounts ]
    );

    const paginationInfo = {
        totalItems,
        totalPages: Math.ceil( totalItems / view.perPage ),
    };

    const bookingFields = [
        {
            id: 'status',
            label: __( 'Status', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: Booking } ) => {
                const statusMap: Record<
                    string,
                    { variant: string; label: string }
                > = {
                    'pending-confirmation': {
                        variant: 'warning',
                        label: __( 'Pending Confirmation', 'dokan' ),
                    },
                    confirmed: {
                        variant: 'success',
                        label: __( 'Confirmed', 'dokan' ),
                    },
                    cancelled: {
                        variant: 'danger',
                        label: __( 'Cancelled', 'dokan' ),
                    },
                    paid: {
                        variant: 'success',
                        label: __( 'Paid', 'dokan' ),
                    },
                    unpaid: {
                        variant: 'warning',
                        label: __( 'Unpaid', 'dokan' ),
                    },
                    'in-cart': {
                        variant: 'secondary',
                        label: __( 'In Cart', 'dokan' ),
                    },
                    complete: {
                        variant: 'success',
                        label: __( 'Complete', 'dokan' ),
                    },
                };
                const status = statusMap[ item.status ] || {
                    variant: 'secondary',
                    label: item.status,
                };
                return (
                    <DokanBadge
                        variant={ status.variant }
                        label={ status.label }
                    />
                );
            },
        },
        {
            id: 'id',
            label: __( 'ID', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: Booking } ) => (
                <a
                    href={
                        bookingUrl + 'booking-details/?booking_id=' + item.id
                    }
                    className="text-dokan-link cursor-pointer"
                >
                    { `#${ item.id }` }
                </a>
            ),
        },
        {
            id: 'product_title',
            label: __( 'Booked Product', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: Booking } ) => (
                <div>
                    { item.product_edit_url ? (
                        <a
                            href={ item.product_edit_url }
                            className="text-dokan-link cursor-pointer"
                        >
                            { item.product_title }
                        </a>
                    ) : (
                        <span>{ item.product_title }</span>
                    ) }
                    { item.resource_title && (
                        <span className="text-gray-500 text-sm block">
                            { item.resource_title }
                        </span>
                    ) }
                </div>
            ),
        },
        {
            id: 'customer_name',
            label: __( 'Customer', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: Booking } ) =>
                item.customer_email ? (
                    <a
                        href={ `mailto:${ item.customer_email }` }
                        className="text-dokan-link cursor-pointer"
                    >
                        { item.customer_name }
                    </a>
                ) : (
                    <span>{ item.customer_name || '—' }</span>
                ),
        },
        {
            id: 'persons',
            label: __( '# of Persons', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: Booking } ) => (
                <span>{ item.persons || '—' }</span>
            ),
        },
        {
            id: 'order_id',
            label: __( 'Order', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: Booking } ) =>
                item.order_id && item.order_url ? (
                    <a
                        href={ decodeEntities( item.order_url ) }
                        className="text-dokan-link cursor-pointer"
                    >
                        { `#${ item.order_id }` }
                    </a>
                ) : (
                    <span>{ '—' }</span>
                ),
        },
        {
            id: 'start_date',
            label: __( 'Start Date', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: Booking } ) => (
                <span>{ item.start_date || '—' }</span>
            ),
        },
        {
            id: 'end_date',
            label: __( 'End Date', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: Booking } ) => (
                <span>{ item.end_date || '—' }</span>
            ),
        },
    ];

    return (
        <DataViews
            data={ data }
            namespace="booking-data-view"
            tabs={ bookingTabs }
            fields={ bookingFields }
            getItemId={ ( item: any ) => item.id }
            onChangeView={ setView }
            paginationInfo={ paginationInfo }
            view={ view }
            actions={ actions }
            isLoading={ isLoading }
            search={ true }
        />
    );
};

export default BookingsTab;
