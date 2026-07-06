import domReady from '@wordpress/dom-ready';
import VerificationAdmin from '../components/admin/VerificationAdmin';

domReady( () => {
    // @ts-ignore
    wp.hooks.addFilter(
        'dokan-admin-dashboard-routes',
        'dokan-admin-vendor-verifications-panel',
        ( routes ) => {
            routes.push( {
                id: 'verifications',
                element: <VerificationAdmin />,
                path: '/verifications',
            } );

            return routes;
        }
    );
} );
