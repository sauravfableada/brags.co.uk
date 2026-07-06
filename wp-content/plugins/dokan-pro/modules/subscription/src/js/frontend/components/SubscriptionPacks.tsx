import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Card, useToast } from '@getdokan/dokan-ui';
import { Slot } from '@wordpress/components';
import { twMerge } from 'tailwind-merge';
import PricingCard from './PricingCard';
import { TypeSubscriptionPacks } from '../definition/SubscriptonPack';
import SubscriptionPacksSkeleton from './skeleton/SubscriptionPacksSkeleton';
import { SubscriptionPackProduct } from '../definition/SubscriptionPackProduct';

const SubscriptionPacks = ( {
    subscription,
    className = '',
    hasManagePermission,
    showHeader = true,
    cardClassname = '',
} ) => {
    const toast = useToast();
    const [ loading, setLoading ] = useState( true );
    const [ packages, setPackages ] = useState( [] );
    const [ currentPackProduct, setCurrentPackProduct ] = useState( null );

    // Get sorted packages to show the current subscription package at the beginning.
    const getSortedPackages = () => {
        return packages.sort( ( a, b ) => {
            if (
                parseInt( a.id ) === parseInt( subscription?.subscription_id )
            ) {
                return -1;
            }

            return 0;
        } );
    };

    useEffect( () => {
        const fetchPackages = async () => {
            setLoading( true );

            try {
                const response: TypeSubscriptionPacks = await apiFetch( {
                    path: 'dokan/v1/vendor-subscription/packages?per_page=100',
                    method: 'GET',
                } );

                if ( response.length ) {
                    setPackages( response );
                }
            } catch ( error ) {
                toast( {
                    type: 'error',
                    title:
                        __( 'Error fetching subscription packages:', 'dokan' ) +
                        error?.message,
                } );
            } finally {
                setLoading( false );
            }
        };

        fetchPackages();
    }, [] );

    useEffect( () => {
        if ( ! subscription?.subscription_id ) {
            return;
        }

        const fetchCurrentPackageProduct = async () => {
            const response: SubscriptionPackProduct = await apiFetch( {
                path: `dokan/v1/products/${ subscription.subscription_id }`,
                method: 'GET',
            } );

            if ( ! response.id ) {
                return;
            }

            setCurrentPackProduct( response );
        };

        fetchCurrentPackageProduct();
    }, [ subscription ] );

    return (
        <div className={ twMerge( 'dokan-layout', className ) }>
            <Card className={ twMerge( 'mb-5', cardClassname ) }>
                { showHeader && (
                    <Card.Header>
                        <Card.Title>
                            { __( 'Subscription Packages', 'dokan' ) }
                        </Card.Title>
                    </Card.Header>
                ) }
                <Card.Body>
                    <>
                        <Slot
                            name="dokan-subscription-packs-before"
                            fillProps={ { packages, subscription } }
                        />

                        { loading ? (
                            <SubscriptionPacksSkeleton />
                        ) : getSortedPackages().length ? (
                            <div className="grid md:grid-cols-2 gap-5">
                                { packages.map( ( pack ) => (
                                    <PricingCard
                                        key={ pack.id }
                                        pack={ pack }
                                        currentSubscription={ subscription }
                                        hasManagePermission={
                                            hasManagePermission
                                        }
                                        currentPackProduct={
                                            currentPackProduct
                                        }
                                    />
                                ) ) }
                            </div>
                        ) : (
                            <p>
                                { __( 'No subscription pack found.', 'dokan' ) }
                            </p>
                        ) }

                        <Slot
                            name="dokan-subscription-packs-after"
                            fillProps={ { packages, subscription } }
                        />
                    </>
                </Card.Body>
            </Card>
        </div>
    );
};

export default SubscriptionPacks;
