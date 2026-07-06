import { useState, useEffect } from '@wordpress/element';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { getSetting } from '@woocommerce/settings';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { PaymentMethodInterface } from '@woocommerce/type-defs';
import { __ } from '@wordpress/i18n';
import { createAPI } from '../../utils/api';
import { extractErrorMessage } from '../../utils';
import { DokanStripeExpressBlockData } from '../../types';
import React from 'react';
import { isRecurringSubscription, isSubscription } from './PaymentElements';
/**
 * Content shown when a saved payment method (saved card) is selected in block checkout.
 * Initializes the subscription when the cart has a recurring subscription so that
 * the server can process the order with process_subscription_with_saved_payment_method.
 *
 * @see https://developer.woocommerce.com/docs/cart-and-checkout-payment-method-integration-for-the-checkout-block/
 */
const SavedCardContent = ( props: PaymentMethodInterface ) => {
    const { cartData, components, activePaymentMethod } = props;
    const { PaymentMethodLabel, ValidationInputError, LoadingMask } = components;

    const [ subscriptionId, setSubscriptionId ] = useState<string>( '' );
    const [ error, setError ] = useState<string | null>( null );

    const settings: DokanStripeExpressBlockData = getSetting( 'dokan_stripe_express_data' );
    const { checkout: checkoutNonce, ajax_url: ajaxUrl } = settings;
    const api = createAPI( ajaxUrl );

    const hasSubscription = cartData?.cartItems?.some( isRecurringSubscription );
    const subscriptionProduct = cartData?.cartItems?.find( isSubscription );
    const productId = subscriptionProduct?.id?.toString();

    // Initialize subscription when cart has recurring subscription (saved card selected).
    useEffect( () => {
        if ( ! hasSubscription || ! productId || activePaymentMethod !== 'dokan_stripe_express' ) {
            return;
        }

        const initializeSubscription = async () => {
            const subscriptionResponse = await api.createSubscription( checkoutNonce, productId );
            if ( ! subscriptionResponse.is_success ) {
                setError( subscriptionResponse.message || null );
                return;
            }
            setSubscriptionId( subscriptionResponse.subscriptionId || '' );
        };

        void initializeSubscription();
    }, [ ajaxUrl, checkoutNonce, hasSubscription, productId, activePaymentMethod ] );

    if ( error ) {
        const errorMessage = extractErrorMessage( error );
        return (
            <ValidationInputError
                errorMessage={ __( 'Error:', 'dokan' ) + errorMessage }
                propertyName=""
                elementId=""
            />
        );
    }

    if ( hasSubscription && ! subscriptionId && LoadingMask ) {
        return <LoadingMask showSpinner={ true } isLoading={ true } />;
    }

    const labelText = settings?.title ?? __( 'Stripe Express', 'dokan' );
    return <PaymentMethodLabel text={ labelText } />;
};

export default SavedCardContent;
