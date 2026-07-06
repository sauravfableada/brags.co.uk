// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import $ from 'jquery';
import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';

import ExpressPaymentRequest from './components/ExpressPaymentRequest';

const mountExpressPaymentRequest = () => {
    // Check if express payment data is available
    if ( ! window.dokanStripeExpressPRData ) {
        return;
    }

    // Check if the payment request container exists
    const container = document.getElementById(
        'dokan-stripe-express-payment-request-button'
    );
    if ( ! container ) {
        return;
    }

    // Get settings from global data
    const settings = window.dokanStripeExpressPRData;

    // Only mount if not already mounted
    if ( ! container.children.length ) {
        const root = createRoot( container );

        root.render( <ExpressPaymentRequest settings={ settings } /> );
    }
};

// Initialize when DOM is ready
domReady( () => {
    // Mount the express payment request component
    mountExpressPaymentRequest();

    // Re-mount on cart updates if not on product page
    if ( ! window.dokanStripeExpressPRData?.isProductPage ) {
        $( document.body )
            .on( 'updated_cart_totals', mountExpressPaymentRequest )
            .on( 'updated_checkout', mountExpressPaymentRequest );
    }
} );

export default ExpressPaymentRequest;
