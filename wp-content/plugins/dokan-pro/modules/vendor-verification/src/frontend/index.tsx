import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';
import Verification from '../components/frontend/Verification';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { VisitStore } from '@dokan/components';

domReady( () => {
    // @ts-ignore
    window.wp.hooks.addFilter(
        'dokan-dashboard-routes',
        'dokan-pro-verification',
        ( routes: any ) => {
            routes.push( {
                id: 'settings-verification',
                title: (
                    <VisitStore>{ __( 'Verification', 'dokan' ) }</VisitStore>
                ),
                element: <Verification />,
                path: '/settings/verification',
                exact: true,
                order: 10,
                parent: 'settings',
                capabilities: [ 'dokan_view_store_verification_menu' ],
            } );
            return routes;
        }
    );
} );
