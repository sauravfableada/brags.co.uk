import domReady from '@wordpress/dom-ready';
import { __ } from '@wordpress/i18n';
import App from './components/App';

domReady( () => {
    // @ts-ignore
    window.wp.hooks.addFilter(
        'dokan-dashboard-routes',
        'dokan-shipstation',
        function ( routes ) {
            routes.push( {
                id: 'dokan-shipstation',
                title: __( 'ShipStation', 'dokan' ),
                element: <App />,
                path: 'settings/shipstation',
                exact: true,
                order: 10,
                parent: '',
            } );

            return routes;
        }
    );
} );
