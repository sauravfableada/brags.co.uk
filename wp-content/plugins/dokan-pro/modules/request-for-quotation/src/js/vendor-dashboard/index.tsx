import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';

import QuoteList from './components/QuoteList';

domReady( function () {
    window.wp.hooks.addFilter(
        'dokan-dashboard-routes',
        'dokan-frontend-rfq-menu',
        function ( routes: any[] ) {
            routes.push( {
                id: 'dokan-frontend-rfq-list',
                path: 'requested-quotes',
                title: __( 'Request Quotes', 'dokan' ),
                capabilities: [ 'dokan_view_request_quote_menu' ],
                exact: true,
                order: 53,
                parent: '',
                // @ts-ignore
                element: <QuoteList />,
            } );

            return routes;
        }
    );
} );
