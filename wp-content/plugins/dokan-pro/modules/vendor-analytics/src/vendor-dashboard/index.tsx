import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';

import AnalyticsDashboard from './components/AnalyticsDashboard';

domReady( function () {
    window.wp.hooks.addFilter(
        'dokan-dashboard-routes',
        'dokan-frontend-vendor-analytics',
        function ( routes: any[] ) {
            routes.push( {
                id: 'dokan-frontend-vendor-analytics',
                path: 'analytics',
                title: __( 'Store Stats', 'dokan' ),
                exact: true,
                order: 182,
                parent: '',
                element: ( props: any ) => (
                    // @ts-ignore
                    <AnalyticsDashboard
                        navigate={ props.navigate }
                        location={ props.location }
                    />
                ),
            } );

            return routes;
        }
    );
} );
