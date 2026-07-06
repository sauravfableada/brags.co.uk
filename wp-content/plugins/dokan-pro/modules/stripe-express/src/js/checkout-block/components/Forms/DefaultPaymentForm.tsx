import {
    PaymentElement,
    useElements,
    useStripe,
} from '@stripe/react-stripe-js';
import { type StripePaymentElementOptions } from '@stripe/stripe-js';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { DefaultPaymentFormProps } from '../../../types';
import { mapBillingAddress } from '../../../utils';

const DefaultPaymentForm = ( props: DefaultPaymentFormProps ) => {
    const { eventRegistration, emitResponse, paymentIntentData, billing } =
        props;

    const { onPaymentSetup, onCheckoutSuccess } = eventRegistration;
    const { billingAddress } = billing;

    const stripe = useStripe();
    const elements = useElements();

    // @see https://docs.stripe.com/js/elements_object/create#stripe_elements-options
    let paymentElementOptions: StripePaymentElementOptions = {
        layout: 'accordion',
        defaultValues: {
            billingDetails: mapBillingAddress( billingAddress ),
        },
        fields: {
            billingDetails: {
                name: 'never',
                email: 'never',
                phone: 'never',
                address: {
                    country: 'never',
                    line1: 'never',
                    line2: 'never',
                    city: 'never',
                    state: 'never',
                    postalCode: 'never',
                },
            },
        },
    };

    // @see https://docs.stripe.com/js/elements_object/create#stripe_elements-options
    // @ts-ignore
    paymentElementOptions = wp.hooks.applyFilters(
        'dokan_stripe_express_payment_element_options',
        paymentElementOptions,
        props
    );

    /**
     * Handle payment setup
     *
     * @see https://developer.woocommerce.com/docs/cart-and-checkout-payment-method-integration-for-the-checkout-block/#3-props-fed-to-payment-method-nodes
     * @see https://developer.woocommerce.com/docs/cart-and-checkout-payment-method-integration-for-the-checkout-block/#7-passing-values-from-the-client-to-the-server-side-payment-processing
     * @see https://developer.woocommerce.com/docs/cart-and-checkout-checkout-flow-and-events/
     * @see https://docs.stripe.com/sdks/stripejs-react#usestripe-hook
     */
    useEffect( () => {
        const unsubscribe = onPaymentSetup( async () => {
            if ( paymentIntentData ) {
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: {
                            payment_intent_id: paymentIntentData.id,
                        },
                    },
                };
            }

            return {
                type: emitResponse.responseTypes.ERROR,
                message: __( 'Payment intent data is missing.', 'dokan' ),
            };
        } );

        return () => unsubscribe();
    }, [
        emitResponse.responseTypes.ERROR,
        emitResponse.responseTypes.SUCCESS,
        onPaymentSetup,
        paymentIntentData,
    ] );

    /**
     * Handle checkout success
     *
     * @see https://developer.woocommerce.com/docs/cart-and-checkout-payment-method-integration-for-the-checkout-block/#3-props-fed-to-payment-method-nodes
     * @see https://developer.woocommerce.com/docs/cart-and-checkout-payment-method-integration-for-the-checkout-block/#7-passing-values-from-the-client-to-the-server-side-payment-processing
     * @see https://developer.woocommerce.com/docs/cart-and-checkout-checkout-flow-and-events/
     * @see https://docs.stripe.com/payments/accept-a-payment?platform=web&ui=elements#web-submit-payment
     */
    useEffect( () => {
        const unsubscribe = onCheckoutSuccess(
            async ( checkoutSuccessResponse ) => {
                const { error } = await stripe.confirmPayment( {
                    elements,
                    confirmParams: {
                        payment_method_data: {
                            billing_details:
                                mapBillingAddress( billingAddress ),
                        },
                        return_url: checkoutSuccessResponse.redirectUrl,
                    },
                    redirect: 'always',
                } );

                if ( error ) {
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: error.message,
                    };
                }

                return {
                    type: emitResponse.responseTypes.SUCCESS,
                };
            }
        );

        return () => unsubscribe();
    }, [
        billingAddress,
        elements,
        emitResponse.responseTypes.ERROR,
        emitResponse.responseTypes.SUCCESS,
        onCheckoutSuccess,
        paymentIntentData.client_secret,
        paymentIntentData.id,
        stripe,
    ] );

    // @see https://docs.stripe.com/payments/payment-element
    // @see https://docs.stripe.com/sdks/stripejs-react#elements-provider
    return (
        <PaymentElement
            id="dokan-stripe-express-payment-element"
            options={ paymentElementOptions }
        />
    );
};

export default DefaultPaymentForm;
