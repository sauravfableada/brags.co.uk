import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';
import BookingContainer from './BookingContainer';

domReady( function () {
    window.wp.hooks.addFilter(
        'dokan-dashboard-routes',
        'dokan-vendor-booking',
        function ( routes: any[] ) {
            routes.push(
                {
                    id: 'dokan-vendor-booking',
                    path: 'booking',
                    title: __( 'All Booking Products', 'dokan' ),
                    capabilities: [ 'dokan_view_booking_menu' ],
                    exact: true,
                    order: 10,
                    parent: '',
                    element: <BookingContainer activeTab="products" />,
                },
                {
                    id: 'dokan-vendor-booking-manage',
                    path: 'booking/my-bookings',
                    title: __( 'Manage Bookings', 'dokan' ),
                    capabilities: [ 'dokan_manage_bookings' ],
                    exact: true,
                    order: 10,
                    element: <BookingContainer activeTab="bookings" />,
                },
                {
                    id: 'dokan-vendor-booking-calendar',
                    path: 'booking/calendar',
                    title: __( 'Calendar', 'dokan' ),
                    capabilities: [ 'dokan_manage_booking_calendar' ],
                    exact: true,
                    order: 10,
                    element: <BookingContainer activeTab="calendar" />,
                },
                {
                    id: 'dokan-vendor-booking-resources',
                    path: 'booking/resources',
                    title: __( 'Manage Resources', 'dokan' ),
                    capabilities: [ 'dokan_manage_booking_resource' ],
                    exact: true,
                    order: 10,
                    element: <BookingContainer activeTab="resources" />,
                }
            );

            return routes;
        }
    );
} );
