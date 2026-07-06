// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import $ from 'jquery';

import { useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import '../../../../../../src/definitions/window-types';
import { ProductPageHandlerProps } from '../../types';

const ProductPageHandler = ( {
    expressCheckoutElement,
    onAddToCart,
    onGetProductData,
    settings,
}: ProductPageHandlerProps ) => {
    // Handle payment request button click on product page
    const handleProductPageClick = useCallback(
        async ( event: Event ) => {
            // Check if login is required
            if ( settings.loginStatus ) {
                event.preventDefault();
                const message = settings.loginStatus.message.replace(
                    /\*\*/g,
                    ''
                );

                // Show login confirmation dialog using SweetAlert if available
                if ( typeof window.dokan_sweetalert === 'function' ) {
                    const result = await window.dokan_sweetalert( message, {
                        action: 'confirm',
                        icon: 'warning',
                        confirmButtonColor: '#363636',
                        cancelButtonColor: '#b54545',
                        confirmButtonText:
                            settings.i18n?.login || __( 'Login', 'dokan' ),
                        cancelButtonText:
                            settings.i18n?.cancel || __( 'Cancel', 'dokan' ),
                    } );

                    if ( result.isConfirmed ) {
                        window.location.href =
                            settings.loginStatus.redirect_url;
                    }
                }
                return;
            }

            const addToCartButton = $( '.single_add_to_cart_button' );

            // Check if product can be added to cart
            if ( addToCartButton.is( '.disabled' ) ) {
                event.preventDefault();

                if ( addToCartButton.is( '.wc-variation-is-unavailable' ) ) {
                    if ( typeof window.dokan_sweetalert === 'function' ) {
                        window.dokan_sweetalert(
                            settings.i18n?.productUnavailable ||
                                __( 'Product unavailable', 'dokan' ),
                            {
                                action: 'alert',
                                icon: 'warning',
                                timer: 2200,
                            }
                        );
                    }
                    return;
                }

                if ( addToCartButton.is( '.wc-variation-selection-needed' ) ) {
                    if ( typeof window.dokan_sweetalert === 'function' ) {
                        window.dokan_sweetalert(
                            settings.i18n?.makeSelection ||
                                __( 'Please make a selection', 'dokan' ),
                            {
                                action: 'alert',
                                icon: 'warning',
                                timer: 2200,
                            }
                        );
                    }
                    return;
                }
            }

            // Add to cart before showing payment request
            try {
                await onAddToCart();
            } catch ( error ) {
                event.preventDefault();
            }
        },
        [ settings, onAddToCart ]
    );

    // Handle variation changes
    const handleVariationChange = useCallback( async () => {
        if ( ! expressCheckoutElement ) {
            return;
        }

        // Block the express checkout button temporarily
        $( '#dokan-stripe-express-payment-request-button' )
            .addClass( 'wc_request_button_is_blocked' )
            .block( { message: null } );

        try {
            const productData = await onGetProductData();
            if ( productData && productData.total ) {
                // For Express Checkout Element, we don't need to update the element directly
                // The parent component handles amount updates via elements.update()
                // This handler is mainly for blocking/unblocking the UI
            }
        } catch ( error ) {
            console.error( 'Failed to update express checkout:', error );
        } finally {
            // Unblock the express checkout button
            $( '#dokan-stripe-express-payment-request-button' )
                .removeClass( [
                    'wc_request_button_is_blocked',
                    'wc_request_button_is_disabled',
                ] )
                .unblock();
        }
    }, [ expressCheckoutElement, onGetProductData ] );

    // Debounce function for quantity changes
    const debounce = useCallback( ( func: Function, wait: number ) => {
        let timeout: NodeJS.Timeout;
        return function executedFunction( ...args: any[] ) {
            const later = () => {
                clearTimeout( timeout );
                func( ...args );
            };
            clearTimeout( timeout );
            timeout = setTimeout( later, wait );
        };
    }, [] );

    // Handle quantity changes with debouncing
    const handleQuantityChange = useCallback(
        debounce( async () => {
            if ( ! expressCheckoutElement ) {
                return;
            }

            $( '#dokan-stripe-express-payment-request-button' )
                .addClass( 'wc_request_button_is_blocked' )
                .block( { message: null } );

            try {
                const productData = await onGetProductData();
                if ( productData && productData.total ) {
                    // For Express Checkout Element, we don't need to update the element directly
                    // The parent component handles amount updates via elements.update()
                    // This handler is mainly for blocking/unblocking the UI
                }
            } catch ( error ) {
                console.error(
                    'Failed to update express checkout for quantity change:',
                    error
                );
            } finally {
                $( '#dokan-stripe-express-payment-request-button' )
                    .removeClass( [
                        'wc_request_button_is_blocked',
                        'wc_request_button_is_disabled',
                    ] )
                    .unblock();
            }
        }, 250 ),
        [ expressCheckoutElement, onGetProductData, debounce ]
    );

    // Handle stock status for variations
    const handleVariationFound = useCallback(
        ( event: any, variation: any ) => {
            const wrapper = $(
                '#dokan-stripe-express-payment-request-wrapper'
            );
            const separator = $(
                '#dokan-stripe-express-payment-request-button-separator'
            );

            if ( variation.is_in_stock ) {
                // Show payment request button if variation is in stock
                wrapper.show();
                separator.show();
            } else {
                // Hide payment request button if variation is out of stock
                wrapper.hide();
                separator.hide();
            }
        },
        []
    );

    // Set up event listeners
    useEffect( () => {
        // Product page specific event listeners
        const expressButton = $(
            '#dokan-stripe-express-payment-request-button'
        );

        // Click handler for express checkout button
        expressButton.on( 'click', handleProductPageClick );

        // Variation change handler
        $( document.body ).on(
            'woocommerce_variation_has_changed',
            handleVariationChange
        );

        // Quantity change handlers
        $( '.quantity' ).on( 'input', '.qty', () => {
            $( '#dokan-stripe-express-payment-request-button' )
                .addClass( 'wc_request_button_is_blocked' )
                .block( { message: null } );
        } );

        $( '.quantity' ).on( 'input', '.qty', handleQuantityChange );

        // Variation found handler for variable products
        if ( $( '.variations_form' ).length ) {
            $( '.variations_form' ).on(
                'found_variation.wc-variation-form',
                handleVariationFound
            );
        }

        // Custom body events for blocking/unblocking button
        $( document.body ).on(
            'dokan_stripe_express_unblock_payment_request_button dokan_stripe_express_enable_payment_request_button',
            () => {
                $( '#dokan-stripe-express-payment-request-button' )
                    .removeClass( [
                        'wc_request_button_is_blocked',
                        'wc_request_button_is_disabled',
                    ] )
                    .unblock();
            }
        );

        $( document.body ).on(
            'dokan_stripe_express_block_payment_request_button',
            () => {
                $( '#dokan-stripe-express-payment-request-button' )
                    .addClass( 'wc_request_button_is_blocked' )
                    .block( { message: null } );
            }
        );

        $( document.body ).on(
            'dokan_stripe_express_disable_payment_request_button',
            () => {
                $( '#dokan-stripe-express-payment-request-button' )
                    .addClass( 'wc_request_button_is_disabled' )
                    .block( { message: null } );
            }
        );

        // Cleanup function
        return () => {
            expressButton.off( 'click', handleProductPageClick );
            $( document.body ).off(
                'woocommerce_variation_has_changed',
                handleVariationChange
            );
            $( '.quantity' ).off( 'input', '.qty' );
            $( '.variations_form' ).off(
                'found_variation.wc-variation-form',
                handleVariationFound
            );
            $( document.body ).off(
                'dokan_stripe_express_unblock_payment_request_button dokan_stripe_express_enable_payment_request_button'
            );
            $( document.body ).off(
                'dokan_stripe_express_block_payment_request_button'
            );
            $( document.body ).off(
                'dokan_stripe_express_disable_payment_request_button'
            );
        };
    }, [
        handleProductPageClick,
        handleVariationChange,
        handleQuantityChange,
        handleVariationFound,
    ] );

    return null; // This component doesn't render anything, it just handles events
};

export default ProductPageHandler;
