import {
    PaymentElement,
    useElements,
    useStripe,
} from '@stripe/react-stripe-js';
import {
    type ConfirmPaymentData,
    type StripeElements,
    type StripePaymentElementOptions,
} from '@stripe/stripe-js';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    CheckoutSuccessResponse,
    SubscriptionPaymentFormProps,
} from '../../../types';
import { mapBillingAddress } from '../../../utils';

const SubscriptionPaymentForm = ( props: SubscriptionPaymentFormProps ) => {
    const {
        eventRegistration,
        emitResponse,
        paymentIntentData,
        billing,
        subscriptionId,
        activePaymentMethod,
    } = props;

    const { onPaymentSetup, onCheckoutSuccess } = eventRegistration;
    const { billingAddress } = billing;

    const stripe = useStripe();
    const elements = useElements();

    // @see https://docs.stripe.com/js/elements_object/create#stripe_elements-options
    const paymentElementOptions: StripePaymentElementOptions = {
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
            if ( 'dokan_stripe_express' !== activePaymentMethod ) {
                return false;
            }
            if ( paymentIntentData ) {
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: {
                            payment_intent_id: paymentIntentData.id,
                            subscription_id: subscriptionId,
                            // dokan_stripe_express_payment_type: '', // note: previously set to selected payment method
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
        activePaymentMethod,
        emitResponse.responseTypes.ERROR,
        emitResponse.responseTypes.SUCCESS,
        onPaymentSetup,
        paymentIntentData,
        subscriptionId,
    ] );

    /**
     * Handle checkout success
     *
     * @see https://developer.woocommerce.com/docs/cart-and-checkout-payment-method-integration-for-the-checkout-block/#3-props-fed-to-payment-method-nodes
     * @see https://developer.woocommerce.com/docs/cart-and-checkout-payment-method-integration-for-the-checkout-block/#7-passing-values-from-the-client-to-the-server-side-payment-processing
     * @see https://developer.woocommerce.com/docs/cart-and-checkout-checkout-flow-and-events/
     * @see https://docs.stripe.com/sdks/stripejs-react#usestripe-hook
     */
    useEffect( () => {
        const unsubscribe = onCheckoutSuccess(
            async ( checkoutSuccessResponse: CheckoutSuccessResponse ) => {
                const confirmationConfig: {
                    elements: StripeElements;
                    confirmParams?: Partial< ConfirmPaymentData >;
                    redirect: 'if_required';
                } = {
                    elements,
                    confirmParams: {
                        payment_method_data: {
                            billing_details:
                                mapBillingAddress( billingAddress ),
                        },
                        return_url: checkoutSuccessResponse.redirectUrl,
                    },
                    redirect: 'if_required',
                };

                const isPaymentNeed =
                    checkoutSuccessResponse?.payment_needed ??
                    checkoutSuccessResponse?.processingResponse?.paymentDetails
                        ?.payment_needed ??
                    true;

                const { error } = isPaymentNeed
                    ? await stripe.confirmPayment( confirmationConfig )
                    : await stripe.confirmSetup( confirmationConfig );

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
        stripe,
    ] );

    // @see https://docs.stripe.com/billing/subscriptions/build-subscriptions?platform=web&ui=elements
    // @see https://docs.stripe.com/payments/payment-element/migration
    // @see https://docs.stripe.com/sdks/stripejs-react#elements-provider
    return (
        <PaymentElement
            id="dokan-stripe-express-payment-element"
            options={ paymentElementOptions }
        />
    );
};

export default SubscriptionPaymentForm;
