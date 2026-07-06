import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';

import './../types/store-support.ts';
import TicketsList from './components/TicketsList';
import TicketDetails from './components/Ticket/TicketDetails';

domReady( function () {
    // Register the new routes
    window.wp.hooks.addFilter(
        'dokan-dashboard-routes',
        'dokan-frontend-store-support-menu',
        function ( routes: any[] ) {
            // Add a new route for the request list page
            routes.push( {
                id: 'dokan-frontend-store-support-menu',
                path: 'support',
                title: __( 'Support Tickets', 'dokan' ),
                capabilities: [ 'dokan_manage_support_tickets' ],
                exact: true,
                order: 10,
                parent: '',
                // @ts-ignore
                element: <TicketsList />,
            } );

            // Add a new route for the request details page
            routes.push( {
                id: 'dokan-frontend-store-support-details',
                path: 'support/:ticketId',
                title: __( 'Support Ticket', 'dokan' ),
                capabilities: [ 'dokan_manage_support_tickets' ],
                backUrl: '/support',
                exact: true,
                order: 10,
                parent: '',
                // @ts-ignore
                element: <TicketDetails />,
            } );

            return routes;
        }
    );
} );
