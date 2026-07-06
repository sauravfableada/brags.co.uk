import apiFetch from '@wordpress/api-fetch';
import {
    CreatePaymentIntentResponse,
    CreatePaymentIntentApiResponse,
    CreateSubscriptionResponse,
    CreateSubscriptionApiResponse,
    AddToCartResponse,
    AddToCartApiResponse,
    CartDetailsResponse,
    CartDetailsApiResponse,
    GetSelectedProductDataResponse,
    GetSelectedProductDataApiResponse,
    UpdateShippingOptionsResponse,
    UpdateShippingOptionsApiResponse,
    UpdateShippingDetailsResponse,
    UpdateShippingDetailsApiResponse,
    ProcessPaymentResponseResponse,
    ProcessPaymentResponseApiResponse,
    PaymentRequestEventData,
    ProductAttributes,
    ShippingAddress,
} from '../types';

/**
 * Centralized API utility for Dokan Stripe Express
 */
export class DokanStripeExpressAPI {
    private ajaxUrl: string;

    constructor( ajaxUrl: string ) {
        this.ajaxUrl = ajaxUrl;
    }

    /**
     * Generic API call method
     * @param action The WordPress AJAX action
     * @param data   Additional form data
     * @return Promise with response data
     */
    async call< T = any >(
        action: string,
        data: Record< string, any > = {}
    ): Promise< T > {
        const formData = new FormData();
        formData.append( 'action', `dokan_stripe_express_${ action }` );

        // Add all data to FormData
        Object.entries( data ).forEach( ( [ key, value ] ) => {
            if ( Array.isArray( value ) ) {
                value.forEach( ( item, index ) => {
                    formData.append( `${ key }[${ index }]`, item );
                } );
            } else if ( typeof value === 'object' && value !== null ) {
                Object.entries( value ).forEach( ( [ subKey, subValue ] ) => {
                    formData.append(
                        `${ key }[${ subKey }]`,
                        String( subValue )
                    );
                } );
            } else {
                formData.append( key, String( value ) );
            }
        } );

        return apiFetch< T >( {
            url: this.ajaxUrl.replace(
                '%%endpoint%%',
                action === 'process_checkout' ? 'checkout' : `dokan_stripe_express_${ action }`
            ),
            method: 'POST',
            body: formData,
        } );
    }

    /**
     * Common API endpoints with response transformation
     * @param nonce
     * @param orderId
     */
    async createPaymentIntent(
        nonce: string,
        orderId?: string
    ): Promise< CreatePaymentIntentResponse > {
        const data: Record< string, any > = { _ajax_nonce: nonce };
        if ( orderId ) {
            data.order_id = orderId;
        }

        const response = await this.call< CreatePaymentIntentApiResponse >(
            'create_payment_intent',
            data
        );

        if ( response.success ) {
            return {
                is_success: response.success,
                clientSecret: response.data.client_secret,
                paymentIntentData: response.data,
            };
        }

        return {
            is_success: response.success,
            message: response.data.error?.message,
        };
    }

    async createSetupIntent( nonce: string ) {
        return this.call( 'init_setup_intent', { _ajax_nonce: nonce } );
    }

    async createSubscription(
        nonce: string,
        productId: string
    ): Promise< CreateSubscriptionResponse > {
        const response = await this.call< CreateSubscriptionApiResponse >(
            'create_subscription',
            {
                _ajax_nonce: nonce,
                product_id: productId,
            }
        );

        if ( response.success ) {
            return {
                is_success: response.success,
                subscriptionId: response.data.subscription_id,
                clientSecret: response.data.client_secret,
                paymentIntentData: {
                    id: response.data.id,
                    client_secret: response.data.client_secret,
                },
            };
        }

        return {
            is_success: response.success,
            message: response.data.error?.message,
        };
    }

