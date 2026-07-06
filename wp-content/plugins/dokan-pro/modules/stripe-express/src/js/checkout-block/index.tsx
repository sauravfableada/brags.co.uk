import {
    registerPaymentMethod,
    registerExpressPaymentMethod,
    // @ts-ignore
    // eslint-disable-next-line import/no-unresolved
} from '@woocommerce/blocks-registry';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { getSetting } from '@woocommerce/settings';
import { __ } from '@wordpress/i18n';

import PaymentElements from './components/PaymentElements';
import SavedCardContent from './components/SavedCardContent';
import ExpressCheckout from './components/ExpressCheckout';
import '../../scss/checkout-common.scss';
import React from 'react';

const settings = getSetting( 'dokan_stripe_express_data' );

const Label = ( props: any ) => {
    const { PaymentMethodLabel } = props.components;

    const labelText = settings?.title ?? __( 'Stripe Express', 'dokan' );
    return <PaymentMethodLabel text={ labelText } />;
};

const EditContent = () => {
    return (
        <>
            <h4>{ settings?.title }</h4>
            <p>{ settings?.description }</p>
        </>
    );
};

const PaymentMethodsOptions = {
    name: settings?.id ?? 'dokan_stripe_express',
    gatewayId: settings?.id ?? 'dokan_stripe_express',
    paymentMethodId: settings?.id ?? 'dokan_stripe_express',
    title: settings?.title ?? __( 'Dokan Stripe Express', 'dokan' ),
    label: <Label />,
    savedTokenComponent: <SavedCardContent />,
    content: <PaymentElements />,
    edit: <EditContent />,
    canMakePayment: () => true,
    ariaLabel: __( 'Dokan Stripe Express', 'dokan' ),
    supports: {
        showSaveOption: settings?.show_save_option ?? false,
        features: settings?.supports ?? [],
        style: settings?.style ?? [],
    },
};

const ExpressPaymentMethodsOptions = {
    ...PaymentMethodsOptions,
    name: 'dokan_stripe_express_checkout',
    paymentMethodId: 'dokan_stripe_express_checkout',
    title: settings?.title ?? __( 'Dokan Stripe Express', 'dokan' ),
    content: <ExpressCheckout />,
    supports: {
        features: settings?.supports ?? [],
        style: settings?.style ?? [],
    },
};

/**
 * Register the payment method.
 *
 * @see https://developer.woocommerce.com/docs/cart-and-checkout-payment-method-integration-for-the-checkout-block/#0-client-side-integration
 * @see https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/internal-developers/block-client-apis/checkout/checkout-api.md#usepaymentmethodinterface
 * @see https://developer.woocommerce.com/docs/block-development/cart-and-checkout-blocks/checkout-payment-methods/payment-method-integration/#client-side-integration
 * @see https://developer.woocommerce.com/docs/block-development/cart-and-checkout-blocks/checkout-payment-methods/payment-method-integration/
 */
registerPaymentMethod( PaymentMethodsOptions );
registerExpressPaymentMethod( ExpressPaymentMethodsOptions );

// @see https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/blocks/docs/third-party-developers/extensibility/data-store/payment.md
// @see https://github.com/woocommerce/woocommerce/tree/trunk/plugins/woocommerce/client/blocks/assets/js/data/payment
// @see https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/blocks/assets/js/data/payment/default-state.ts
