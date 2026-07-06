import { type Appearance, type DefaultValuesOption } from '@stripe/stripe-js';

// =============================================================================
// BASE TYPES
// =============================================================================

// Common reusable types
export type DisplayItem = {
    label: string;
    amount: number;
};

export type PendingDisplayItem = DisplayItem & {
    pending?: boolean;
};

export type Total = {
    label: string;
    amount: number;
};

export type PendingTotal = Total & {
    pending: boolean;
};

export type ErrorResponse = {
    message: string;
};

// =============================================================================
// ADDRESS TYPES
// =============================================================================

export type BillingAddress = {
    first_name: string;
    last_name: string;
    company: string;
    address_1: string;
    address_2: string;
    city: string;
    state: string;
    postcode: string;
    country: string;
    email: string;
    phone: string;
};

export type CheckoutBillingAddress = {
    billing_first_name: string;
    billing_last_name: string;
    billing_email: string;
    billing_phone: string;
    billing_address_1: string;
    billing_address_2: string;
    billing_city: string;
    billing_state: string;
    billing_postcode: string;
    billing_country: string;
};

export type CheckoutShippingAddress = {
    shipping_recipient: string;
    shipping_country: string;
    shipping_address_1: string;
    shipping_address_2: string;
    shipping_city: string;
    shipping_state: string;
    shipping_postcode: string;
};

export type UniformAddress = {
    firstName: string;
    lastName: string;
    company: string;
    address1: string;
    address2: string;
    city: string;
    state: string;
    postcode: string;
    country: string;
    email: string;
    phone: string;
};

export type ShippingAddress = {
    recipient: string;
    organization?: string;
    country: string;
    addressLine: string[];
    city: string;
    region: string;
    postalCode: string;
};

export type PaymentFormBillingDetails = {
    name: string;
    email: string;
    phone: string;
    address: {
        country: string;
        line1: string;
        line2: string;
        city: string;
        state: string;
        postal_code: string;
    };
};

export type BillingData = Omit<
    DefaultValuesOption,
    'card'
>[ 'billingDetails' ];

export type HiddenBillingFields = {
    name: 'auto' | 'never';
    email: 'auto' | 'never';
    phone: 'auto' | 'never';
    address: {
        country: 'auto' | 'never';
        line1: 'auto' | 'never';
        line2: 'auto' | 'never';
        city: 'auto' | 'never';
        state: 'auto' | 'never';
        postalCode: 'auto' | 'never';
    };
};

// =============================================================================
// PRODUCT TYPES
// =============================================================================

export type ProductItem = {
    type: string;
    id: number;
};

export type ProductAttributes = {
    [ key: string ]: string;
};

export type ShippingOption = {
    id: string;
    label?: string;
    detail?: string;
    amount?: number;
};

// This type represents the unified event data structure created by createUniformEventData
export type PaymentRequestEventData = CheckoutBillingAddress &
    CheckoutShippingAddress & {
        element_type: 'expressCheckout';
        express_payment_type: string;
        payment_intent_id: string;
        payment_method: 'dokan_stripe_express';
        shipping_option: ShippingOption | null;
        tax_id?: string;
    };

// =============================================================================
// RESPONSE TYPES
// =============================================================================

export type CheckoutSuccessResponse = {
    redirectUrl: string;
    orderId: number;
    customerId: number;
    orderNotes: string;
    payment_needed?: string;
    processingResponse: {
        message: string;
        paymentStatus: string;
        redirectUrl: string;
        paymentDetails: {
            result: string;
            payment_needed: string;
            order_id: string;
            redirect: string;
        };
    };
};

// Payment Intent Types
export type CreatePaymentIntentResponse = {
    is_success: boolean;
    clientSecret?: string;
    paymentIntentData?: {
        id: string;
        client_secret: string;
    };
    message?: string;
};

export type CreatePaymentIntentApiResponse = {
    success: boolean;
    data: {
        client_secret: string;
        id: string;
        error?: ErrorResponse;
    };
};

// Subscription Types
export type CreateSubscriptionResponse = {
    is_success: boolean;
    subscriptionId?: string;
    clientSecret?: string;
    paymentIntentData?: {
        id: string;
        client_secret: string;
    };
    message?: string;
};

export type CreateSubscriptionApiResponse = {
    success: boolean;
    data: {
        subscription_id: string;
        client_secret: string;
        id: string;
        error?: ErrorResponse;
    };
};

// Cart Types
export type AddToCartApiResponse = {
    result: string;
    messages: string;
    currency: string;
    country_code: string;
    displayItems: DisplayItem[];
    total: PendingTotal;
};

export type AddToCartResponse = {
    is_success: boolean;
    displayItems?: DisplayItem[];
    total?: PendingTotal;
    message?: string;
};

export type CartDetailsApiResponse = {
    order_data: {
        total: Total;
        currency: string;
        country_code: string;
        displayItems: DisplayItem[];
    };
    shipping_required: boolean;
    error?: ErrorResponse;
};