    async addToCart(
        security: string,
        productId: string,
        quantity: string,
        attributes: ProductAttributes = {},
        addonData: Record< string, any > = {}
    ): Promise< AddToCartResponse > {
        const response = await this.call< AddToCartApiResponse >(
            'add_to_cart',
            {
                security,
                product_id: productId,
                qty: quantity,
                attributes,
                ...addonData,
            }
        );

        if ( response.result === 'success' ) {
            return {
                is_success: true,
                displayItems: response.displayItems,
                total: response.total,
            };
        }

        return {
            is_success: false,
            message: response.messages,
        };
    }

    async getCartDetails( nonce: string ): Promise< CartDetailsResponse > {
        const response = await this.call< CartDetailsApiResponse >(
            'get_cart_details',
            { security: nonce }
        );

        if ( response.order_data ) {
            return {
                is_success: true,
                order_data: response.order_data,
                shipping_required: response.shipping_required,
            };
        }

        return {
            is_success: false,
            message: response?.error?.message,
        };
    }

    async getSelectedProductData(
        nonce: string,
        productId: string,
        quantity: string,
        attributes: ProductAttributes,
        addonValue: number
    ): Promise< GetSelectedProductDataResponse > {
        const response = await this.call< GetSelectedProductDataApiResponse >(
            'get_selected_product_data',
            {
                security: nonce,
                product_id: productId,
                qty: quantity,
                attributes,
                addon_value: addonValue,
            }
        );

        if ( response.success && ! ( 'error' in response.data ) ) {
            return {
                is_success: true,
                total: response.data.total,
                displayItems: response.data.displayItems,
            };
        }

        return {
            is_success: false,
            message:
                'error' in response.data
                    ? response.data.error.message
                    : 'Failed to get product data',
        };
    }

    async updateShippingOptions(
        nonce: string,
        address: ShippingAddress,
        paymentRequestType: string,
        isProductPage: boolean
    ): Promise< UpdateShippingOptionsResponse > {
        const response = await this.call< UpdateShippingOptionsApiResponse >(
            'get_shipping_options',
            {
                security: nonce,
                country: address.country,
                state: address.region,
                postcode: address.postalCode,
                city: address.city,
                address: address.addressLine?.[ 0 ] || '',
                address_2: address.addressLine?.[ 1 ] || '',
                payment_request_type: paymentRequestType,
                is_product_page: isProductPage.toString(),
            }
        );

        if ( response.result === 'success' ) {
            return {
                is_success: true,
                result: response.result,
                shipping_options: response.shipping_options,
                total: response.total,
                displayItems: response.displayItems,
            };
        }

        return {
            is_success: false,
            message:
                response.error?.message || 'Failed to update shipping options',
        };
    }

    async updateShippingDetails(
        nonce: string,
        shippingMethods: string[],
        paymentRequestType: string,
        isProductPage: boolean
    ): Promise< UpdateShippingDetailsResponse > {
        const response = await this.call< UpdateShippingDetailsApiResponse >(
            'update_shipping_method',
            {
                security: nonce,
                shipping_method: shippingMethods,
                payment_request_type: paymentRequestType,
                is_product_page: isProductPage.toString(),
            }
        );

        if ( response.result === 'success' ) {
            return {
                is_success: true,
                result: response.result,
                total: response.total,
                displayItems: response.displayItems,
            };
        }

        return {
            is_success: false,
            message:
                response.error?.message || 'Failed to update shipping details',
        };
    }

    async processPaymentResponse(
        intentNonce: string,
        checkoutNonce: string,
        eventData: PaymentRequestEventData,
        expressPaymentType: string
    ): Promise< ProcessPaymentResponseResponse > {
        const response = await this.call< ProcessPaymentResponseApiResponse >(
            'create_order',
            {
                _ajax_nonce: intentNonce,
                _wpnonce: checkoutNonce,
                payment_request_type: expressPaymentType,
                ...eventData,
            }
        );

        return {
            is_success: response.result === 'success',
            result: response.result,
            redirect: response.redirect,
            messages: response.messages,
            message: response.message,
        };
    }

