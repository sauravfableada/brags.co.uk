import { useState, useEffect, useMemo } from '@wordpress/element';
import { loadStripe, StripeElementsOptions } from '@stripe/stripe-js';
import { Elements } from '@stripe/react-stripe-js';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { getSetting } from '@woocommerce/settings';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { PaymentMethodInterface } from '@woocommerce/type-defs';
import { createAPI } from '../../utils/api';
import DefaultPaymentForm from './Forms/DefaultPaymentForm';
import SubscriptionPaymentForm from './Forms/SubscriptionPaymentForm';
import PaymentDescription from './Description';
import { extractErrorMessage } from '../../utils';
import { __ } from '@wordpress/i18n';
import React from 'react';

import { ProductItem, ContentState, DokanStripeExpressBlockData } from '../../types';

const initialState: ContentState = {
    error: null,
    subscriptionId: '',
    PI: null,
    isLoading: true,
};

export type SubscriptionItemType = {
    extensions: {
        dokan_stripe_express: {
            subscription: {
                recurring: boolean
            }
        }
    }
} & ProductItem;

export const isRecurringSubscription = (item: SubscriptionItemType) => {
    return item.extensions.dokan_stripe_express.subscription.recurring;
};

export const isSubscription = ( item: ProductItem ) => item.type === 'product_pack';

/**
 * Content component.
 *
 * @see https://developer.woocommerce.com/docs/cart-and-checkout-payment-method-integration-for-the-checkout-block/#3-props-fed-to-payment-method-nodes
 *
 * @param root0
 * @param root0.cartData
 * @param root0.components
 */
const PaymentElements = ( {
    cartData,
    components,
    ...props
}: PaymentMethodInterface ) => {
    const [ state, setState ] = useState< ContentState >( initialState );

    const settings: DokanStripeExpressBlockData = getSetting( 'dokan_stripe_express_data' );

    const { publishable_key: publishableKey } = settings;
    const { checkout: checkoutNonce, ajax_url: ajaxUrl } = settings;

    // Create API instance
    const api = createAPI( ajaxUrl );
    const { LoadingMask, ValidationInputError } = components;

    const stripePromise = useMemo(
        () => {
            if ( ! publishableKey ) {
                return null;
            }
            return loadStripe( publishableKey );
        },
        [ publishableKey ]
    );

    const hasSubscription = cartData?.cartItems.some( isRecurringSubscription );
    const subscriptionProduct = cartData?.cartItems.find( isSubscription );
    const productId = subscriptionProduct?.id.toString();

    useEffect( () => {
        const initializeIntent = async () => {
            if ( hasSubscription ) {
                return;
            }

            const intentResponse = await api.createPaymentIntent( checkoutNonce );
            if ( ! intentResponse.is_success ) {
                setState( ( prevState ) => ( {
                    ...prevState,
                    error: intentResponse.message || null,
                    isLoading: false,
                } ) );
                return;
            }

            setState( ( prevState ) => ( {
                ...prevState,
                PI: intentResponse.paymentIntentData,
                isLoading: false,
            } ) );
        };

        void initializeIntent();
    }, [ ajaxUrl, checkoutNonce, hasSubscription ] );

    useEffect( () => {
        const initializeSubscription = async () => {
            if ( ! hasSubscription || ! productId ) {
                return;
            }

            const subscriptionResponse = await api.createSubscription( checkoutNonce, productId );
            if ( ! subscriptionResponse.is_success ) {
                setState( ( prevState ) => ( {
                    ...prevState,
                    error: subscriptionResponse.message || null,
                    isLoading: false,
                } ) );
                return;
            }

            setState( ( prevState ) => ( {
                ...prevState,
                subscriptionId: subscriptionResponse.subscriptionId || '',
                PI: subscriptionResponse.paymentIntentData,
                isLoading: false,
            } ) );
        };

        void initializeSubscription();
    }, [ ajaxUrl, checkoutNonce, hasSubscription, productId ] );

    if ( state.error ) {
        const errorMessage = extractErrorMessage( state.error );

        return (
            <ValidationInputError
                errorMessage={ __( 'Error:', 'dokan' ) + errorMessage }
                propertyName=""
                elementId=""
            />
        );
    }

    if (
        ! ( state.PI && state.PI.client_secret ) ||
        ( hasSubscription && ! state.subscriptionId )
    ) {
        return (
            <LoadingMask showSpinner={ true } isLoading={ state.isLoading } />
        );
    }

    const elementOptions: StripeElementsOptions = {
        appearance: {
            theme: settings.element_theme as 'stripe' | 'night' | 'flat',
        },
        loader: 'auto',
        clientSecret: state.PI.client_secret,
    };

    /**
     * Render the payment form.
     *
     * @see https://github.com/woocommerce/woocommerce-gateway-stripe/blob/develop/client/blocks/credit-card/payment-method.js#L80
     * @see https://docs.stripe.com/payments/accept-a-payment?platform=web&ui=elements#set-up-stripe.js
     */
    return (
        <div className="dokan-stripe-express-payment-method-root dokan-stripe-express-payment-element">
            <PaymentDescription
                isTestMode={ settings.testmode }
                description={ settings.description }
            />
            <Elements stripe={ stripePromise } options={ elementOptions }>
                { hasSubscription ? (
                    <SubscriptionPaymentForm
                        subscriptionId={ state.subscriptionId }
                        paymentIntentData={ state.PI }
                        { ...props }
                    />
                ) : (
                    <DefaultPaymentForm
                        paymentIntentData={ state.PI }
                        { ...props }
                    />
                ) }
            </Elements>
        </div>
    );
};

export default PaymentElements;
