import domReady from '@wordpress/dom-ready';
import ReviewList from './components/ReviewList';

domReady( () => {
    // Add the staff list component to the vendor staff route
    // @ts-ignore
    window.wp.hooks.addFilter(
        'dokan-admin-dashboard-routes',
        'dokan-admin-store-reviews',
        function ( routes = [] ) {
            routes.push( {
                id: 'dokan-admin-store-reviews',
                path: 'store-reviews',
                element: ReviewList,
            } );
            return routes;
        }
    );
} );