    async confirmIntent(
        nonce: string,
        returnUrl: string,
        paymentMethod?: string
    ) {
        const data: Record< string, any > = {
            _ajax_nonce: nonce,
            return_url: returnUrl,
        };
        if ( paymentMethod ) {
            data.payment_method = paymentMethod;
        }
        return this.call( 'confirm_intent', data );
    }

    async updateIntent(
        nonce: string,
        paymentIntentId: string,
        orderId: string | null,
        savePaymentMethod: string,
        paymentType?: string | null
    ) {
        // Don't update setup intents
        if ( paymentIntentId.includes( 'seti_' ) ) {
            return;
        }

        const data: Record< string, any > = {
            _wpnonce: nonce,
            payment_intent_id: paymentIntentId,
            save_payment_method: savePaymentMethod,
        };

        if ( orderId ) {
            data.order_id = orderId;
        }
        if ( paymentType ) {
            data.payment_type = paymentType;
        }

        return this.call( 'update_payment_intent', data );
    }

    async updateFailedOrder(
        nonce: string,
        paymentIntentId: string,
        orderId: string
    ) {
        return this.call( 'update_failed_order', {
            _wpnonce: nonce,
            payment_intent_id: paymentIntentId,
            order_id: orderId,
        } );
    }

    async processCheckout(
        nonce: string,
        paymentIntentId: string,
        subscriptionId: string,
        formFields: Record< string, any >
    ) {
        return this.call( 'process_checkout', {
            _wpnonce: nonce,
            payment_intent_id: paymentIntentId,
            subscription_id: subscriptionId,
            ...formFields,
        } );
    }
}

// Create a singleton instance with factory function
export const createAPI = ( ajaxUrl: string ) =>
    new DokanStripeExpressAPI( ajaxUrl );

// Legacy handler compatibility functions
export const createPaymentIntentHandler = async (
    ajaxUrl: string,
    nonce: string,
    orderId?: string
) => {
    const api = createAPI( ajaxUrl );
    return api.createPaymentIntent( nonce, orderId );
};

export const createSetupIntentHandler = async (
    ajaxUrl: string,
    nonce: string
) => {
    const api = createAPI( ajaxUrl );
    return api.createSetupIntent( nonce );
};

export const createSubscriptionHandler = async (
    productId: string,
    ajaxUrl: string,
    nonce: string
) => {
    const api = createAPI( ajaxUrl );
    return api.createSubscription( nonce, productId );
};

export const confirmIntentHandler = async (
    returnUrl: string,
    paymentMethod: string | null,
    ajaxUrl: string,
    nonce: string
) => {
    const api = createAPI( ajaxUrl );
    return api.confirmIntent( nonce, returnUrl, paymentMethod || undefined );
};

export const processCheckoutHandler = async (
    paymentIntentId: string,
    formFields: Record< string, any >,
    subscriptionId: string,
    ajaxUrl: string,
    nonce: string
) => {
    const api = createAPI( ajaxUrl );
    return api.processCheckout(
        nonce,
        paymentIntentId,
        subscriptionId,
        formFields
    );
};

export const updateIntentHandler = async (
    paymentIntentId: string,
    orderId: string | null,
    savePaymentMethod: string,
    paymentType: string | null,
    ajaxUrl: string,
    nonce: string
) => {
    const api = createAPI( ajaxUrl );
    return api.updateIntent(
        nonce,
        paymentIntentId,
        orderId,
        savePaymentMethod,
        paymentType
    );
};

export const updateFailedOrderHandler = async (
    paymentIntentId: string,
    orderId: string,
    ajaxUrl: string,
    nonce: string
) => {
    const api = createAPI( ajaxUrl );
    return api.updateFailedOrder( nonce, paymentIntentId, orderId );
};

export default DokanStripeExpressAPI;
