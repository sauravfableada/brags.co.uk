import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';
import SubscriptionDetails from './components/SubscriptionDetails';
import SubscriptionList from './components/SubscriptionList';
import '../../../src/definitions/window-types';
import '../../../src/stores/country-state';

domReady( function () {
    const dokanBtnColor = window
        .getComputedStyle( document.documentElement )
        .getPropertyValue( '--dokan-button-background-color' );

    if ( dokanBtnColor ) {
        document.documentElement.style.setProperty(
            '--wp-components-color-accent',
            dokanBtnColor
        );
    }
    window.wp.hooks.addFilter(
        'dokan-dashboard-routes',
        'dokan-frontend-user-subscription-menu',
        function ( routes ) {
            routes.push( {
                id: 'dokan-user-subscription',
                title: __( 'User Subscriptions', 'dokan' ),
                element: <SubscriptionList />,
                path: 'user-subscription',
                exact: true,
                order: 4,
                parent: '',
                capabilities: [ 'dokan_view_order_menu' ],
            } );

            return routes;
        }
    );
    window.wp.hooks.addFilter(
        'dokan-dashboard-routes',
        'dokan-frontend-user-subscription-details-menu',
        function ( routes ) {
            routes.push( {
                id: 'dokan-user-subscription-details',
                title: __( 'Subscription details', 'dokan' ),
                element: <SubscriptionDetails />,
                path: 'user-subscription/:subscriptionId',
                backUrl: '/user-subscription',
                exact: true,
                order: 4,
                parent: '',
                capabilities: [ 'dokan_view_order_menu' ],
            } );

            return routes;
        }
    );
} );
