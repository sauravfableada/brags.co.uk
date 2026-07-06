import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { Card, DokanToaster, useToast } from '@getdokan/dokan-ui';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { NoInformation } from '@dokan/components';
import { LayoutGrid } from 'lucide-react';
import { dateI18n, getDate, getSettings } from '@wordpress/date';
import { VendorSubscription } from './InterfaceVendorSubscription';
import SubscriptionPacks from '../../js/frontend/components/SubscriptionPacks';

import { twMerge } from 'tailwind-merge';
import { addFilter } from '@wordpress/hooks';
import PackAction from './PackAction';
import SubscriptionPacksSkeleton from '../../js/frontend/components/skeleton/SubscriptionPacksSkeleton';

interface SubscriptionTabProps {
    vendor: Record< string, any >;
    vendorStats: Record< string, any > | null;
}

const SubscriptionTab = ( { vendor }: SubscriptionTabProps ) => {
    const [ subscription, setSubscription ] = useState< any >( null );
    const [ pack, setPack ] = useState< any >( null );
    const [ loading, setLoading ] = useState( true );
    const [ error, setError ] = useState( false );
    const [ billingDate, setBillingDate ] = useState( '' );
    const [ billingDateType, setBillingDateType ] = useState( '' );

    const toast = useToast();

    const setBillingNotification = ( subOrder, sub ) => {
        if (
            ! subOrder ||
            ! subOrder?.meta_data ||
            ! Array.isArray( subOrder?.meta_data ) ||
            ! sub ||
            ! sub?.is_recurring
        ) {
            return;
        }

        let packValidity = subOrder?.meta_data.find(
            ( meta ) => meta.key === '_pack_validity'
        );
        packValidity = packValidity ? getDate( packValidity ) : '';

        if ( ! packValidity ) {
            return;
        }

        let subscriptionPackValidityStart = subOrder?.meta_data.find(
            ( meta ) => meta.key === '_dokan_subscription_pack_validity_start'
        );
        subscriptionPackValidityStart = subscriptionPackValidityStart
            ? getDate( subscriptionPackValidityStart )
            : '';

        if ( packValidity < getDate( '' ) || ! subscriptionPackValidityStart ) {
            // Pack expired...
            setBillingDate(
                dateI18n(
                    getSettings().formats.date,
                    subscriptionPackValidityStart,
                    getSettings().timezone.string
                )
            );
            setBillingDateType( 'last' );
        }

        setBillingDate(
            dateI18n(
                getSettings().formats.date,
                packValidity,
                getSettings().timezone.string
            )
        );
        setBillingDateType( 'next' );
    };

    const fetchData = async () => {
        setLoading( true );
        setError( false );

        if ( ! vendor || ! vendor.id ) {
            setLoading( false );
            return;
        }

        try {
            const subRes: VendorSubscription = await apiFetch( {
                path: `dokan/v1/vendor-subscription/vendor/${ vendor.id }`,
                method: 'GET',
            } );

            if ( subRes && subRes.subscription_id ) {
                setSubscription( subRes );

                // Fetch pack
                try {
                    const packRes = await apiFetch( {
                        path: `dokan/v1/vendor-subscription/packages/${ subRes.subscription_id }`,
                        method: 'GET',
                    } );

                    setPack( packRes );
                } catch ( packError: any ) {
                    setError( true );
                    setPack( null );
                }

                // Fetch order
                if ( subRes.order_id ) {
                    try {
                        const orderRes = await apiFetch( {
                            path: `dokan/v1/orders/${ subRes.order_id }`,
                            method: 'GET',
                        } );

                        setBillingNotification( orderRes, subRes );
                    } catch ( orderError: any ) {
                        setError( true );
                        toast( {
                            type: 'error',
                            title:
                                __( 'Error fetching order info:', 'dokan' ) +
                                orderError?.message,
                        } );
                    }
                }
            } else {
                setSubscription( null );
            }
        } catch ( fetchError: any ) {
            setError( true );
            setSubscription( null );
            setPack( null );
        } finally {
            setLoading( false );
        }
    };

    useEffect( () => {
        fetchData();
    }, [] );

    useEffect( () => {
        addFilter(
            'dokan-vendor-subscription-actions',
            'dokan-vendor-subscription-actions',
            (
                actions,
                isActiveSubscription,
                currentPack,
                currentSubscription
            ) => {
                return () => (
                    <PackAction
                        isActiveSubscription={ isActiveSubscription }
                        currentPack={ currentPack }
                        vendor={ vendor }
                        billingDate={ billingDate }
                        billingDateType={ billingDateType }
                        fetchData={ fetchData }
                        currentSubscription={ currentSubscription }
                    />
                );
            },
            10,
            3
        );
    }, [] );

    if ( loading ) {
        return (
            <Card className="bg-white p-6">
                <SubscriptionPacksSkeleton />
            </Card>
        );
    }

    return (
        <div>
            { ( error || ! subscription || ! pack ) && (
                <div className="mb-6">
                    <NoInformation
                        icon={ <LayoutGrid /> }
                        title={ __( 'No Subscription Purchased', 'dokan' ) }
                        description={ __(
                            'Once subscription purchased, it will appear',
                            'dokan'
                        ) }
                        className="min-h-3 p-6"
                        contentClassName={ twMerge(
                            'flex-row gap-6 !justify-start'
                        ) }
                        titleClassName={ twMerge( 'mt-0' ) }
                    />
                </div>
            ) }

            { subscription && pack && (
                <SubscriptionPacks
                    subscription={ subscription }
                    hasManagePermission={ false }
                    showHeader={ false }
                    cardClassname={ twMerge( 'bg-white' ) }
                />
            ) }

            <DokanToaster />
        </div>
    );
};

export default SubscriptionTab;
