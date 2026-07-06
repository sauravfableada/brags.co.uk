import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { VendorSubscription } from './InterfaceVendorSubscription';
import {
    assignSubscription,
    cancelVendorSubscription,
} from '../../js/frontend/Util';
import BillingNotification from './BillingNotification';
import { DokanModal, DokanButton } from '@dokan/components';
import { useToast } from '@getdokan/dokan-ui';
import CanceledSubscriptionAlert from './CanceledSubscriptionAlert';

function PackAction( {
    isActiveSubscription,
    currentPack,
    billingDate,
    billingDateType,
    vendor,
    fetchData,
    currentSubscription,
} ) {
    const toast = useToast();

    const [ isConfirmationModalOpen, setIsConfirmationModalOpen ] =
        useState( false );
    const adminActionBUttons = () => {
        if ( isActiveSubscription() ) {
            return (
                <DokanButton
                    variant="danger"
                    className="w-full"
                    onClick={ () => setIsConfirmationModalOpen( true ) }
                    disabled={
                        !! currentSubscription?.has_active_cancelled_sub
                    }
                >
                    { !! currentSubscription?.has_active_cancelled_sub
                        ? __( 'Assign Plan', 'dokan' )
                        : __( 'Cancel Subscription', 'dokan' ) }
                </DokanButton>
            );
        } else if ( 'yes' === currentPack.recurring_payment ) {
            return (
                <DokanButton
                    variant="primary"
                    className="w-full"
                    disabled={ true }
                >
                    { __( 'Assign Plan', 'dokan' ) }
                </DokanButton>
            );
        }
        return (
            <DokanButton
                variant="primary"
                className="w-full"
                onClick={ () => setIsConfirmationModalOpen( true ) }
            >
                { __( 'Assign Plan', 'dokan' ) }
            </DokanButton>
        );
    };
    const handleCancelSubscription = async () => {
        if ( ! vendor?.id ) {
            return;
        }

        try {
            const updatedSubscription: VendorSubscription =
                await cancelVendorSubscription( vendor?.id );

            if ( updatedSubscription?.has_active_cancelled_sub ) {
                // setSubscription( updatedSubscription );

                toast( {
                    type: 'success',
                    title: __(
                        'Subscription cancellation successful.',
                        'dokan'
                    ),
                } );
            } else {
                toast( {
                    type: 'error',
                    title: __( 'Subscription cancellation failed.', 'dokan' ),
                } );
            }

            fetchData();
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

    const handleAssignSubscription = async () => {
        if ( ! vendor?.id ) {
            return;
        }

        try {
            await assignSubscription( vendor, currentPack?.id );

            fetchData();
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
        <>
            <div className="px-6 w-full">
                { adminActionBUttons() }

                <DokanModal
                    isOpen={ isConfirmationModalOpen }
                    namespace="dokan-vendor-subscription-cancel"
                    dialogTitle=""
                    confirmationTitle={
                        isActiveSubscription()
                            ? __(
                                  'Are you sure you want to cancel the subscription plan?',
                                  'dokan'
                              )
                            : __(
                                  'Are you sure you want to activate this pack for this vendor?',
                                  'dokan'
                              )
                    }
                    confirmationDescription={
                        isActiveSubscription()
                            ? sprintf(
                                  __(
                                      'Next billing date is %s and reassign is not possible for recurring subscriptionChanging this vendor subscription pack will take effect immediately.',
                                      'dokan'
                                  ),
                                  billingDate
                              )
                            : __(
                                  'Changing this vendor subscription pack will take effect immediately.',
                                  'dokan'
                              )
                    }
                    confirmButtonText={
                        isActiveSubscription()
                            ? __( 'Yes, Cancel', 'dokan' )
                            : __( 'Assign', 'dokan' )
                    }
                    cancelButtonText={
                        isActiveSubscription()
                            ? __( 'No', 'dokan' )
                            : __( 'Cancel', 'dokan' )
                    }
                    onConfirm={ () => {
                        if ( isActiveSubscription() ) {
                            handleCancelSubscription();
                        } else {
                            handleAssignSubscription();
                        }
                    } }
                    onClose={ () => setIsConfirmationModalOpen( false ) }
                />
            </div>

            { billingDate && isActiveSubscription() && (
                <div className="px-6 p-3 w-full">
                    <BillingNotification
                        date={ billingDate }
                        type={ billingDateType }
                    />
                </div>
            ) }

            { isActiveSubscription() &&
                !! currentSubscription?.has_active_cancelled_sub && (
                    <div className="px-6 p-3 w-full">
                        <CanceledSubscriptionAlert
                            message={ sprintf(
                                // Translators: %s subscription validity end date.
                                __(
                                    'This subscription is cancelled. It will remain active until %s.',
                                    'dokan'
                                ),
                                currentSubscription?.end_date
                            ) }
                        />
                    </div>
                ) }
        </>
    );
}

export default PackAction;
