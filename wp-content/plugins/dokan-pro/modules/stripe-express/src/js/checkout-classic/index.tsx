// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import $ from 'jquery';
import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';

import Checkout from './components/Checkout';

const mountComponent = () => {
    if ( ! $( '#payment_method_dokan_stripe_express' ).is( ':checked' ) ) {
        return;
    }

    const container = document.getElementById( 'dokan-stripe-express-element' );
    if ( container ) {
        if ( ! container.children.length ) {
            const root = createRoot( container );

            root.render( <Checkout /> );
        }
    }
};

domReady( () => {
    // Mount on WooCommerce updates
    $( 'body' )
        .on( 'updated_checkout', mountComponent )
        .on(
            'change',
            'input#payment_method_dokan_stripe_express',
            function () {
                mountComponent();
            }
        );
    const addPaymentForm = $( '#add_payment_method' );
    const selector = '#payment_method_dokan_stripe_express';
    // Initial mount
    if( addPaymentForm.find( selector ).is( ':checked' ) ) {
        mountComponent();
    }
    const orderReview = $( 'form#order_review' );
    if ( orderReview.find( selector ).is( ':checked' ) ) {
        mountComponent();
    }
} );
