import { useState, useEffect, RawHTML } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { twMerge } from 'tailwind-merge';
import { Card, useToast } from '@getdokan/dokan-ui';
import { DokanButton, DokanAlert, DokanModal } from '@dokan/components';
import CurrentSubscriptionSkeleton from './skeleton/CurrentSubscriptionSkeleton';
import { VendorSubscription } from '../definition/VendorSubscription';
import { cancelVendorSubscription } from '../Util';

const CurrentSubscription = ( {
    vendorId,
    subscription,
    loading,
    className = '',
    setSubscription,
} ) => {
    const toast = useToast();
    const [ hasPendingSubscription, setHasPendingSubscription ] =
        useState( false );
    const [ canPostProduct, setCanPostProduct ] = useState( false );
    const [ isOnTrial, setIsOnTrial ] = useState( false );
    const [ isRecurring, setIsRecurring ] = useState( false );
    const [ hasActiveCancelledSub, setHasActiveCancelledSub ] =
        useState( false );
    const [ subscriptionAction, setSubscriptionAction ] = useState( '' );
    const [ subscriptionTitle, setSubscriptionTitle ] = useState( '' );
    const [ subscriptionDetails, setSubscriptionDetails ] = useState( '' );
    const [ isConfirmationModalOpen, setIsConfirmationModalOpen ] =
        useState( false );

    useEffect( () => {
        if ( ! subscription ) {
            return;
        }

        setHasPendingSubscription( !! subscription.has_pending_subscription );
        setCanPostProduct( !! subscription.can_post_product );
        setIsOnTrial( !! subscription.is_on_trial );
        setIsRecurring( !! subscription.is_recurring );
        setHasActiveCancelledSub( !! subscription.has_active_cancelled_sub );
    }, [ subscription ] );

    useEffect( () => {
        if ( ! subscription ) {
            return;
        }

        let action =
            isRecurring && hasActiveCancelledSub ? 'activate' : 'cancel';

        if ( ! isRecurring && hasActiveCancelledSub ) {
            action = '';
        }

        setSubscriptionAction( action );
    }, [ subscription, isRecurring, hasActiveCancelledSub ] );

    useEffect( () => {
        if ( ! subscription ) {
            return;
        }

        let title = subscription.subscription_title;

        if ( isOnTrial ) {
            title += sprintf(
                // Translators: %d is the trial duration, %s is the trial period type (e.g., days, weeks, months).
                __( '(%1$d %2$s trial)', 'dokan' ),
                subscription?.trial_range,
                subscription?.trial_period_type
            );
        }

        setSubscriptionTitle( title );
    }, [ subscription, isOnTrial ] );

    useEffect( () => {
        if ( ! subscription ) {
            return;
        }

        let subscriptionBullets = sprintf(
            // Translators: %s is the amount of allowed products.
            _n(
                '<li class="!mb-1 !list-disc">You can add <span class="font-semibold">%s product</span></li>',
                '<li class="!mb-1 !list-disc">You can add <span class="font-semibold">%s products</span></li>',
                parseInt( subscription?.no_of_allowed_products ),
                'dokan'
            ),
            subscription?.no_of_allowed_products
        );

        if ( ! isRecurring ) {
            const numberOfDays =
                subscription?.end_date === 'Unlimited'
                    ? __( 'unlimited', 'dokan' )
                    : subscription?.pack_validity_days;

            subscriptionBullets += ' ';
            subscriptionBullets += sprintf(
                // Translators: %s is the number of days.
                _n(
                    '<li class="!mb-1 !list-disc">Your package is valid for <span class="font-semibold">%s day</span></li>',
                    '<li class="!mb-1 !list-disc">Your package is valid for <span class="font-semibold">%s days</span></li>',
                    parseInt( numberOfDays ),
                    'dokan'
                ),
                numberOfDays
            );

            if ( subscription?.end_date === 'Unlimited' ) {
                subscriptionBullets += __(
                    '<li class="!mb-1 !list-disc">You have a <span class="font-semibold">lifetime package</span>.</li>',
                    'dokan'
                );
            } else {
                subscriptionBullets += sprintf(
                    // Translators: %s is the subscription pack expire date.
                    __(
                        '<li class="!mb-1 !list-disc">Your package will expire on <span class="font-semibold">%s</span>.</li>',
                        'dokan'
                    ),
                    subscription?.end_date
                );
            }
        }

        if ( isRecurring && ! isOnTrial && ! hasActiveCancelledSub ) {
            subscriptionBullets += sprintf(
                // Translators: %d is the trial duration, %s is the trial period type (e.g., days, weeks, months).
                __(
                    '<li class="!mb-1 !list-disc">You will be charged <span class="font-semibold">every %d %s</span>.</li>',
                    'dokan'
                ),
                subscription?.recurring_interval,
                subscription?.recurring_period_type
            );
            subscriptionBullets += sprintf(
                // Translators: %s is the next billing date.
                __(
                    '<li class="!mb-1 !list-disc">Next billing date: <span class="font-semibold">%s</span>.</li>',
                    'dokan'
                ),
                subscription?.subscription_end_date
            );
        }
        if ( isOnTrial ) {
            subscriptionBullets += sprintf(
                // Translators: %s is the trial end date.
                __(
                    '<li class="!mb-1 !list-disc">You are currently on a trial period until <span class="font-semibold">%s</span>.</li>',
                    'dokan'
                ),
                subscription?.subscription_trial_until
            );
        }

        setSubscriptionDetails( subscriptionBullets );
    }, [ subscription, isRecurring ] );

    // Subscription Action Handler.
    const handleSubscriptionAction = () => {
        if ( 'activate' === subscriptionAction ) {
            handleActivateSubscription();
        }

        if ( 'cancel' === subscriptionAction ) {
            setIsConfirmationModalOpen( true );
        }
    };

    // Activate Subscription Handler.
    const handleActivateSubscription = async () => {
        try {
            const data = {
                action: 'activate',
            };

            const updatedSubscription: VendorSubscription = await apiFetch( {
                path: `dokan/v1/vendor-subscription/update/${ vendorId }`,
                method: 'PUT',
                data,
            } );

            if ( ! updatedSubscription?.has_active_cancelled_sub ) {
                setHasActiveCancelledSub( false );
                setSubscription( updatedSubscription );

                toast( {
                    type: 'success',
                    title: __( 'Subscription activation successful.', 'dokan' ),
                } );
            } else {
                toast( {
                    type: 'error',
                    title: __( 'Subscription activation failed.', 'dokan' ),
                } );
            }
        } catch ( error ) {
            toast( {
                type: 'error',
                title:
                    __( 'Subscription activation error:', 'dokan' ) +
                    error?.message,
            } );
        }
    };

    // Cancel Subscription Handler.
    const handleCancelSubscription = async () => {
        try {
            const updatedSubscription: VendorSubscription = await cancelVendorSubscription( vendorId );

            setSubscription( updatedSubscription );
            toast( {
                type: 'success',
                title: __(
                    'Subscription cancellation successful.',
                    'dokan'
                ),
            } );

            if ( updatedSubscription?.has_active_cancelled_sub ) {
                setHasActiveCancelledSub( true );
            } 
        } catch ( error ) {
            toast( {
                type: 'error',
                title:
                    __( 'Subscription cancellation error:', 'dokan' ) +
                    error?.message,
            } );
        } finally {
            setIsConfirmationModalOpen( false );
        }
    };

    return (
        <div className={ twMerge( 'dokan-layout', className ) }>
            <Card className="mb-5">
                <Card.Header>
                    <Card.Title>
                        { __( 'Current Subscription', 'dokan' ) }
                    </Card.Title>
                </Card.Header>
                <Card.Body>
                    <>
                        { loading ? (
                            <CurrentSubscriptionSkeleton />
                        ) : ! ( subscription?.id && subscription?.order_id ) ? (
                            <div>
                                { __(
                                    "You don't have an active subscription.",
                                    'dokan'
                                ) }
                            </div>
                        ) : null }

                        { hasPendingSubscription ? (
                            <div>
                                <RawHTML>
                                    { sprintf(
                                        // Translators: %1$s wraps the subscription title in a styled <span>, %2$s and %3$s wrap the "Pay Now" text in a styled <a> tag with a link.
                                        __(
                                            'The intended %1$s subscription is inactive due to payment failure. %2$sPay Now%3$s to activate it again.',
                                            'dokan'
                                        ),
                                        `<span class="text-dokan-primary italic">${ subscriptionTitle }</span>`,
                                        `<a href="?add-to-cart=${ subscription?.subscription_id }" class="text-dokan-link">`,
                                        '</a>'
                                    ) }
                                </RawHTML>
                            </div>
                        ) : canPostProduct ? (
                            <>
                                <div className="mb-2.5">
                                    <RawHTML>
                                        { sprintf(
                                            // Translators: %s subscription pack title.
                                            __(
                                                'You are using %s package. Package details are given below:',
                                                'dokan'
                                            ),
                                            `<span class="text-dokan-primary font-semibold">${ subscriptionTitle }</span>`
                                        ) }
                                    </RawHTML>
                                </div>

                                <ul className="ml-4 mt-4 !list-disc">
                                    <RawHTML>{ subscriptionDetails }</RawHTML>
                                </ul>

                                { hasActiveCancelledSub && !isOnTrial ? (
                                    <DokanAlert
                                        variant="info"
                                        label={ sprintf(
                                            // Translators: %s subscription validity end date.
                                            __(
                                                'Your subscription has been cancelled! However, it is still active till %s.',
                                                'dokan'
                                            ),
                                            subscription?.subscription_end_date
                                        ) }
                                        className="mt-4"
                                    ></DokanAlert>
                                ) : null }
                            </>
                        ) : null }

                        { subscription &&
                            subscription?.id &&
                            subscription?.order_id &&
                            subscriptionAction && (
                                <div className="flex items-center justify-between border border-gray-200 rounded-md p-3.5 mt-5">
                                    <RawHTML className="inline-block mr-1">
                                        { sprintf(
                                            __(
                                                // Translators: %s is the subscription update action (e.g., activate, cancel).
                                                '%s your subscription',
                                                'dokan'
                                            ),
                                            'activate' === subscriptionAction
                                                ? __( 'Activate', 'dokan' )
                                                : __( 'Cancel', 'dokan' )
                                        ) }
                                    </RawHTML>

                                    <DokanButton
                                        variant={
                                            'activate' === subscriptionAction
                                                ? 'primary'
                                                : 'danger'
                                        }
                                        label={
                                            'activate' === subscriptionAction
                                                ? __( 'Activate', 'dokan' )
                                                : __( 'Cancel', 'dokan' )
                                        }
                                        onClick={ () =>
                                            handleSubscriptionAction()
                                        }
                                    />
                                </div>
                            ) }
                    </>
                </Card.Body>
            </Card>

            <DokanModal
                isOpen={ isConfirmationModalOpen }
                namespace="dokan-vendor-subscription-cancel"
                dialogTitle={ __( 'Cancel Subscription', 'dokan' ) }
                confirmationTitle={ __(
                    'Are you sure you want to proceed?',
                    'dokan'
                ) }
                confirmationDescription={ __(
                    'Canceling subscription might immediately deactivate your current subscription.',
                    'dokan'
                ) }
                confirmButtonText={ __( 'Yes, Cancel', 'dokan' ) }
                cancelButtonText={ __( 'Close', 'dokan' ) }
                onConfirm={ () => handleCancelSubscription() }
                onClose={ () => setIsConfirmationModalOpen( false ) }
            />
        </div>
    );
};

export default CurrentSubscription;
