import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { TabPanel } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import coreStore from '@dokan/stores/core';
import { DokanAlert, Forbidden } from '@dokan/components';
import { DokanToaster, useToast } from '@getdokan/dokan-ui';
import CurrentSubscription from './CurrentSubscription';
import SubscriptionPacks from './SubscriptionPacks';
import SubscriptionOrders from './SubscriptionOrders';
import { User } from '../definition/CurrentUserTypes';
import { VendorSubscription } from '../definition/VendorSubscription';
import { applyFilters } from '@wordpress/hooks';

const App = ( props ) => {
    const hasSubscriptionPermission: boolean = useSelect( ( select ) => {
        const hasManageCap = select( coreStore ).hasCap( 'manage_options' ),
            hasSellerCap = select( coreStore ).hasCap( 'seller' );

        return applyFilters(
            'dokan_vendor_subscription_permission_caps',
            hasManageCap || hasSellerCap,
            props
        ) as boolean;
    }, [] );

    if ( ! hasSubscriptionPermission ) {
        return <Forbidden />;
    }

    const toast = useToast();
    const [ loading, setLoading ] = useState( true );
    const [ vendorId, setVendorId ] = useState( 0 );
    const [ subscription, setSubscription ] = useState( null );
    const [ tabQueryParam, setTabQueryParam ] = useState( '' );
    const tabsData = [
        {
            name: 'packs',
            title: __( 'Subscription Packs', 'dokan' ),
            className:
                'border-0 border-b border-solid mr-5 -mb-px space-x-8 whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium cursor-pointer hover:bg-transparent focus:outline-none text-gray-500 border-gray-200 hover:text-gray-600 hover:border-gray-300',
        },
    ];

    const hasManagePermission: boolean = useSelect( ( select ) => {
        return applyFilters(
            'dokan_vendor_subscription_has_manage_caps',
            select( coreStore ).hasCap( 'manage_options' ),
            props
        ) as boolean;
    }, [] );

    const currentUser: User = useSelect( ( select ) => {
        return select( coreStore ).getCurrentUser();
    }, [] );

    const onTabChange = ( tabName ) => {
        props.navigate( `?tab=${ tabName }` );
    };

    useEffect( () => {
        const queryParams = new URLSearchParams( props.location.search );
        const tab = queryParams.get( 'tab' );
        const tabIds = tabsData.map( ( item ) => item.name );

        if ( ! tab || ! tabIds.includes( tab ) ) {
            queryParams.set( 'tab', 'packs' ); // Set default tab
            props.navigate(
                { search: queryParams.toString() },
                { replace: true }
            );
        }

        setTabQueryParam( tab );
    }, [ props.location.search ] );

    useEffect( () => {
        if ( ! currentUser || ! currentUser?.id ) {
            return;
        }

        setVendorId( currentUser?.id );
    }, [ currentUser ] );

    useEffect( () => {

        const fetchSubscription = async () => {
            setLoading( true );
            try {
                const response: VendorSubscription = await apiFetch( {
                    path: `dokan/v1/vendor-subscription/vendor/${ vendorId }`,
                    method: 'GET',
                } );

                if ( response.subscription_id ) {
                    setSubscription( response );
                }
            } finally {
                setLoading( false );
            }
        };

        if ( vendorId ) {
            fetchSubscription();
        }
    }, [ vendorId ] );

    if ( ! hasManagePermission ) {
        tabsData.push( {
            name: 'orders',
            title: __( 'Subscription Orders', 'dokan' ),
            className:
                'border-0 border-b border-solid mr-5 -mb-px space-x-8 whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium cursor-pointer hover:bg-transparent focus:outline-none text-gray-500 border-gray-200 hover:text-gray-600 hover:border-gray-300',
        } );
    }

    return (
        <div>
            { tabQueryParam && (
                <TabPanel
                    className="dokan-tab-panel text-gray-500 hover:text-gray-700 [&:not(:last-child)]:*:border-b *:first:border-gray-200 *:first:*:border-transparent *:[&:not(:last-child)]:*:border-b-2 focus:*:[&:not(:last-child)]:*:outline-transparent"
                    activeClass="!text-dokan-primary !border-dokan-btn-border !border-b-2 dokan-active-tab"
                    tabs={ tabsData }
                    initialTabName={ tabQueryParam }
                    onSelect={ onTabChange }
                >
                    { ( tab ) => (
                        <div className="mt-5">
                            { tab.name === 'packs' && (
                                <>
                                    <CurrentSubscription
                                        vendorId={ vendorId }
                                        subscription={ subscription }
                                        className="mb-5"
                                        loading={ loading }
                                        setSubscription={ setSubscription }
                                    />
                                    <SubscriptionPacks
                                        subscription={ subscription }
                                        hasManagePermission={
                                            hasManagePermission
                                        }
                                    />
                                </>
                            ) }

                            { tab.name === 'orders' &&
                                ! hasManagePermission && (
                                    <SubscriptionOrders vendorId={ vendorId } />
                                ) }
                        </div>
                    ) }
                </TabPanel>
            ) }

            <DokanToaster />
        </div>
    );
};

export default App;
