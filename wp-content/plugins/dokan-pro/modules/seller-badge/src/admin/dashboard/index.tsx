import './style.scss';
import domReady from '@wordpress/dom-ready';
import { addFilter } from '@wordpress/hooks';
import SellerBadgesList from './SellerBadgesList';
import CreateOrEditSellerBadge from './CreateOrEditSellerBadge';

domReady( () => {
    addFilter(
        'dokan-admin-dashboard-routes',
        'dokan-admin-seller-badges-route',
        function ( routes: any[] ) {
            routes.push( {
                id: 'seller-badges',
                element: <SellerBadgesList />,
                path: '/dokan-seller-badge',
            } );
            routes.push( {
                id: 'seller-badges-create',
                element: <CreateOrEditSellerBadge />,
                path: '/dokan-seller-badge/new',
            } );
            routes.push( {
                id: 'seller-badges-edit',
                element: <CreateOrEditSellerBadge />,
                path: '/dokan-seller-badge/edit/:id',
            } );
            return routes;
        }
    );
} );
