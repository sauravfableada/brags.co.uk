import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';

import './../types/warranty-request.ts';
import RequestsList from './components/RequestsList';
import RequestDetails from './components/Request/SingleDetails';

domReady( function () {
    // Register the new routes
    window.wp.hooks.addFilter(
        'dokan-dashboard-routes',
        'dokan-frontend-rma-menu',
        function ( routes: any[] ) {
            // Add a new route for the request list page
            routes.push( {
                id: 'dokan-frontend-rma-request-menu',
                path: 'return-request',
                title: __( 'Return Requests', 'dokan' ),
                capabilities: [ 'dokan_view_store_rma_menu' ],
                exact: true,
                order: 10,
                parent: '',
                // @ts-ignore
                element: <RequestsList />,
            } );

            // Add a new route for the request details page
            routes.push( {
                id: 'dokan-frontend-rma-request-details',
                path: 'return-request/:requestId',
                title: __( 'Return Request', 'dokan' ),
                capabilities: [ 'dokan_view_store_rma_menu' ],
                backUrl: '/return-request',
                exact: true,
                order: 10,
                parent: '',
                // @ts-ignore
                element: <RequestDetails />,
            } );

            return routes;
        }
    );
} );
