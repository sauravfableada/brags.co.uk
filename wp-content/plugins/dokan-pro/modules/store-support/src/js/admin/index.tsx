import domReady from '@wordpress/dom-ready';
import StoreSupportList from './StoreSupportList';
import StoreSupportSingle from './StoreSupportSingle';
import { addFilter } from '@wordpress/hooks';

domReady( () => {
    addFilter(
        'dokan-admin-dashboard-routes',
        'dokan-admin-dashboard-store-support-list-single',
        ( routes: Array< object > ) => {
            routes.push( {
                id: 'dokan-admin-store-support',
                element: <StoreSupportList />,
                path: '/admin-store-support',
            } );

            routes.push( {
                id: 'dokan-admin-store-support-single',
                element: <StoreSupportSingle />,
                path: '/admin-store-support/:ticketId/:vendorId',
            } );

            return routes;
        }
    );
} );