export type CartDetailsResponse = {
    is_success: boolean;
    order_data?: {
        total: Total;
        currency: string;
        country_code: string;
        displayItems: DisplayItem[];
    };
    shipping_required?: boolean;
    message?: string;
};

// Product Data Types
export type GetSelectedProductDataRequest = {
    security: string;
    product_id: string;
    qty: string;
    attributes: ProductAttributes;
    addon_value: number;
};

export type GetSelectedProductDataApiResponse = {
    success: boolean;
    data:
        | {
              total: Total;
              displayItems: DisplayItem[];
          }
        | {
              error: ErrorResponse;
          };
};

export type GetSelectedProductDataResponse = {
    is_success: boolean;
    total?: Total;
    displayItems?: DisplayItem[];
    message?: string;
    error?: string;
};

// Shipping Types
export type UpdateShippingDetailsRequest = {
    security: string;
    shipping_method: string[];
    payment_request_type: string;
    is_product_page: boolean;
};

export type UpdateShippingDetailsApiResponse = {
    result?: 'success';
    total?: Total;
    displayItems?: DisplayItem[];
    error?: ErrorResponse;
};

export type UpdateShippingDetailsResponse = {
    is_success: boolean;
    result?: string;
    total?: Total;
    displayItems?: DisplayItem[];
    message?: string;
};

export type UpdateShippingOptionsApiResponse = {
    result?: 'success';
    shipping_options?: Array< {
        id: string;
        label: string;
        detail: string;
        amount: number;
    } >;
    displayItems?: DisplayItem[];
    total?: PendingTotal;
    error?: ErrorResponse;
};

export type UpdateShippingOptionsResponse = {
    is_success: boolean;
    result?: string;
    shipping_options?: Array< {
        id: string;
        label: string;
        amount: number;
    } >;
    total?: Total;
    displayItems?: DisplayItem[];
    message?: string;
};

// Payment Processing Types
export type ProcessPaymentResponseApiResponse = {
    result: string;
    messages: string;
    refresh: boolean;
    reload: boolean;
    payment_needed?: boolean;
    order_id?: number;
    redirect?: string;
    message?: string;
};

export type ProcessPaymentResponseResponse = {
    is_success: boolean;
    result?: string;
    redirect?: string;
    messages?: string;
    message?: string;
};

// =============================================================================
// COMPONENT STATE TYPES
// =============================================================================

export type ContentState = {
    error: string | null;
    subscriptionId: string;
    PI: any | null;
    isLoading: boolean;
};

export type CheckoutState = {
    error: string | null;
    subscriptionId: string;
    isLoading: boolean;
    isComplete: boolean;
    selectedPaymentMethodRaw: string;
};

// =============================================================================
// COMPONENT PROPS TYPES
// =============================================================================

// Base Payment Form Props (for blocks)
export type PaymentFormProps = Record< string, any > & {
    eventRegistration: {
        onPaymentSetup: ( callback: () => Promise< any > ) => () => void;
        onCheckoutSuccess: (
            callback: ( response: CheckoutSuccessResponse ) => Promise< any >
        ) => () => void;
        onCheckoutFail?: ( callback: ( response: any ) => any ) => () => void;
    };
    emitResponse: {
        responseTypes: {
            SUCCESS: string;
            ERROR: string;
        };
        noticeContexts?: {
            EXPRESS_PAYMENTS?: string;
        };
    };
    paymentIntentData: {
        id: string;
        client_secret: string;
    };
    cartData: any;
    shippingData: any;
    billing: {
        billingAddress: BillingAddress;
    };
};

export type DefaultPaymentFormProps = PaymentFormProps;

export type SubscriptionPaymentFormProps = PaymentFormProps & {
    subscriptionId: string;
};

// Classic Payment Form Props (for classic checkout)
export type PaymentFormClassicProps = {
    onChange: ( event: any ) => void;
    paymentIntentId: string;
    subscriptionId: string;
    ajaxurl: string;
    nonce: string;
    orderId: string;
    orderReturnURL: string;
    addPaymentReturnURL: string;
    assets: DokanStripeExpressData[ 'assets' ];
    isOrderPay: boolean;
    isCheckout: boolean;
    isAddPaymentMethod: boolean;
    isChangingPayment: boolean;
};

// Express Payment Component Props
export type ExpressPaymentRequestProps = {
    settings: DokanStripeExpressPRData;
};

export type ProductPageHandlerProps = {
    expressCheckoutElement: React.ReactElement | null;
    onAddToCart: () => Promise< any >;
    onGetProductData: () => Promise< any >;
    settings: DokanStripeExpressPRData;
};

