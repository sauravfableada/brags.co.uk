import domReady from '@wordpress/dom-ready';
import ReportAbusePage from './ReportAbusePage';

domReady( () => {
    // @ts-ignore
    wp.hooks.addFilter(
        'dokan-admin-dashboard-routes',
        'dokan-admin-dashboard-report-abuse',
        ( routes ) => {
            routes.push( {
                id: 'dokan-report-abuse',
                element: <ReportAbusePage />,
                path: '/abuse-reports',
            } );

            return routes;
        }
    );
} );
