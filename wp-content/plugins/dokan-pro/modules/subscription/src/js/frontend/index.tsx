import domReady from '@wordpress/dom-ready';
import { __ } from '@wordpress/i18n';
import App from './components/App';
import SubscriptionOrders from './components/SubscriptionOrders';

domReady( () => {
    window.wp.hooks.addFilter(
        'dokan-dashboard-routes',
        'dokan-vendor-subscription',
        function ( routes ) {
            routes.push( {
                id: 'dokan-vendor-subscription',
                title: __( 'Subscription', 'dokan' ),
                element: <App />,
                path: 'subscription',
                exact: true,
                order: 10,
                parent: '',
            } );

            return routes;
        }
    );

    window.wp.hooks.addFilter(
        'dokan-dashboard-routes',
        'dokan-vendor-subscription-orders',
        function ( routes ) {
            routes.push( {
                id: 'dokan-subscription-orders-data-view-table',
                title: __( 'Subscription Orders', 'dokan' ),
                element: SubscriptionOrders,
                path: 'subscription/orders',
                exact: true,
                order: 10,
                parent: '',
            } );

            return routes;
        }
    );
} );
