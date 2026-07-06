import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import SubscriptionTab from './components/SubscriptionTab';
import VendorEdit from './components/VendorEdit';
import domReady from '@wordpress/dom-ready';
import { registerPlugin } from '@wordpress/plugins';
import SubscriptionInfo from './components/SubscriptionInfo';

domReady( () => {
    addFilter(
        'dokan-admin-vendor-tabs',
        'dokan-admin-vendor-subscription-tab',
        function ( tabs = [] ) {
            tabs.push( {
                name: 'subscription',
                title: __( 'Subscription', 'dokan' ),
                component: SubscriptionTab,
                position: 4,
            } );
            return tabs;
        }
    );

    addFilter(
        'dokan-vendor-form-data-tabs-dokan-edit-vendor',
        'dokan-admin-edit-vendor-subscription-tab',
        function ( tabs = [] ) {
            tabs.push( {
                name: 'subscription',
                title: __( 'Subscription', 'dokan' ),
                className:
                    'border-0 border-b border-solid mr-5 -mb-px space-x-8 whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium cursor-pointer hover:bg-transparent focus:outline-none text-gray-500 border-gray-200 hover:text-gray-600 hover:border-gray-300',
                component: VendorEdit,
            } );
            return tabs;
        }
    );

    registerPlugin( 'dokan-admin-vendor-subscription-info-panel', {
        render: SubscriptionInfo,
        scope: 'dokan-admin-dashboard-vendors-single',
    } );
} );
