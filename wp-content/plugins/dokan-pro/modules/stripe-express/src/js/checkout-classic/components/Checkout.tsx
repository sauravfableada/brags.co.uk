// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import $ from 'jquery';
import React from 'react';
import { useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import {
    type Appearance,
    loadStripe,
    type Stripe,
    type StripeElementLocale,
    type StripeElementsOptions,
} from '@stripe/stripe-js';
import { Elements } from '@stripe/react-stripe-js';

import { DokanStripeExpressData } from '../../types';
// Import centralized handlers
import {
    createPaymentIntentHandler,
    createSetupIntentHandler,
    createSubscriptionHandler,
    confirmIntentHandler,
} from '../../utils/api';
import {
    getFontRulesFromPage,
    getSubscriptionProductId,
    handleEarlyRenewal,
} from '../../utils';
import PaymentForm from './PaymentForm';

import { CheckoutState } from '../../types';

const initialState: CheckoutState = {
    error: null,
    subscriptionId: '',
    isLoading: true,
    isComplete: false,
    selectedPaymentMethodRaw: '',
};

const Checkout = () => {
    const [ state, setState ] = useState< CheckoutState >( initialState );
    const [ paymentIntentId, setPaymentIntentId ] = useState< string >( '' );
    const [ paymentIntentClientSecret, setPaymentIntentClientSecret ] =
        useState< string >( '' );
    const [ stripe, setStripe ] = useState< Stripe | null >( null );
    const [ shouldMount, setShouldMount ] = useState< boolean >( false );

    // Add ref to track initialization
    const isInitializedRef = useRef< boolean >( false );

    const settings: DokanStripeExpressData = window.dokanStripeExpress;
    const {
        key,
        ajaxurl,
        nonce,
        isOrderPay,
        isCheckout,
        isAddPaymentMethod,
        isChangingPayment,
        orderId,
        orderReturnURL,
        addPaymentReturnURL,
        assets,
        appearance,
        locale,
    } = settings;

    const stripePromise = useMemo( () => {
        if ( ! key ) {
            return null;
        }
        return loadStripe( key );
    }, [ key ] );

    // Initialize Stripe
    useEffect( () => {
        stripePromise
            ?.then( ( stripeInstance ) => {
                setStripe( stripeInstance );
            } )
            .catch( ( error ) => {
                setState( ( prev ) => ( {
                    ...prev,
                    error: sprintf(
                        // translators: error message
                        __( 'Failed to load Stripe: %s', 'dokan' ),
                        error.message
                    ),
                    isLoading: false,
                } ) );
            } );
    }, [] );

    // Check payment method
    useEffect( () => {
        const checkPaymentMethod = () => {
            setShouldMount(
                $( 'input#payment_method_dokan_stripe_express' ).is(
                    ':checked'
                )
            );
        };

        checkPaymentMethod();
    }, [] );

    // Initialize payment/setup intent or subscription
    useEffect( () => {
        const initializeIntent = async () => {
            // Prevent re-initialization if already done
            if ( isInitializedRef.current || ! shouldMount || ! stripe ) {
                return;
            }

            // Mark as initialized
            isInitializedRef.current = true;

            setState( ( prev ) => ( { ...prev, isLoading: true } ) );

            try {
                const subscriptionProductId = getSubscriptionProductId();
                if (
                    subscriptionProductId &&
                    ! state.subscriptionId &&
                    ! paymentIntentId
                ) {
                    const result = await createSubscriptionHandler(
                        subscriptionProductId,
                        ajaxurl,
                        nonce
                    );
                    if ( ! result.is_success ) {
                        throw new Error(
                            result.message ||
                                __(
                                    'There was a problem processing the subscription. Please try again.',
                                    'dokan'
                                )
                        );
                    }
                    setPaymentIntentId( result.paymentIntentData?.id || '' );
                    setPaymentIntentClientSecret( result.clientSecret || '' );
                    setState( ( prev ) => ( {
                        ...prev,
                        subscriptionId: result.subscriptionId || '',
                        isLoading: false,
                    } ) );
                } else if (
                    ( isAddPaymentMethod || isChangingPayment ) &&
                    ! paymentIntentId
                ) {
                    const result = await createSetupIntentHandler(
                        ajaxurl,
                        nonce
                    );
                    if ( ! result.success ) {
                        throw new Error(
                            result.message ||
                                __(
                                    'There was a problem setting up the payment method. Please try again.',
                                    'dokan'
                                )
                        );
                    }
                    setPaymentIntentId( result.data?.id || '' );
                    setPaymentIntentClientSecret(
                        result.data.client_secret || ''
                    );
                    setState( ( prev ) => ( {
                        ...prev,
                        isLoading: false,
                    } ) );
                } else if ( ! paymentIntentId ) {
                    const result = await createPaymentIntentHandler(
                        ajaxurl,
                        nonce,
                        isOrderPay ? orderId : '0'
                    );
                    if ( ! result.is_success ) {
                        throw new Error(
                            result.message ||
                                __(
                                    'There was a problem processing the payment. Please try again.',
                                    'dokan'
                                )
                        );
                    }
                    setPaymentIntentId( result.paymentIntentData?.id || '' );
                    setPaymentIntentClientSecret( result.clientSecret || '' );
                    setState( ( prev ) => ( {
                        ...prev,
                        isLoading: false,
                    } ) );
                } else {
                    // Intent or subscription already exists
                    setState( ( prev ) => ( { ...prev, isLoading: false } ) );
                }

                // Note: client_secret validation is now handled by individual handlers
            } catch ( error: any ) {
                // Allow re-initialization on error
                isInitializedRef.current = false;
                setState( ( prev ) => ( {
                    ...prev,
                    error:
                        error.message ||
                        __(
                            'There was a problem processing the payment. Please check your email inbox and refresh the page to try again.',
                            'dokan'
                        ),
                    isLoading: false,
                } ) );
            }
        };

        void initializeIntent();
    }, [
        shouldMount,
        stripe,
        isAddPaymentMethod,
        isChangingPayment,
        isOrderPay,
        orderId,
        ajaxurl,
        nonce,
        state.subscriptionId,
        paymentIntentId,
    ] );

    // SCA modal
    const maybeShowAuthenticationModal = async () => {
        if ( ! stripe ) {
            return;
        }

        const paymentMethodId = $(
            '#dokan-stripe-express-payment-method'
        ).val();
        const savePaymentMethod = $(
            '#wc-dokan_stripe_express-new-payment-method'
        ).is( ':checked' );

        const confirmation = await confirmIntentHandler(
            window.location.href,
            savePaymentMethod ? paymentMethodId : null,
            ajaxurl,
            nonce
        );
        if ( confirmation === true ) {
            return;
        }

        // @ts-ignore
        const { request, isOrderPage } = confirmation;
        if ( isOrderPage ) {
            const orderReviewForm = $( '#order_review' );
            orderReviewForm.addClass( 'processing' );
            orderReviewForm.append(
                '<div class="blockUI blockOverlay" style="background: #fff; opacity: 0.6;"></div>'
            );
            $( '#payment' ).css( 'display', 'none' );
        }

        history.replaceState(
            '',
            document.title,
            window.location.pathname + window.location.search
        );

        try {
            window.location.href = await request;
        } catch ( error: any ) {
            const checkoutForm = $( 'form.checkout' );
            const orderReviewForm = $( '#order_review' );
            if ( checkoutForm.length ) {
                checkoutForm.removeClass( 'processing' );
                checkoutForm.find( '.blockOverlay' ).remove();
            }
            if ( orderReviewForm.length ) {
                orderReviewForm.removeClass( 'processing' );
                orderReviewForm.find( '.blockOverlay' ).remove();
            }
            $( '#payment' ).css( 'display', 'block' );
            setState( ( prev ) => ( {
                ...prev,
                error:
                    error.message ||
                    __(
                        'There was a problem processing the payment. Please check your email inbox and refresh the page to try again.',
                        'dokan'
                    ),
            } ) );
            const container = $( '.woocommerce-notices-wrapper' );
            if ( container.length ) {
                container.html(
                    `<ul class="woocommerce-error" role="alert"><li>${
                        error.message ||
                        __(
                            'There was a problem processing the payment. Please check your email inbox and refresh the page to try again.',
                            'dokan'
                        )
                    }</li></ul>`
                );
                $(
                    '.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message'
                ).remove();
                container[ 0 ].scrollIntoView( { behavior: 'smooth' } );
                $( document.body ).trigger( 'checkout_error' );
            }
        }
    };

    useEffect( () => {
        if (
            window.location.hash.startsWith( '#dokan-stripe-express-confirm-' )
        ) {
            void maybeShowAuthenticationModal();
        }

        const handleHashChange = () => {
            if (
                window.location.hash.startsWith(
                    '#dokan-stripe-express-confirm-'
                )
            ) {
                void maybeShowAuthenticationModal();
            }
        };

        $( window ).on( 'hashchange', handleHashChange );
        return () => $( window ).off( 'hashchange', handleHashChange );
    }, [ stripe, ajaxurl, nonce ] );

    // Subscription early renewal
    useEffect( () => {
        const renewalButton = $(
            '#early_renewal_modal_submit[data-payment-method="dokan-stripe-express"], #early_renewal_modal_submit'
        );
        if ( renewalButton.length ) {
            renewalButton.on( 'click', ( event: any ) =>
                handleEarlyRenewal( event, maybeShowAuthenticationModal )
            );
        }

        return () => {
            if ( renewalButton.length ) {
                renewalButton.off( 'click' );
            }
        };
    }, [ ajaxurl, nonce ] );

    if ( state.error ) {
        return (
            <div className="dokan-stripe-express-error">
                { /* eslint-disable-next-line @wordpress/i18n-translator-comments */ }
                { sprintf( __( 'Error: %s', 'dokan' ), state.error ) }
            </div>
        );
    }

    if (
        ! state.isLoading &&
        shouldMount &&
        stripe &&
        ! paymentIntentClientSecret
    ) {
        return (
            <div className="dokan-stripe-express-error">
                { __(
                    'Failed to render payment form: Missing client secret',
                    'dokan'
                ) }
            </div>
        );
    }

    if (
        state.isLoading ||
        ! shouldMount ||
        ! stripe ||
        ! paymentIntentClientSecret
    ) {
        return <div>{ __( 'Loading…', 'dokan' ) }</div>;
    }

    const elementOptions: StripeElementsOptions = {
        clientSecret: paymentIntentClientSecret,
        appearance: appearance as Appearance,
        fonts: getFontRulesFromPage(),
        locale: locale as StripeElementLocale,
        loader: 'auto',
        ...( isAddPaymentMethod || isChangingPayment
            ? { wallets: { applePay: 'never', googlePay: 'never' } }
            : {} ),
    };

    return (
        <Elements stripe={ stripePromise } options={ elementOptions }>
            <PaymentForm
                onChange={ ( event ) => {
                    setState( ( prev ) => ( {
                        ...prev,
                        selectedPaymentMethodRaw: event.value.type,
                        isComplete: event.complete,
                    } ) );

                    $( '#dokan-stripe-express-payment-method' ).val(
                        event.value.type
                    );

                    $( '#dokan-stripe-express-payment-type' ).val(
                        event.value.type
                    );
                    $( '.woocommerce-SavedPaymentMethods-saveNew' ).css(
                        'display',
                        event.value.type !== 'new' ? 'block' : 'none'
                    );
                } }
                paymentIntentId={ paymentIntentId }
                subscriptionId={ state.subscriptionId }
                ajaxurl={ ajaxurl }
                nonce={ nonce }
                orderId={ orderId }
                orderReturnURL={ orderReturnURL }
                addPaymentReturnURL={ addPaymentReturnURL }
                assets={ assets }
                isOrderPay={ Boolean( isOrderPay ) }
                isCheckout={ Boolean( isCheckout ) }
                isAddPaymentMethod={ Boolean( isAddPaymentMethod ) }
                isChangingPayment={ Boolean( isChangingPayment ) }
            />
        </Elements>
    );
};

export default Checkout;
