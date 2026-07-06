/* global dokan  */
;(function($){
    const dokan_quote_form = '.dokan-quote-form';
    let Dokan_Request_Quote = {
        init() {
            $( '.product' ).on( 'click', '.dokan_request_button', this.addToQuote );
            $( dokan_quote_form ).on( 'click', '.remove-dokan-quote-item', this.removeQuote );
            $( '#dokan-quote-form, #quote-action-container' ).on( 'click', '.status-updater', this.quoteStatusHandler );
            $( '.dokan-dashboard-wrap table.cart.quote_details' ).on( 'input', '.my-offer, .qty, #shipping-cost', this.calculateQuotePrice );
            $( '.quote-datepicker' ).datepicker( { dateFormat : 'yy-mm-dd', minDate : new Date( new Date() ) } );
        },

        addToQuote() {
            if( $(this).hasClass('disabled') ){
                return;
            }
            let variationId = $(this).closest('form').find('.variation_id').val(),
                productId   = parseInt(variationId) ? variationId : $(this).data('product_id'),
                qty         = $('.qty').val();

            if ( !qty ){
                qty = 1;
            }
            let self = $(this);
            self.addClass('loading');
            $.ajax( {
                url: dokan.ajaxurl,
                method: 'post',
                data: {
                    action: 'dokan_add_to_quote',
                    product_id: productId,
                    quantity: qty,
                    nonce: dokan.dokan_request_quote_nonce,
                }
            } ).done( function ( response ) {
                if ( 'error' === response['type']) {
                    dokan_sweetalert(response['message'], {
                        icon: 'error',
                    });
                }
                self.removeClass('loading');
                if (response['view_button']) {
                    self.after(response['view_button']);
                    self.remove();
                }else if(response['redirect_to']) {
                    window.location.href = response['redirect_to'];
                }else {
                    window.location.reload();
                }
            } ).fail( function ( jqXHR ) {
                if ( jqXHR.responseJSON.data.message ) {
                    dokan_sweetalert( jqXHR.responseJSON.data.message, {
                        icon: 'error',
                    } );
                }
            } );
        },

        removeQuote( e ) {
            "use strict";
            e.preventDefault();

            $(this).closest('tr').css('opacity', '0.5');

            $.ajax({
                url: dokan.ajaxurl,
                type: 'POST',
                data: {
                    action: 'remove_dokan_quote_item',
                    quote_key: $(this).data('cart_item_key'),
                    hide_price: $(this).data('hide_price'),
                    nonce: dokan.dokan_request_quote_nonce
                },
                success: function (response) {
                    if ( 'error' === response['type']) {
                        dokan_sweetalert(response['message'], {
                            icon: 'error',
                        });
                    }
                    if( response['quote_empty'] ){
                        location.reload();
                    }

                    let notice_wrapper = $('div.woocommerce-notices-wrapper');
                    notice_wrapper.html(response['message'] );
                    $('table.dokan_quote_table_contents').replaceWith( response['quote-table'] );
                    $('table.table_quote_totals').replaceWith( response['quote-totals'] );
                    $('body').animate({
                            scrollTop: notice_wrapper.offset().top,
                        }, 500
                    );
                },
                error: function ( err ) {
                    console.log( err );
                }
            });
        },

        updateTotals() {
            let totalSum = 0,
                currencySymbol = dokan.currency_format_symbol;

            const shippingCostElement = $( '#shipping-cost' ),
                shippingCost = parseFloat( shippingCostElement.val() ) || 0,
                shippingPrevCost = parseFloat( shippingCostElement.data( 'shipping_cost' ) );
            if ( isNaN( shippingCost ) ) {
                $( '#shipping-cost' ).val( 0 );
            }

            const approveBtnElement = $( '#quote-action-container .dokan_convert_to_order_button button.quote-approve-button' ),
                updateLabel = approveBtnElement.data( 'update_label' ),
                approveLabel = approveBtnElement.data( 'approve_label' );

            if ( shippingPrevCost !== shippingCost ) {
                approveBtnElement.html( updateLabel );
            } else {
                approveBtnElement.html( approveLabel );
            }

            $( '.dokan-dashboard-wrap table.cart.quote_details .product-row' ).each( function() {
                let offerElement = $( this ).find( '.my-offer' ),
                    offerValue = offerElement.val(),
                    myOffer = parseFloat( offerValue ) || 0,
                    qtyValue = $( this ).find( '.qty' ).val(),
                    qty = parseFloat( qtyValue ) || 1,
                    total = myOffer * qty,
                    offerPrevPrice = parseFloat( offerElement.data( 'offer_price' ) );

                if ( isNaN( myOffer ) ) {
                    $( this ).find( '.my-offer' ).val( 0 );
                }

                if ( isNaN( qty ) ) {
                    $( this ).find( '.qty' ).val( 0 );
                }

                if ( offerPrevPrice !== myOffer ) {
                    approveBtnElement.html( updateLabel );
                }

                $( this ).find( '.total' ).text( total.toFixed( 2 ) + currencySymbol );
                totalSum += total;
            });

            totalSum += shippingCost;

            $( '#total' ).text( totalSum.toFixed( 2 ) + currencySymbol );
        },

        calculateQuotePrice() {
            const value = $( this ).val();
            if ( isNaN( value ) || value === '' ) { // Handle non numeric value.
                $( this ).val( 0 );
            }
            Dokan_Request_Quote.updateTotals();
        },

        updateQuoteStatus( action, QuoteId, successMsg, onSuccess ) {
            Dokan_Request_Quote.sendAjaxRequest(
                'dokan_update_quote_status',
                { status: action, quote_id: QuoteId, success_msg: successMsg },
                function ( response ) {
                    if ( response.success ) {
                        if ( response.data.redirect_to) {
                            window.location = response.data.redirect_to;
                        }
                        onSuccess && onSuccess( response );
                    }
                }
            );
        },

        sendAjaxRequest( action, data, successCallback ) {
            $.ajax({
                url: dokan.ajaxurl,
                type: 'POST',
                dataType: 'JSON',
                data: {
                    action: action,
                    nonce: dokan.dokan_request_quote_nonce,
                    ...data
                },
                success: successCallback,
                error: function ( err ) {
                    console.log( err );
                }
            });
        },

        async quoteStatusHandler( e ) {
            e.preventDefault();

            const self = $( this ),
                { action, message } = self.data(),
                confirmation =
                await Dokan_Request_Quote.showConfirmationAlert(
                    dokan[`${action}_confirmation_msg`],
                    'warning'
                );

            let onSuccess;
            if ( confirmation ) {
                const { quote_id } = self.data();
                if ( action === 'trash' ) {
                    onSuccess = ( response ) => response.success && self.closest( 'tr' ).remove();
                }

                Dokan_Request_Quote.updateQuoteStatus( action, quote_id, message, onSuccess );
            }
        },

        async showConfirmationAlert( message, icon ) {
            const result = await dokan_sweetalert(
                message,
                {
                    action: 'confirm',
                    icon: icon,
                }
            );

            return result?.isConfirmed;
        },

    };

    $(document).ready(function(){
        Dokan_Request_Quote.init();
    });
})(jQuery);
