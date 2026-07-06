// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import $ from 'jquery';
import {
    type StripeExpressCheckoutElementOptions,
    type StripeExpressCheckoutElementConfirmEvent as ConfirmEvent,
} from '@stripe/stripe-js';

// Utility function to map billing address
import {
    BillingAddress,
    CheckoutBillingAddress,
    CheckoutShippingAddress,
    BillingData,
    PaymentRequestEventData,
    ShippingAddress,
    ProductAttributes,
    DokanStripeExpressPRData,
    DokanStripeExpressBlockData,
} from './types';
import { __ } from '@wordpress/i18n';

const mapBillingAddress = ( address: BillingAddress ): BillingData => {
    const ownerInfo: BillingData = {
        address: {
            line1: address.address_1,
            line2: address.address_2,
            city: address.city,
            state: address.state,
            postal_code: address.postcode,
            country: address.country,
        },
        phone: address.phone || 'undefined',
    };

    if ( address.phone ) {
        ownerInfo.phone = address.phone;
    }
    if ( address.email ) {
        ownerInfo.email = address.email;
    }
    if ( address.first_name || address.last_name ) {
        ownerInfo.name = `${ address.first_name } ${ address.last_name }`;
    }

    return ownerInfo;
};

const extractErrorMessage = ( message: string, errorPrefix = 'Error: ' ) => {
    if ( ! message ) {
        return '';
    }

    const errorIndex = message.indexOf( errorPrefix );
    if ( errorIndex === -1 ) {
        return message;
    }

    return message.substring( errorIndex + errorPrefix.length ).trim();
};

const stripHTMLTags = ( str: string ) => str.replace( /<[^>]*>/g, '' );

/**
 * Map Stripe Express Checkout address to uniform format
 *
 * @param address Stripe address object
 * @return Uniform address object
 */
const mapExpressCheckoutAddress = ( address: any ): ShippingAddress => {
    return {
        country: address.country ?? '',
        region: address.state ?? '',
        postalCode: address.postal_code ?? '',
        city: address.city ?? '',
        addressLine: [ address.line1 ?? '', address.line2 ?? '' ],
        recipient: address.name ?? '',
    };
};

/**
 * Create uniform event data for processPaymentResponse
 *
 * @param event               Stripe Express Checkout confirm event
 * @param paymentIntentId     Payment intent ID from confirmed payment
 * @param customer
 * @param customer.first_name
 * @param customer.last_name
 * @param customer.tax_id
 * @return Formatted event object
 */
const createUniformEventData = (
    event: ConfirmEvent,
    paymentIntentId: string,
    customer?: { first_name: string; last_name: string; tax_id?: string }
): PaymentRequestEventData => {
    const billingAddress: CheckoutBillingAddress = {
        billing_first_name:
            event.billingDetails?.name ?? customer?.first_name ?? '',
        billing_last_name:
            event.billingDetails?.name ?? customer?.last_name ?? '',
        billing_email: event.billingDetails?.email,
        billing_phone: event.billingDetails?.phone ?? '',
        billing_address_1: event.billingDetails?.address?.line1 ?? '',
        billing_address_2: event.billingDetails?.address?.line2 ?? '',
        billing_city: event.billingDetails?.address?.city ?? '',
        billing_state: event.billingDetails?.address?.state ?? '',
        billing_postcode: event.billingDetails?.address?.postal_code ?? '',
        billing_country: event.billingDetails?.address?.country ?? '',
    };

    const shippingAddress: CheckoutShippingAddress = {
        shipping_recipient:
            event.shippingAddress?.name ?? event.billingDetails?.name ?? '',
        shipping_country:
            event.shippingAddress?.address?.country ??
            event.billingDetails?.address?.country ??
            '',
        shipping_address_1:
            event.shippingAddress?.address?.line1 ??
            event.billingDetails?.address?.line1 ??
            '',
        shipping_address_2:
            event.shippingAddress?.address?.line2 ??
            event.billingDetails?.address?.line2 ??
            '',
        shipping_city:
            event.shippingAddress?.address?.city ??
            event.billingDetails?.address?.city ??
            '',
        shipping_state:
            event.shippingAddress?.address?.state ??
            event.billingDetails?.address?.state ??
            '',
        shipping_postcode:
            event.shippingAddress?.address?.postal_code ??
            event.billingDetails?.address?.postal_code ??
            '',
    };

    return {
        element_type: 'expressCheckout',
        express_payment_type: event.expressPaymentType,
        payment_intent_id: paymentIntentId,
        payment_method: 'dokan_stripe_express',
        ...billingAddress,
        ...shippingAddress,
        shipping_option: event.shippingRate ?? null,
        tax_id: customer?.tax_id ?? '',
    };
};

// Additional utility functions for checkout-classic compatibility
const getDokanStripeExpressData = () => window.dokanStripeExpress;

const getFontRulesFromPage = () => [];

const getAppearance = () =>
    getDokanStripeExpressData().appearance || { theme: 'stripe' };

// Centralized Express Checkout Element configuration
const getExpressCheckoutElementOptions = (
    settings: DokanStripeExpressPRData | DokanStripeExpressBlockData,
    needsShipping: boolean = false,
    needPhoneNumber: boolean = false
) =>
    ( {
        emailRequired: true,
        billingAddressRequired: true,
        phoneNumberRequired: needPhoneNumber,
        shippingAddressRequired: needsShipping,
        business: {
            name: settings.accountDescriptor ?? '',
        },
        paymentMethods: {
            amazonPay: 'auto',
            applePay: 'always',
            googlePay: 'always',
            link: 'auto',
            paypal: 'auto',
            klarna: 'auto',
        },
        layout: { overflow: 'never' },
        buttonType: {
            applePay: 'plain',
            googlePay: 'pay',
            paypal: 'pay',
            klarna: 'pay',
        },
    } ) as StripeExpressCheckoutElementOptions;

