import {
    ExpressCheckoutElement,
    useElements,
    useStripe,
} from '@stripe/react-stripe-js';
import {
    type StripeExpressCheckoutElementConfirmEvent as ConfirmEvent,
    type StripeExpressCheckoutElementShippingAddressChangeEvent as ShippingAddressChangeEvent,
    type StripeExpressCheckoutElementShippingRateChangeEvent as ShippingRateChangeEvent,
} from '@stripe/stripe-js';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
// eslint-disable-next-line import/no-unresolved
import { Property } from 'csstype';

// Import API and utilities
import { createAPI } from '../utils/api';
import {
    DokanStripeExpressBlockData as ExpressCheckoutData,
    DokanStripeExpressPRData as ExpressClassicData,
    SharedExpressPaymentFormProps,
} from '../types';
import {
    mapBillingAddress,
    stripHTMLTags,
    mapExpressCheckoutAddress,
    createUniformEventData,
    getExpressCheckoutElementOptions,
} from '../utils';
import TaxInputForm from './TaxInputForm';

const ExpressPaymentForm = ( props: SharedExpressPaymentFormProps ) => {
    const stripe = useStripe();
    const elements = useElements();
    const [ taxId, setTaxId ] = useState< string | null >( null );

    // State for visibility animation and loading
    const [ visibility, setVisibility ] =
        useState< Property.Visibility >( 'hidden' );
    const [ isLoading, setIsLoading ] = useState< boolean >( false );

    // Determine which mode we're in based on props
    const isBlockContext = !! props.eventRegistration;

    let settings = props.settings;

    let ajaxUrl: string;
    let checkoutNonce: string;
    let wooCheckoutNonce: string;
    let isProductPage = false;

    if ( isBlockContext ) {
        settings = props.settings as ExpressCheckoutData;
        ajaxUrl = settings.ajax_url;
        checkoutNonce = settings?.checkout;
        wooCheckoutNonce = settings.woo_checkout;
    } else {
        settings = props.settings as ExpressClassicData;
        ajaxUrl = settings.ajaxUrl;
        checkoutNonce = settings.nonce.intent;
        wooCheckoutNonce = settings.nonce.checkout;
        isProductPage = props.isProductPage || false;
    }

    const isShippingNeeded = props?.shippingData?.needsShipping ?? false;

    // Create API instance
    const api = createAPI( ajaxUrl );

    // Error handling utility
    const handleError = ( message: string ) => {
        ( props.setExpressPaymentError || props.onError )?.( message );
        setIsLoading( false );
    };

    // Loading management
    const handleLoading = ( loading: boolean ) => {
        setIsLoading( loading );
        if ( loading && props.onBlockUI ) {
            props.onBlockUI();
        }
    };

    // Handle onReady event for visibility
    const onReady = ( { availablePaymentMethods } ) => {
        if ( availablePaymentMethods ) {
            setVisibility( 'initial' );
        }
    };

    // Handle shipping address change
    const onShippingAddressChange = async (
        event: ShippingAddressChangeEvent
    ) => {
        const { address, resolve, reject } = event;
        handleLoading( true );

        // Add to cart if on product page
        if ( isProductPage && props.onAddToCart ) {
            try {
                await props.onAddToCart();
            } catch ( error ) {
                handleError( __( 'Failed to add product to cart.', 'dokan' ) );
                return;
            }
        }

        try {
            const result = await api.updateShippingOptions(
                // @ts-ignore
                settings.nonce.shipping ?? settings.nonce.get_shipping_options,
                mapExpressCheckoutAddress( address ),
                'express_checkout',
                isProductPage
            );

            if ( ! result.is_success ) {
                handleError(
                    result.message || __( 'Invalid shipping address.', 'dokan' )
                );
                reject();
                return;
            }

            // Map shipping options to shipping rates format expected by Stripe
            const shippingRates =
                result.shipping_options?.map( ( option ) => ( {
                    id: option.id,
                    displayName: option.label,
                    amount: option.amount,
                } ) ) || [];

            if ( result.total?.amount ) {
                elements.update( { amount: result.total.amount } );
            }

            resolve( {
                shippingRates,
            } );
            handleLoading( false );
        } catch ( error ) {
            handleError( __( 'Failed to update shipping address.', 'dokan' ) );
            reject();
        }
    };

    // Handle shipping rate change
    const onShippingRateChange = async ( event: ShippingRateChangeEvent ) => {
        const { shippingRate, resolve, reject } = event;
        handleLoading( true );

        try {
            const result = await api.updateShippingDetails(
                // @ts-ignore
                settings.nonce?.updateShipping ??
                    settings.nonce.update_shipping_method,
                [ shippingRate.id ],
                'express_checkout',
                isProductPage
            );

            if ( ! result.is_success ) {
                handleError(
                    result.message ||
                        __( 'Failed to update shipping method.', 'dokan' )
                );
                reject();
                return;
            }

            if ( result.total?.amount ) {
                elements.update( { amount: result.total.amount } );
            }

            // Update resolved with new totals
            resolve( {
                lineItems: ( result.displayItems || [] ).map( ( item ) => ( {
                    name: item.label,
                    amount: item.amount,
                } ) ),
            } );
            handleLoading( false );
        } catch ( error ) {
            handleError( __( 'Failed to update shipping method.', 'dokan' ) );
            reject();
        }
    };

    // Handle onConfirm event to create PaymentIntent and confirm payment
    const onConfirm = async ( event: ConfirmEvent ) => {
        if ( ! stripe || ! elements ) {
            handleError( __( 'Stripe is not loaded.', 'dokan' ) );
            return;
        }

        // Add to cart if on product page
        if ( isProductPage && props.onAddToCart && ! isShippingNeeded ) {
            try {
                const cartResult = await props.onAddToCart();
                if ( cartResult?.total?.amount ) {
                    elements.update( { amount: cartResult.total.amount } );
                }
            } catch ( error ) {
                handleError( __( 'Failed to add product to cart.', 'dokan' ) );
                return;
            }
        }

        handleLoading( true );

        const { error: submitError } = await elements.submit();
        if ( submitError ) {
            handleError(
                submitError.message ||
                    __( 'Payment submission failed.', 'dokan' )
            );
            return;
        }

        // Create PaymentIntent on the server using our handler
        try {
            const result = await api.createPaymentIntent( checkoutNonce );
            if ( ! result.is_success || ! result.clientSecret ) {
                handleError(
                    result.message ||
                        __( 'Failed to create PaymentIntent.', 'dokan' )
                );
                return;
            }

            const clientSecret = result.clientSecret;
            const paymentIntentId = result.paymentIntentData?.id;

            try {
                // Create properly formatted event data using uniform utility
                const formattedEvent = createUniformEventData(
                    event,
                    paymentIntentId,
                    // @ts-ignore
                    settings?.customer
                        ? { ...settings?.customer, tax_id: taxId }
                        : null
                );

                // Create order using the processPaymentResponse handler
                const orderResult = await api.processPaymentResponse(
                    checkoutNonce,
                    wooCheckoutNonce,
                    formattedEvent,
                    event.expressPaymentType
                );

                if (
                    orderResult.is_success &&
                    orderResult.result === 'success'
                ) {
                    // Order created successfully, complete the payment
                    handleLoading( false );

                    // Confirm the PaymentIntent
                    const { error } = await stripe.confirmPayment( {
                        elements,
                        clientSecret,
                        confirmParams: {
                            return_url: orderResult.redirect,
                            ...( props.billing?.billingAddress && {
                                payment_method_data: {
                                    billing_details: mapBillingAddress(
                                        props.billing.billingAddress
                                    ),
                                },
                            } ),
                        },
                        redirect: 'always',
                    } );

                    if ( error ) {
                        handleError(
                            error.message ||
                                __( 'Payment confirmation failed.', 'dokan' )
                        );
                        console.error( 'paymentError', error );
                        return;
                    }

                    // Redirect for classic context
                    if ( ! isBlockContext && orderResult.redirect ) {
                        window.location.href = orderResult.redirect;
                    }
                } else {
                    console.error( 'orderResult', orderResult );
                    const errorMsg =
                        orderResult.messages ||
                        orderResult.message ||
                        __( 'Failed to create order.', 'dokan' );
                    handleError(
                        isBlockContext ? stripHTMLTags( errorMsg ) : errorMsg
                    );
                    return;
                }
            } catch ( orderError ) {
                handleError(
                    __( 'Failed to create order after payment.', 'dokan' )
                );
                console.error( 'orderError', orderError );
                return;
            }

            handleLoading( false );
        } catch ( error ) {
            handleError( __( 'Error creating PaymentIntent.', 'dokan' ) );
        }
    };

    // Handle load errors
    const onLoadError = () => {
        handleError(
            __( 'Failed to load Express Checkout Element.', 'dokan' )
        );
    };

    // Handle cancellation
    const onCancel = () => {
        // For classic context, just reset loading state
        setIsLoading( false );
        handleLoading( false );
    };

    // Ensure stripe and elements are loaded before rendering
    if ( ! stripe || ! elements ) {
        return null;
    }

    // Configure Express Checkout Element options
    const expressCheckoutOptions = getExpressCheckoutElementOptions(
        settings,
        isShippingNeeded,
        isShippingNeeded
    );

    const taxRequired = settings.euCompliance.needTaxId;

    const renderExpressEl = () => {
        if ( taxRequired && ( taxId === null || taxId === '' ) ) {
            return null;
        }
        return (
            <ExpressCheckoutElement
                id="dokan-stripe-express-checkout-element"
                options={ expressCheckoutOptions }
                onReady={ onReady }
                onConfirm={ onConfirm }
                onLoadError={ onLoadError }
                onShippingAddressChange={ onShippingAddressChange }
                onShippingRateChange={ onShippingRateChange }
                onCancel={ onCancel }
            />
        );
    };

    // Block context rendering with loading overlay
    if ( isBlockContext ) {
        return (
            <div
                style={ {
                    visibility: taxRequired ? 'initial' : visibility,
                    position: 'relative',
                    maxHeight: 'fit-content',
                    height: '100%',
                    transition: 'all 0.2s ease-in-out',
                    maxWidth: '100%',
                } }
            >
                { isLoading && (
                    <div
                        style={ {
                            position: 'absolute',
                            top: 0,
                            left: 0,
                            right: 0,
                            bottom: 0,
                            background: 'rgba(255, 255, 255, 0.7)',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                        } }
                    >
                        <span>{ __( 'Loading…', 'dokan' ) }</span>
                    </div>
                ) }
                { settings.euCompliance.needTaxId && (
                    <TaxInputForm setTaxId={ setTaxId } settings={ settings } />
                ) }
                { renderExpressEl() }
            </div>
        );
    }

    // Classic context rendering (simpler)
    return (
        <div
            style={ {
                maxWidth: '100%',
                height: '100%',
                transition: 'all 0.2s ease-in-out',
            } }
        >
            { settings.euCompliance.needTaxId && (
                <TaxInputForm setTaxId={ setTaxId } settings={ settings } />
            ) }
            { renderExpressEl() }
        </div>
    );
};

export default ExpressPaymentForm;
