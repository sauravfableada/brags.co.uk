import domReady from '@wordpress/dom-ready';
import SubscriptionsPage from './SubscriptionsPage';

domReady( () => {
    // @ts-ignore
    wp.hooks.addFilter(
        'dokan-admin-dashboard-routes',
        'dokan-admin-dashboard-subscriptions',
        ( routes ) => {
            routes.push( {
                id: 'dokan-admin-subscriptions',
                element: <SubscriptionsPage />,
                path: '/subscriptions',
            } );

            return routes;
        }
    );
} );