// Centralized error display utility
const displayError = ( message: string, isProductPage: boolean = false ) => {
    $( '.woocommerce-error' ).remove();

    const errorElement = $( '<div class="woocommerce-error" />' ).text(
        message
    );

    if ( isProductPage ) {
        const element = $( '.product' ).first();
        element.before( errorElement );
        $( 'html, body' ).animate(
            {
                scrollTop:
                    element.prev( '.woocommerce-error' ).offset()?.top || 0,
            },
            600
        );
    } else {
        const $form = $( '.shop_table.cart' ).closest( 'form' );
        if ( $form.length ) {
            $form.before( errorElement );
            $( 'html, body' ).animate(
                {
                    scrollTop:
                        $form.prev( '.woocommerce-error' ).offset()?.top || 0,
                },
                600
            );
        } else {
            // Fallback for checkout page
            const container = $( '.woocommerce-notices-wrapper' );
            if ( container.length ) {
                container.html(
                    `<ul class="woocommerce-error" role="alert"><li>${ message }</li></ul>`
                );
                container[ 0 ].scrollIntoView( { behavior: 'smooth' } );
            }
        }
    }
};

// Centralized UI blocking utility
const blockUI = () => {
    if ( typeof $.blockUI === 'function' ) {
        $.blockUI( {
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6,
            },
        } );
    }
};

// Subscription utility functions
const getSubscriptionProductId = (): string | null => {
    const element = document.getElementById(
        'dokan-stripe-express-subscription-product-id'
    ) as HTMLInputElement;
    return element?.value || null;
};

const handleEarlyRenewal = async (
    event: Event,
    maybeShowAuthenticationModal: () => Promise< void >
) => {
    event.preventDefault();
    const button = event.target as HTMLButtonElement;
    const url = button.getAttribute( 'href' )!;

    // Inline payment request handler
    try {
        const response = await fetch( url, { method: 'GET' } );
        const data = await response.json();
        if (
            data.dokan_stripe_express_sca_required &&
            maybeShowAuthenticationModal
        ) {
            await maybeShowAuthenticationModal();
        } else if ( data.redirect_url ) {
            window.location.href = data.redirect_url;
        }
        return data;
    } catch ( error ) {
        throw new Error(
            ( error instanceof Error ? error.message : undefined ) ||
                __(
                    'There was a problem processing the payment. Please check your email inbox and refresh the page to try again.',
                    'dokan'
                )
        );
    }
};

// Product page utility functions
const getProductId = (): string => {
    let productId = $( '.single_add_to_cart_button' ).val() as string;

    // Check if product is a variable product
    if ( $( '.single_variation_wrap' ).length ) {
        productId = $( '.single_variation_wrap' )
            .find( 'input[name="product_id"]' )
            .val() as string;
    }

    return productId;
};

const getProductAttributes = (): ProductAttributes => {
    if ( ! $( '.variations_form' ).length ) {
        return {};
    }

    const select = $( '.variations_form' ).find( '.variations select' );
    const data: ProductAttributes = {};

    select.each( function () {
        const attributeName =
            $( this ).data( 'attribute_name' ) || $( this ).attr( 'name' );
        const value = $( this ).val() || '';
        data[ attributeName ] = value;
    } );

    return data;
};

const getProductQuantity = (): string => {
    return $( '.quantity .qty' ).val() as string;
};

const getAddonData = (): Record< string, any > => {
    const formData = $( 'form.cart' ).serializeArray();
    const addonData: Record< string, any > = {};

    $.each( formData, function ( i, field ) {
        if ( /^addon-/.test( field.name ) ) {
            if ( /\[\]$/.test( field.name ) ) {
                const fieldName = field.name.substring(
                    0,
                    field.name.length - 2
                );
                if ( addonData[ fieldName ] ) {
                    addonData[ fieldName ].push( field.value );
                } else {
                    addonData[ fieldName ] = [ field.value ];
                }
            } else {
                addonData[ field.name ] = field.value;
            }
        }
    } );

    return addonData;
};

const getAddonValue = (): number => {
    const addons = $( '#product-addons-total' ).data( 'price_data' ) || [];
    return addons.reduce( ( sum: number, addon: any ) => sum + addon.cost, 0 );
};

// Event binding utilities
const bindJQueryEvent = (
    selector: string,
    event: string,
    handler: () => void
) => {
    $( selector ).on( event, handler );
};

const unbindJQueryEvent = (
    selector: string,
    event: string,
    handler: () => void,
    context?: any
) => {
    if ( context ) {
        $( context ).off( event, selector, handler );
    } else {
        $( selector ).off( event, handler );
    }
};

// DOM manipulation utilities
const showElement = ( selector: string ) => {
    $( selector ).show();
};

const hideElement = ( selector: string ) => {
    $( selector ).hide();
};

export {
    mapBillingAddress,
    extractErrorMessage,
    stripHTMLTags,
    mapExpressCheckoutAddress,
    createUniformEventData,
    getDokanStripeExpressData,
    getFontRulesFromPage,
    getAppearance,
    getExpressCheckoutElementOptions,
    displayError,
    blockUI,
    getSubscriptionProductId,
    handleEarlyRenewal,
    getProductId,
    getProductAttributes,
    getProductQuantity,
    getAddonData,
    getAddonValue,
    bindJQueryEvent,
    unbindJQueryEvent,
    showElement,
    hideElement,
};
