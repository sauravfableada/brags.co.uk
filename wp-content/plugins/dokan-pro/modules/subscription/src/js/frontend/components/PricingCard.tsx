import { useState, useEffect, RawHTML } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useToast } from '@getdokan/dokan-ui';
import { Slot } from '@wordpress/components';
import { PriceHtml, DokanButton } from '@dokan/components';
import { applyFilters } from '@wordpress/hooks';

const PricingCard = ( {
    pack,
    currentSubscription,
    currentPackProduct,
    hasManagePermission,
} ) => {
    const toast = useToast();
    const [ packId, setPackId ] = useState( 0 );
    const [ subscriptionId, setSubscriptionId ] = useState( 0 );

    useEffect( () => {
        if ( ! pack?.id ) {
            return;
        }

        setPackId( Number( pack.id ) );
    }, [ pack ] );

    useEffect( () => {
        if ( ! currentSubscription?.subscription_id ) {
            return;
        }

        if ( ! currentSubscription?.order_id ) {
            return;
        }

        setSubscriptionId( Number( currentSubscription.subscription_id ) );
    }, [ currentSubscription ] );

    // Is active subscription.
    const isActiveSubscription = () => {
        return packId === subscriptionId;
    };

    // Prevent pack switching.
    const preventPackSwitching = ( e ) => {
        if (
            subscriptionId &&
            currentSubscription?.is_recurring &&
            ! currentSubscription?.has_active_cancelled_sub &&
            ! isActiveSubscription()
        ) {
            e.preventDefault();

            toast( {
                type: 'error',
                title: __(
                    'You are already under a recurring subscription plan. To switch pack, you need to cancel it first.',
                    'dokan'
                ),
            } );
        }
    };
    const recurringPaymentInterval =
        parseInt( pack.recurring_period_interval ) > 1
            ? pack.recurring_period_interval + ' ' + pack.recurring_period_type
            : pack.recurring_period_type;

    const actoins = () => {
        return (
            <div className="px-6 min-w-full">
                <DokanButton
                    variant="primary"
                    link={ ! hasManagePermission }
                    onClick={ preventPackSwitching }
                    disabled={ hasManagePermission }
                    href={
                        isActiveSubscription()
                            ? currentPackProduct?.permalink
                            : `?add-to-cart=${ packId }`
                    }
                    className={ `m-0 w-full py-2 px-4 dokan-btn ${
                        isActiveSubscription() ? 'dokan-btn-secondary' : ''
                    }` }
                >
                    { subscriptionId && currentSubscription?.order_id
                        ? isActiveSubscription()
                            ? __( 'Your Pack', 'dokan' )
                            : __( 'Switch Pack', 'dokan' )
                        : pack.allowed_trial === 'yes'
                        ? __( 'Start Free Trial', 'dokan' )
                        : __( 'Buy Now', 'dokan' ) }
                </DokanButton>
            </div>
        );
    };

    const Actions = applyFilters(
        'dokan-vendor-subscription-actions',
        actoins,
        isActiveSubscription,
        pack,
        currentSubscription
    );

    return (
        <div
            className={ `flex flex-col justify-start items-start py-6 bg-white rounded-lg shadow-sm border [&_li]:list-disc! ${
                isActiveSubscription() ? 'border-dokan-btn-border' : 'border-gray-200'
            }` }
        >
            <Slot
                name="dokan-subscription-pack-before-content"
                fillProps={ {
                    pack,
                    currentSubscription,
                    isActiveSubscription: isActiveSubscription(),
                } }
            />

            <h3 className="mb-2 mr-2 px-6 text-lg font-small font-semibold text-gray-700 text-center min-w-full">
                { pack.title }
            </h3>

            <div className="prose flex items-center w-full gap-2 px-6 mb-1 text-sm text-gray-500 min-w-full min-h-10 !box-border *:div:!box-border *:!max-w-full [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:list-outside [&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:list-outside">
                { pack.short_description ? (
                    <RawHTML>{ pack.short_description }</RawHTML>
                ) : (
                    <span className="text-sm text-gray-400 w-full !text-center block">
                        { __(
                            'No short description listed for the pack.',
                            'dokan'
                        ) }
                    </span>
                ) }
            </div>

            <Slot
                name="dokan-subscription-pack-before-price"
                fillProps={ {
                    pack,
                    currentSubscription,
                    isActiveSubscription: isActiveSubscription(),
                } }
            />

            <div className="flex justify-center items-baseline mt-4 mb-3 px-6 text-center min-w-full">
                <span className="text-4xl font-bold text-black">
                    <PriceHtml price={ pack.price } />
                </span>
                <span className="ml-1 text-gray-500">
                    { pack.recurring_payment === 'yes'
                        ? `/ ${ recurringPaymentInterval }`
                        : parseInt( pack.pack_validity ) <= 0
                        ? `/ ${ __( 'unlimited days', 'dokan' ) }`
                        : `/ ${ pack.pack_validity } ${ __(
                              'days',
                              'dokan'
                          ) }` }
                </span>
            </div>

            <Slot
                name="dokan-subscription-pack-after-price"
                fillProps={ {
                    pack,
                    currentSubscription,
                    isActiveSubscription: isActiveSubscription(),
                } }
            />

            <div className="flex justify-center items-baseline mb-6 px-6 text-center min-w-full">
                <ul className="flex justify-center items-baseline mb-1 px-6 text-center min-w-full text-sm font-small text-gray-500">
                    <li className="!list-disc">
                        { 'yes' === pack.recurring_payment
                            ? __( 'Recurring', 'dokan' )
                            : __( 'Non Recurring', 'dokan' ) }
                    </li>
                    { isActiveSubscription() && (
                        <li className="!list-disc !ml-6 text-dokan-primary">
                            { __( 'Active', 'dokan' ) }
                        </li>
                    ) }
                </ul>
            </div>

            <Actions />

            <Slot
                name="dokan-subscription-pack-after-actions"
                fillProps={ {
                    pack,
                    currentSubscription,
                    isActiveSubscription: isActiveSubscription(),
                } }
            />

            <div className="prose flex items-center w-full gap-2 border-t text-gray-500 border-gray-200 mt-8 pt-6 px-6 !box-border *:div:!box-border *:max-w-full [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:list-outside [&_ol]:list-decimal [&_ol]:pl-5 [&_ol]:list-outside">
                { pack.description ? (
                    <RawHTML>{ pack.description }</RawHTML>
                ) : (
                    <span className="text-sm text-gray-400 text-center w-full">
                        { __( 'No description listed for the pack.', 'dokan' ) }
                    </span>
                ) }
            </div>

            <Slot
                name="dokan-subscription-pack-after-content"
                fillProps={ {
                    pack,
                    currentSubscription,
                    isActiveSubscription: isActiveSubscription(),
                } }
            />
        </div>
    );
};

export default PricingCard;
