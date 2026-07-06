import React from 'react';
import {
    loadStripe,
    StripeElementLocale,
    type StripeElementsOptions,
} from '@stripe/stripe-js';
import { Elements } from '@stripe/react-stripe-js';
import { useMemo } from '@wordpress/element';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { getSetting } from '@woocommerce/settings';
import { DokanStripeExpressBlockData } from '../../types';
import ExpressPaymentForm from './Forms/DefaultExpressPaymentForm';

/**
 * Content component.
 *
 * @param props
 *
 * @see https://developer.woocommerce.com/docs/cart-and-checkout-payment-method-integration-for-the-checkout-block/#3-props-fed-to-payment-method-nodes
 */
const ExpressCheckout = ( props ) => {
    const settings: DokanStripeExpressBlockData = getSetting(
        'dokan_stripe_express_data'
    );

    const { publishable_key: publishableKey } = settings;

    const stripePromise = useMemo( () => {
        if ( ! publishableKey ) {
            return null;
        }
        return loadStripe( publishableKey );
    }, [ publishableKey ] );

    const elementOptions: StripeElementsOptions = {
        mode: 'payment',
        loader: 'auto',
        appearance: {
            theme: settings.element_theme as
                | 'stripe'
                | 'night'
                | 'flat'
                | undefined,
        },
        currency: props?.billing?.currency?.code?.toLowerCase() || 'usd',
        amount: props?.billing?.cartTotal?.value || 0,
        locale: settings.locale as StripeElementLocale,
    };

    /**
     * Render the payment form.
     *
     * @see https://github.com/woocommerce/woocommerce-gateway-stripe/blob/develop/client/blocks/credit-card/payment-method.js#L80
     * @see https://docs.stripe.com/payments/accept-a-payment?platform=web&ui=elements#set-up-stripe.js
     */
    return (
        <Elements stripe={ stripePromise } options={ elementOptions }>
            <ExpressPaymentForm settings={ settings } { ...props } />
        </Elements>
    );
};

export default ExpressCheckout;
