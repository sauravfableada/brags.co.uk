import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';
import { Fill } from '@wordpress/components';
import { ExternalLink } from 'lucide-react';

import '../../../../../src/definitions/window-types';
import AddonList from './components/AddonList';

declare const dokanProductAddon: {
    settingsUrl: string;
    storeUrl: string;
};

const HeaderActions = () => (
    <Fill name="dokan-header-actions">
        <a
            href={ dokanProductAddon.storeUrl }
            target="_blank"
            rel="noreferrer"
            className="dokan-btn dokan-btn-default relative z-10"
            onClick={ ( e ) => e.stopPropagation() }
        >
            { __( 'Visit Store', 'dokan' ) }
            <ExternalLink className="inline-block ml-1" size={ 14 } />
        </a>
        <a
            href={ `${ dokanProductAddon.settingsUrl }?add=1` }
            className="dokan-btn dokan-btn-theme relative z-10"
            onClick={ ( e ) => e.stopPropagation() }
        >
            <i className="fas fa-plus mr-1"></i>
            { __( 'Create New Addon', 'dokan' ) }
        </a>
    </Fill>
);

const AddonPage = ( props: React.ComponentProps< typeof AddonList > ) => (
    <>
        <HeaderActions />
        <AddonList { ...props } />
    </>
);

domReady( function () {
    window.wp.hooks.addFilter(
        'dokan-dashboard-routes',
        'dokan-frontend-product-addon-menu',
        function ( routes: any[] ) {
            routes.push( {
                id: 'dokan-frontend-product-addon-menu',
                path: 'settings/product-addon',
                title: __( 'Product Addons', 'dokan' ),
                exact: true,
                order: 10,
                parent: '',
                // @ts-ignore
                element: <AddonPage />,
            } );

            return routes;
        }
    );
} );
