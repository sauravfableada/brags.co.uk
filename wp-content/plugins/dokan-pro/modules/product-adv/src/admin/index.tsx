import domReady from '@wordpress/dom-ready';
import { addFilter } from '@wordpress/hooks';
import AdvertisementList from './components/AdvertisementList';

domReady( () => {
    addFilter(
        'dokan-admin-dashboard-routes',
        'dokan-add-product-adv-admin-menu',
        function ( routes ) {
            routes.push( {
                id: 'product-advertising',
                element: <AdvertisementList />,
                path: 'product-advertising',
            } );
            return routes;
        }
    );
} );
