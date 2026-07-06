import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { DefaultPaymentFormProps } from '../../../types';
import ExpressPaymentForm from '../../../shared-components/ExpressPaymentForm';
import React from 'react';

const DefaultExpressPaymentForm = ( props: DefaultPaymentFormProps ) => {
    const { eventRegistration, emitResponse, activePaymentMethod } = props;
    const { onPaymentSetup, onCheckoutSuccess, onCheckoutFail } =
        eventRegistration;

    // Handle payment setup for WooCommerce integration
    useEffect( () => {
        const unsubscribe = onPaymentSetup( async () => {
            if ( 'dokan_stripe_express_checkout' !== activePaymentMethod ) {
                return false;
            }

            const paymentIntentId =
                window.localStorage.getItem( 'payment_intent_id' );
            if ( ! paymentIntentId ) {
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: __( 'Payment intent data is missing.', 'dokan' ),
                };
            }

            window.localStorage.removeItem( 'payment_intent_id' );

            return {
                type: emitResponse.responseTypes.SUCCESS,
                meta: {
                    paymentMethodData: {
                        payment_intent_id: paymentIntentId,
                    },
                },
            };
        } );

        return () => unsubscribe();
    }, [
        activePaymentMethod,
        emitResponse.responseTypes.ERROR,
        emitResponse.responseTypes.SUCCESS,
        onPaymentSetup,
    ] );

    // Handle checkout success for WooCommerce integration
    useEffect( () => {
        const unsubscribe = onCheckoutSuccess( async () => {
            // Payment confirmed in onConfirm; return success
            return {
                type: emitResponse.responseTypes.SUCCESS,
            };
        } );

        return () => unsubscribe();
    }, [ emitResponse.responseTypes.SUCCESS, onCheckoutSuccess ] );

    useEffect( () => {
        if ( ! onCheckoutFail ) {
            return () => {};
        }
        const unsubscribe = onCheckoutFail( ( response ) => {
            // Support both paymentResult and processingResponse (different WooCommerce versions)
            const paymentData =
                response?.processingResponse ?? response?.paymentResult ?? {};
            const paymentDetails =
                paymentData?.payment_details ??
                paymentData?.paymentDetails ??
                [];

            // WooCommerce returns payment_details as array of {key, value}
            // or as object { messages: string } depending on version
            let errorMessage = '';
            if ( Array.isArray( paymentDetails ) ) {
                const messagesEntry = paymentDetails.find(
                    ( item: { key?: string; value?: string } ) =>
                        item?.key === 'messages'
                );
                errorMessage = messagesEntry?.value ?? '';
            } else if (
                typeof paymentDetails === 'object' &&
                paymentDetails !== null
            ) {
                errorMessage =
                    paymentDetails.messages || paymentDetails.message || '';
            }

            return {
                type: emitResponse.responseTypes.ERROR,
                message:
                    ( typeof errorMessage === 'string' ? errorMessage : '' ) ||
                    __( 'Payment failed. Please try again.', 'dokan' ),
                messageContext: emitResponse.noticeContexts?.EXPRESS_PAYMENTS,
            };
        } );

        return () => unsubscribe();
    }, [
        emitResponse.responseTypes.ERROR,
        emitResponse.noticeContexts,
        onCheckoutFail,
    ] );

    return <ExpressPaymentForm { ...props } />;
};

export default DefaultExpressPaymentForm;
