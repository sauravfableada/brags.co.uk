// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import $ from 'jquery';

import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Elements } from '@stripe/react-stripe-js';
import {
    loadStripe,
    type Stripe,
    type StripeElementLocale,
    type StripeElementsOptions,
} from '@stripe/stripe-js';

// Import API and utilities
import { createAPI } from '../../utils/api';
import { ExpressPaymentRequestProps } from '../../types';
import {
    displayError,
    blockUI,
    getProductId,
    getProductAttributes,
    getProductQuantity,
    getAddonData,
    getAddonValue,
} from '../../utils';
import ExpressPaymentForm from '../../shared-components/ExpressPaymentForm';
import ProductPageHandler from './ProductPageHandler';

const ExpressPaymentRequest = ( { settings }: ExpressPaymentRequestProps ) => {
    const [ stripe, setStripe ] = useState< Stripe | null >( null );
    const [ isLoading, setIsLoading ] = useState< boolean >( true );
    const [ error, setError ] = useState< string | null >( null );
    const [ cartData, setCartData ] = useState< any >( null );
    const [ productData, setProductData ] = useState< any >( null );

    const isProductPage = settings.isProductPage || false;

    // Create API instance
    const api = createAPI( settings.ajaxUrl );

    // Initialize Stripe and get initial data
    useEffect( () => {
        const initStripe = async () => {
            try {
                const stripeInstance = await loadStripe( settings.stripe.key, {
                    locale: settings.button.locale as StripeElementLocale,
                } );

                setStripe( stripeInstance );
                setIsLoading( false );
            } catch ( err ) {
                setError( __( 'Failed to load Stripe', 'dokan' ) );
                setIsLoading( false );
            }
        };

        void initStripe();
    }, [
        settings.stripe.key,
        settings.button.locale,
        isProductPage,
        settings.nonce.payment,
    ] );

    const getCartDetailsData = useCallback( async () => {
        try {
            const cartResponse = await api.getCartDetails(
                settings.nonce.payment
            );
            setCartData( cartResponse );
        } catch ( err ) {
            setError( __( 'Failed to get cart data', 'dokan' ) );
            // Cart data not available, will show error in render
        }
    }, [ api, settings.nonce.payment ] );

    // Calculate elementOptions dynamically without state
    const getElementOptions = useCallback( (): StripeElementsOptions => {
        if ( ! stripe ) {
            return {};
        }

        let amount = 0;
        let currency = settings.checkout?.currencyCode?.toLowerCase() || 'usd';

        if ( isProductPage && productData?.total ) {
            amount = productData.total.amount;
        } else if ( isProductPage && settings.product?.total ) {
            amount = settings.product.total.amount;
        } else if ( cartData?.order_data?.total ) {
            amount = cartData.order_data.total.amount;
            currency = cartData.order_data.currency.toLowerCase();
        }

        return {
            mode: 'payment',
            loader: 'auto',
            currency,
            amount,
            locale: settings.button.locale as StripeElementLocale,
        };
    }, [ stripe, settings, isProductPage, cartData, productData ] );

    // Get product attributes with variation info
    const getAttributes = useCallback( () => {
        const data = getProductAttributes();
        const select = $( '.variations_form' ).find( '.variations select' );
        const count = select.length;
        const chosen = select.filter( ( _, el ) => $( el ).val() ).length;

        return { count, chosenCount: chosen, data };
    }, [] );

    // Get attributes for API calls
    const getAttributesForAPI = useCallback( () => {
        return $( '.variations_form' ).length ? getAttributes().data : {};
    }, [ getAttributes ] );

    // Add to cart using centralized handler
    const addToCartProduct = useCallback( async () => {
        const productId = getProductId();
        const attributes = getAttributesForAPI();
        const quantity = getProductQuantity();
        const addonData = getAddonData();

        try {
            const response = await api.addToCart(
                settings.nonce.addToCart,
                productId,
                quantity,
                attributes,
                addonData
            );

            if ( ! response.is_success ) {
                throw new Error(
                    response.message || __( 'Failed to add to cart', 'dokan' )
                );
            }

            return response;
        } catch ( err ) {
            // Add to cart error handled
            throw err;
        }
    }, [ api, settings.nonce, getAttributesForAPI ] );

    // Get selected product data for variable products
    const getSelectedProductData = useCallback( async () => {
        const productId = getProductId();
        const addonValue = getAddonValue();
        const attributes = getAttributesForAPI();
        const quantity = getProductQuantity();

        try {
            const response = await api.getSelectedProductData(
                settings.nonce.getSelectedProductData,
                productId,
                quantity,
                attributes,
                addonValue
            );

            if ( ! response.is_success ) {
                throw new Error(
                    response.message ||
                        __( 'Failed to get product data', 'dokan' )
                );
            }

            return response;
        } catch ( err ) {
            // Get product data error handled
            throw err;
        } finally {
            $( '#dokan-stripe-express-payment-request-button' )
                .removeClass( [
                    'wc_request_button_is_blocked',
                    'wc_request_button_is_disabled',
                ] )
                .unblock();
        }
    }, [ api, settings.nonce, getAttributesForAPI ] );

    // Common handler for product data updates
    const updateProductData = useCallback( async () => {
        try {
            const selectedProductData = await getSelectedProductData();
            if ( selectedProductData.total ) {
                setProductData( selectedProductData );
            }
        } catch ( err ) {
            // Product data update error handled
        }
    }, [ getSelectedProductData ] );

    const handleVariationChange = useCallback( async () => {
        try {
            const selectedProductData = await getSelectedProductData();
            if ( selectedProductData.total ) {
                // Update product data to trigger re-render with new amount
                setProductData( selectedProductData );
            }
        } catch ( err ) {
            // Variation change error handled
        }
    }, [ getSelectedProductData ] );

    const handleQuantityChange = useCallback( async () => {
        try {
            const selectedProductData = await getSelectedProductData();
            if ( selectedProductData.total ) {
                // Update product data to trigger re-render with new amount
                setProductData( selectedProductData );
            }
        } catch ( err ) {
            // Quantity change error handled
        }
    }, [ getSelectedProductData ] );

    // Handle product variation changes (for product page)
    useEffect( () => {
        if ( ! isProductPage ) {
            return;
        }

        // Bind to WooCommerce events
        $( document.body ).on(
            'woocommerce_variation_has_changed',
            handleVariationChange
        );
        $( '.quantity' ).on( 'input', '.qty', handleQuantityChange );

        return () => {
            $( document.body ).off(
                'woocommerce_variation_has_changed',
                handleVariationChange
            );
            $( '.quantity' ).off( 'input', '.qty', handleQuantityChange );
        };
    }, [
        handleQuantityChange,
        handleVariationChange,
        isProductPage,
        updateProductData,
    ] );

    // Initialize Stripe and get initial data
    useEffect( () => {
        if ( isProductPage || cartData ) {
            return;
        }

        void getCartDetailsData();
    }, [
        api,
        cartData,
        getCartDetailsData,
        isProductPage,
        settings.nonce.payment,
    ] );

    // Handle cart updates (for cart page)
    useEffect( () => {
        if ( isProductPage ) {
            return;
        }

        $( document.body ).on(
            'updated_cart_totals updated_checkout',
            getCartDetailsData
        );

        return () => {
            $( document.body ).off(
                'updated_cart_totals updated_checkout',
                getCartDetailsData
            );
        };
    }, [ isProductPage, getCartDetailsData ] );

    // Render
    if ( error ) {
        $( '#dokan-stripe-express-payment-request-wrapper' ).show();
        return <div className="dokan-stripe-express-error">{ error }</div>;
    }

    if ( isLoading ) {
        $( '#dokan-stripe-express-payment-request-wrapper' ).show();
        return (
            <div className="dokan-stripe-express-loading">
                { __( 'Loading payment options…', 'dokan' ) }
            </div>
        );
    }

    // Calculate elementOptions for current render
    const elementOptions = getElementOptions();

    // Don't render if we don't have required data
    if ( ! isProductPage && cartData === null ) {
        return null;
    }

    // Show the buttons separator.
    $( '#dokan-stripe-express-payment-request-button-separator' ).show();

    return (
        <>
            <Elements stripe={ stripe } options={ elementOptions }>
                <ExpressPaymentForm
                    settings={ settings }
                    isProductPage={ isProductPage }
                    onAddToCart={ isProductPage ? addToCartProduct : undefined }
                    onError={ ( message: string ) =>
                        displayError( message, isProductPage )
                    }
                    onBlockUI={ () => blockUI() }
                    shippingData={ {
                        needsShipping:
                            settings?.product?.requestShipping ??
                            settings?.checkout?.shippingNeeded === 'yes' ??
                            false,
                    } }
                />
            </Elements>
            { isProductPage && (
                <ProductPageHandler
                    expressCheckoutElement={ null }
                    onAddToCart={ addToCartProduct }
                    onGetProductData={ getSelectedProductData }
                    settings={ settings }
                />
            ) }
        </>
    );
};

export default ExpressPaymentRequest;
