import { __ } from '@wordpress/i18n';
import VerificationTab from './components/VerificationTab';
import { addFilter } from '@wordpress/hooks';
import { CircleCheck } from 'lucide-react';

addFilter(
    'dokan-admin-vendor-tabs',
    'dokan-admin-vendor-verification-tab',
    function ( tabs = [] ) {
        tabs.push( {
            name: 'verification',
            title: __( 'Verification', 'dokan' ),
            component: VerificationTab,
            position: 3,
        } );
        return tabs;
    }
);

addFilter(
    'dokan.dashboard.vendor.header.storeName',
    'add-dokan-verification-badge',
    function ( StoreNameComponent, vendor = [] ) {
        if ( ! vendor || ! vendor.is_verified ) {
            return StoreNameComponent;
        }
        return () => (
            <div className="flex items-center gap-2">
                <StoreNameComponent />
                <CircleCheck
                    size="22"
                    fill="rgba(52, 100, 227, 1)"
                    color="#fff"
                />
            </div>
        );
    },
    10,
    2
);
