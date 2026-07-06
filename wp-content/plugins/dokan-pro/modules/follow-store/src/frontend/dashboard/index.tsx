import domReady from '@wordpress/dom-ready';
import { __ } from '@wordpress/i18n';
import FollowerList from './FollowerList';

domReady( () => {
    window.wp.hooks.addFilter(
        'dokan-dashboard-routes',
        'dokan-vendor-dashboard-followers-list',
        function ( routes ) {
            routes.push( {
                id: 'dokan-followers-list',
                title: __( 'Followers', 'dokan' ),
                element: <FollowerList />,
                path: 'followers',
                exact: true,
                order: 10,
                parent: '',
                capabilities: [ 'dokan_view_overview_menu' ],
            } );

            return routes;
        }
    );
} );