export type SharedExpressPaymentFormProps = {
    // Block-specific props (optional)
    eventRegistration?: {
        onPaymentSetup: ( callback: () => Promise< any > ) => () => void;
        onCheckoutSuccess: (
            callback: ( response: any ) => Promise< any >
        ) => () => void;
    };
    emitResponse?: {
        responseTypes: {
            SUCCESS: string;
            ERROR: string;
        };
    };
    billing?: {
        billingAddress: BillingAddress;
    };
    shippingData?: {
        needsShipping: boolean;
    };
    setExpressPaymentError?: ( message: string ) => void;

    // Classic-specific props (optional)
    settings?: DokanStripeExpressPRData | DokanStripeExpressBlockData;
    isProductPage?: boolean;
    onAddToCart?: () => Promise< any >;
    onError?: ( message: string ) => void;
    onBlockUI?: () => void;
};

export type PaymentDescriptionProps = {
    className?: string;
    isTestMode?: boolean;
    description?: string;
};

// =============================================================================
// GLOBAL DATA TYPES
// =============================================================================

export type DokanStripeExpressData = {
    title: string;
    key: string;
    locale: string;
    billingFields: string[];
    isTestMode: string;
    isCheckout: string;
    isAddPaymentMethod: string;
    isOrderPay: string;
    isChangingPayment: string;
    orderReturnURL: string;
    errors: {
        timeout: string;
        abort: string;
        default: string;
    };
    messages: {
        invalid_number: string;
        invalid_expiry_month: string;
        invalid_expiry_year: string;
        invalid_cvc: string;
        incorrect_number: string;
        incomplete_number: string;
        incomplete_cvc: string;
        incomplete_expiry: string;
        expired_card: string;
        incorrect_cvc: string;
        incorrect_zip: string;
        postal_code_invalid: string;
        invalid_expiry_year_past: string;
        card_declined: string;
        missing: string;
        processing_error: string;
        email_invalid: string;
        invalid_request_error: string;
        amount_too_large: string;
        amount_too_small: string;
        country_code_invalid: string;
        tax_id_invalid: string;
    };
    ajaxurl: string;
    nonce: string;
    addPaymentReturnURL: string;
    accountDescriptor: string;
    genericErrorMessage: string;
    assets: {
        applePayLogo: string;
        googlePayLogo: string;
    };
    sepaElementsOptions: {
        supportedCountries: string[];
        placeholderCountry: string;
    };
    appearance: {
        theme: string;
    };
    isPaymentNeeded: string;
    orderId: string;
};

export type DokanStripeExpressPRData = {
    ajaxUrl: string;
    stripe: {
        key: string;
        allowPrepaidCard: string;
        paymentMethod: string;
        apiVersion: string;
    };
    customer: {
        first_name: string;
        last_name: string;
    };
    nonce: {
        intent: string;
        payment: string;
        shipping: string;
        updateShipping: string;
        update_shipping_method: string;
        checkout: string;
        addToCart: string;
        getSelectedProductData: string;
        logErrors: string;
        clearCart: string;
        get_shipping_options?: string;
    };
    i18n: {
        error: {
            noPrepaidCard: string;
            unknownShipping: string;
        };
        applePay: string;
        googlePay: string;
        login: string;
        cancel: string;
        makeSelection: string;
        productUnavailable: string;
    };
    euCompliance: {
        needTaxId: boolean;
        taxIDFieldTitle: string;
    };
    checkout: {
        url: string;
        currencyCode: string;
        countryCode: string;
        shippingNeeded: string;
        payerPhoneNeeded: boolean;
    };
    element_theme: Appearance[ 'theme' ];
    button: {
        type: string;
        theme: string;
        height: number;
        locale: string;
    };
    loginStatus:
        | {
              message: string;
              redirect_url: string;
          }
        | Record< string, never >;
    isProductPage: boolean;
    product: {
        displayItems: PendingDisplayItem[];
        total: PendingTotal;
        requestShipping: boolean;
        currency: string;
        country_code: string;
        shippingOptions?: Array< {
            id: string;
            label: string;
            detail: string;
            amount: number;
        } >;
    };
    accountDescriptor?: string;
};

export type DokanStripeExpressBlockData = {
    id: string;
    title: string;
    description: string;
    supports: string[];
    accountDescriptor: string;
    element_theme: string;
    capture: boolean;
    testmode: boolean;
    show_save_option: boolean;
    publishable_key: string;
    ajax_url: string;
    checkout: string;
    woo_checkout: string;
    locale: string;
    error_prefix: string;
    euCompliance: {
        needTaxId: boolean;
        taxIDFieldTitle: string;
    };
    nonce: {
        get_shipping_options: string;
        update_shipping_method: string;
        update_payment_method: string;
    };
};

// =============================================================================
// GLOBAL DECLARATIONS
// =============================================================================

declare global {
    interface Window {
        dokanStripeExpress: DokanStripeExpressData;
        dokanStripeExpressPRData: DokanStripeExpressPRData;
        dokanStripeExpressBlockData?: DokanStripeExpressBlockData;
    }
}
