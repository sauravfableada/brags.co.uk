// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import $ from 'jquery';
import React from 'react';
import { useCallback, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    PaymentElement,
    useElements,
    useStripe,
} from '@stripe/react-stripe-js';

import './../../../../../../src/definitions/window-types';
import {
    DokanStripeExpressData,
    PaymentFormBillingDetails,
    PaymentFormClassicProps,
} from '../../types';
import {
    type ConfirmPaymentData,
    type StripeElements,
    type StripePaymentElementOptions,
    type FieldsOption,
} from '@stripe/stripe-js';

// Import centralized handlers
import {
    processCheckoutHandler,
    updateIntentHandler,
    updateFailedOrderHandler,
} from '../../utils/api';

const PaymentForm = ( {
    onChange,
    paymentIntentId,
    subscriptionId,
    ajaxurl,
    nonce,
    orderId,
    orderReturnURL,
    addPaymentReturnURL,
    assets,
    isOrderPay,
    isCheckout,
    isAddPaymentMethod,
}: PaymentFormClassicProps ) => {
    const settings: DokanStripeExpressData = window.dokanStripeExpress;

    // Sync subscriptionId to a hidden form input so it's included in form POST/serialize
    useEffect( () => {
        const $container = $( '.dokan-stripe-express-subscription' ).length
            ? $( '.dokan-stripe-express-subscription' )
            : $( '#dokan-stripe-express-payment-data' );
        if ( ! $container.length ) {
            return;
        }
        let $input = $container.find( 'input[name="subscription_id"]' );
        if ( subscriptionId ) {
            if ( ! $input.length ) {
                $container.append(
                    $( '<input>', {
                        type: 'hidden',
                        name: 'subscription_id',
                        value: subscriptionId,
                    } )
                );
            } else {
                $input.val( subscriptionId );
            }
        } else {
            $input.remove();
        }
    }, [ subscriptionId ] );
    const { accountDescriptor } = settings;

    const stripe = useStripe();
    const elements = useElements();

    // Utility methods
    const isStripeExpressChosen = () => {
        return (
            $( 'input#payment_method_dokan_stripe_express' ).is( ':checked' ) ||
            false
        );
    };

    const isUsingSavedPaymentMethod = () => {
        if (
            $( '#wc-dokan_stripe_express-payment-token-new' ).is( ':checked' )
        ) {
            return false;
        }
        return $( 'input[name="wc-dokan_stripe_express-payment-token"]' ).is(
            ':checked'
        );
    };

    const isSavingNewPaymentMethod = () => {
        return $( '#wc-dokan_stripe_express-new-payment-method' ).is(
            ':checked'
        );
    };

    const blockUI = ( element: any ) => {
        element.addClass( 'processing' );
        if ( typeof element.block === 'function' ) {
            $( document.body ).block( {
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6,
                },
            } );
        }
    };

    const unblockUI = ( element: any ) => {
        element.removeClass( 'processing' );
        if ( typeof element.unblock === 'function' ) {
            $( document.body ).unblock();
        }
    };

    const showError = ( errorMessage: string, isHtml = false ) => {
        const container = $( '.woocommerce-notices-wrapper' ).first();
        if ( ! container.length ) {
            return;
        }
        container
            .find(
                '.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message'
            )
            .remove();
        const html = isHtml
            ? errorMessage
            : `<ul class="woocommerce-error" role="alert"><li>${ errorMessage }</li></ul>`;

        container.html( html );
        container[ 0 ].scrollIntoView( { behavior: 'smooth' } );
        $( document.body ).trigger( 'checkout_error' );
    };

    // Get billing details from form fields
    const getBillingDetails = useCallback( (): PaymentFormBillingDetails => {
        const form = $(
            'form.checkout, #order_review, form#add_payment_method'
        );
        const formFields: Record< string, any > = form.length
            ? Array.from( new FormData( form[ 0 ] as HTMLFormElement ) ).reduce(
                  ( obj, [ key, value ] ) => ( { ...obj, [ key ]: value } ),
                  {}
              )
            : {};

        return {
            name:
                `${ formFields.billing_first_name || '' } ${
                    formFields.billing_last_name || ''
                }`.trim() || '',
            email: formFields.billing_email || '',
            phone: formFields.billing_phone || '',
            address: {
                country: formFields.billing_country || '',
                line1: formFields.billing_address_1 || '',
                line2: formFields.billing_address_2 || '',
                city: formFields.billing_city || '',
                state: formFields.billing_state || '',
                postal_code: formFields.billing_postcode || '',
            },
        };
    }, [] );

    const getHiddenBillingDetails = useCallback( () => {
        return {
            name:
                settings.billingFields.includes( 'billing_first_name' ) ||
                settings.billingFields.includes( 'billing_last_name' )
                    ? 'never'
                    : 'auto',
            email: settings.billingFields.includes( 'billing_email' )
                ? 'never'
                : 'auto',
            phone: settings.billingFields.includes( 'billing_phone' )
                ? 'never'
                : 'auto',
            address: {
                country: settings.billingFields.includes( 'billing_country' )
                    ? 'never'
                    : 'auto',
                line1: settings.billingFields.includes( 'billing_address_1' )
                    ? 'never'
                    : 'auto',
                line2: settings.billingFields.includes( 'billing_address_2' )
                    ? 'never'
                    : 'auto',
                city: settings.billingFields.includes( 'billing_city' )
                    ? 'never'
                    : 'auto',
                state: settings.billingFields.includes( 'billing_state' )
                    ? 'never'
                    : 'auto',
                postalCode: settings.billingFields.includes(
                    'billing_postcode'
                )
                    ? 'never'
                    : 'auto',
            },
        } as FieldsOption[ 'billingDetails' ];
    }, [ settings.billingFields ] );

    // Confirmation methods
    const confirmPayment = useCallback(
        async ( returnUrl: string ): Promise< { error?: any } > => {
            if ( ! stripe || ! elements ) {
                return {
                    error: {
                        message: __(
                            'Confirm Payment: Stripe not initialized',
                            'dokan'
                        ),
                    },
                };
            }

            const confirmationConfig: {
                elements: StripeElements;
                confirmParams: ConfirmPaymentData;
                redirect: 'always';
            } = {
                elements,
                confirmParams: {
                    return_url: returnUrl,
                    payment_method_data: {
                        billing_details: getBillingDetails(),
                    },
                },
                redirect: 'always',
            };

            return await stripe.confirmPayment( confirmationConfig );
        },
        [ stripe, elements, getBillingDetails ]
    );

    const confirmSetup = useCallback(
        async (
            returnUrl: string,
            clientSecret: string
        ): Promise< { error?: any } > => {
            if ( ! stripe || ! elements ) {
                return {
                    error: {
                        message: __(
                            'Confirm Setup: Stripe not initialized',
                            'dokan'
                        ),
                    },
                };
            }

            const confirmationConfig: {
                elements: StripeElements;
                clientSecret: string;
                confirmParams: ConfirmPaymentData;
                redirect?: 'always';
            } = {
                elements,
                clientSecret,
                confirmParams: {
                    return_url: returnUrl,
                    payment_method_data: {
                        billing_details: getBillingDetails(),
                    },
                },
                redirect: 'always',
            };

            return await stripe.confirmSetup( confirmationConfig );
        },
        [ stripe, elements, getBillingDetails ]
    );

    // Handle checkout form submission
    const handleCheckout = useCallback( async () => {
        if ( ! stripe || ! elements ) {
            return;
        }

        const paymentElement = elements?.getElement( 'payment' );
        if ( ! paymentElement ) {
            showError( __( 'Payment form is not initialized.', 'dokan' ) );
            return;
        }

        const form = $( 'form.checkout' );
        if ( ! form.length ) {
            return;
        }

        blockUI( form );

        // Create object where keys are form field names and values are form field values
        const formFields: Record< string, string > = form
            .serializeArray()
            .reduce(
                (
                    obj: { [ x: string ]: any },
                    field: { name: string | number; value: any }
                ) => {
                    obj[ field.name ] = field.value;
                    return obj;
                },
                {}
            );

        try {
            const savePaymentMethod =
                isUsingSavedPaymentMethod() || isSavingNewPaymentMethod()
                    ? 'yes'
                    : 'no';
            if ( ! subscriptionId ) {
                await updateIntentHandler(
                    paymentIntentId,
                    orderId,
                    savePaymentMethod,
                    $( '#dokan-stripe-express-payment-type' ).val(),
                    ajaxurl,
                    nonce
                );
                await elements.fetchUpdates();
            }
        } catch ( error: any ) {
            showError( error );
        }

        try {
            if (
                formFields.dokan_stripe_express_payment_type === 'apple_pay'
            ) {
                const proceed = await window.dokan_sweetalert(
                    __( 'Proceed to payment via Apple Pay?', 'dokan' ),
                    {
                        action: 'confirm',
                        confirmButtonColor: '#363636',
                        cancelButtonColor: '#b54545',
                        confirmButtonText: 'Yes, Proceed',
                        cancelButtonText: 'Decline',
                        imageUrl: assets.applePayLogo,
                        background: '#1a1a1a',
                    }
                );

                if ( proceed.isDismissed ) {
                    throw new Error(
                        __( 'Payment process dismissed', 'dokan' )
                    );
                }
            }

            if (
                formFields.dokan_stripe_express_payment_type === 'google_pay'
            ) {
                const proceed = await window.dokan_sweetalert(
                    __( 'Proceed to payment via Google Pay?', 'dokan' ),
                    {
                        action: 'confirm',
                        confirmButtonColor: '#1a73e8',
                        cancelButtonColor: '#d93025',
                        confirmButtonText: 'Yes, Proceed',
                        cancelButtonText: 'Decline',
                        imageUrl: assets.googlePayLogo,
                        imageWidth: 60,
                        imageHeight: 60,
                        background: '#ffffff',
                        color: '#202124',
                    }
                );

                if ( proceed.isDismissed ) {
                    throw new Error(
                        __( 'Payment process dismissed', 'dokan' )
                    );
                }
            }

            const response = await processCheckoutHandler(
                paymentIntentId,
                formFields,
                subscriptionId,
                ajaxurl,
                nonce
            );

            if ( response.result === 'failure' ) {
                if ( response.messages ) {
                    showError( response.messages, true );
                } else {
                    showError(
                        __(
                            'There was a problem processing the payment.',
                            'dokan'
                        )
                    );
                }
                return;
            }

            const options: {
                elements: StripeElements;
                confirmParams: ConfirmPaymentData;
                redirect: 'always';
            } = {
                elements,
                confirmParams: {
                    return_url: response.redirect,
                    payment_method_data: {
                        billing_details: getBillingDetails(),
                    },
                },
                redirect: 'always',
            };

            let error: any | { message: string };
            if ( response.payment_needed ) {
                ( { error } = await stripe.confirmPayment( options ) );
            } else {
                ( { error } = await stripe.confirmSetup( options ) );
            }

            if ( error ) {
                if (
                    ! [ 'boleto', 'oxxo' ].includes(
                        formFields.dokan_stripe_express_payment_type
                    )
                ) {
                    await updateFailedOrderHandler(
                        paymentIntentId,
                        response.order_id,
                        ajaxurl,
                        nonce
                    );
                }
                throw new Error( error.message );
            }
        } catch ( error: any ) {
            showError(
                error.message ||
                    __( 'There was a problem processing the payment.', 'dokan' )
            );
        } finally {
            unblockUI( form );
        }

        return false;
    }, [
        stripe,
        elements,
        paymentIntentId,
        orderId,
        ajaxurl,
        nonce,
        subscriptionId,
        getBillingDetails,
        assets.applePayLogo,
    ] );

    // Handle order pay form submission
    const handleOrderPay = useCallback(
        async ( event: Event ) => {
            event.preventDefault();
            if ( ! stripe || ! isStripeExpressChosen() ) {
                return;
            }

            const paymentElement = elements?.getElement( 'payment' );
            if ( ! paymentElement ) {
                showError( __( 'Payment form is not initialized.', 'dokan' ) );
                return;
            }

            const form = $( '#order_review' );
            if ( ! form.length ) {
                return;
            }

            blockUI( form );
            try {
                const returnUrl = `${ orderReturnURL }&save_payment_method=${
                    isSavingNewPaymentMethod() ? 'yes' : 'no'
                }`;
                const paymentType = $(
                    '#dokan-stripe-express-payment-type'
                ).val();

                const { error } = await confirmPayment( returnUrl );

                if ( error ) {
                    if ( ! [ 'boleto', 'oxxo' ].includes( paymentType ) ) {
                        await updateFailedOrderHandler(
                            paymentIntentId,
                            orderId,
                            ajaxurl,
                            nonce
                        );
                    }
                    throw new Error( error.message );
                }
            } catch ( error: any ) {
                showError(
                    error.message ||
                        __(
                            'There was a problem processing the payment.',
                            'dokan'
                        )
                );
            } finally {
                unblockUI( form );
            }
        },
        [
            stripe,
            elements,
            paymentIntentId,
            orderId,
            orderReturnURL,
            ajaxurl,
            nonce,
            confirmPayment,
        ]
    );

    // Handle add payment method form submission
    const handleAddPayment = useCallback(
        async ( event: Event ) => {
            event.preventDefault();
            if ( ! stripe || ! isStripeExpressChosen() ) {
                return;
            }

            const paymentElement = elements?.getElement( 'payment' );
            if ( ! paymentElement ) {
                showError( __( 'Payment form is not initialized.', 'dokan' ) );
                return;
            }

            const form = $( 'form#add_payment_method' );
            if ( ! form.length ) {
                return;
            }

            blockUI( form );
            try {
                const { error } = await confirmSetup(
                    addPaymentReturnURL,
                    paymentIntentId
                );

                if ( error ) {
                    throw new Error( error.message );
                }
            } catch ( error: any ) {
                showError(
                    error.message ||
                        __(
                            'There was a problem processing the payment.',
                            'dokan'
                        )
                );
            } finally {
                unblockUI( form );
            }
        },
        [ stripe, elements, paymentIntentId, addPaymentReturnURL, confirmSetup ]
    );

    // Form submission handlers
    useEffect( () => {
        const checkoutForm = $( 'form.checkout' );
        const addPaymentForm = $( 'form#add_payment_method' );
        const orderReviewForm = $( '#order_review' );

        if ( checkoutForm.length ) {
            checkoutForm.on(
                'checkout_place_order_dokan_stripe_express',
                () => {
                    if ( ! isUsingSavedPaymentMethod() ) {
                        handleCheckout();

                        return false;
                    }
                }
            );
        }

        if ( isAddPaymentMethod && addPaymentForm.length ) {
            addPaymentForm.on( 'submit', handleAddPayment );
        }

        if ( isOrderPay && orderReviewForm.length ) {
            orderReviewForm.on( 'submit', handleOrderPay );
        }
    }, [
        handleCheckout,
        handleAddPayment,
        handleOrderPay,
        isAddPaymentMethod,
        isOrderPay,
    ] );

    // PaymentElement options
    const paymentElementOptions: StripePaymentElementOptions = {
        layout: {
            type: 'accordion',
            defaultCollapsed: false,
            radios: false,
            spacedAccordionItems: true,
        },
        ...( isCheckout && ! isOrderPay
            ? { defaultValues: { billingDetails: getBillingDetails() } }
            : {} ),
        ...( isCheckout && ! isOrderPay
            ? { fields: { billingDetails: getHiddenBillingDetails() } }
            : {} ),
        business: { name: accountDescriptor },
    };

    useEffect( () => {
        $( document.body ).trigger( 'wc-credit-card-form-init' );
    }, [] );

    return (
        <PaymentElement
            options={ paymentElementOptions }
            onChange={ onChange }
            onReady={ () => {
                const form = $( 'form.checkout' );
                if ( form.length ) {
                    form.removeClass( 'processing' );
                    if ( typeof form.unblock === 'function' ) {
                        $( document.body ).unblock();
                    }
                }
            } }
            onLoadError={ ( error ) => {
                $( document.body ).trigger( 'dokan-stripe-express-load-error', {
                    detail: error,
                } );
            } }
        />
    );
};

export default PaymentForm;
