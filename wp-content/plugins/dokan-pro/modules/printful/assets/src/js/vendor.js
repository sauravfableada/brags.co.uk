( function ( $ ) {
    const DokanPrintfulVendor = {
        init() {
            const connectPrintfulButton = $(
                '#dokan-pro-connect-printful-btn'
            );
            const disconnectPrintfulButton = $(
                '#dokan-pro-disconnect-printful-btn'
            );
            const enableShippingToggle = $(
                '#dokan-pro-printful-enable-shipping-toggle'
            );
            const enableRatesToggle = $(
                '#dokan-pro-printful-enable-rates-toggle'
            );
            const addSizeGuideButton = $(
                '#dokan-printful-add-size-guide-btn'
            );

            if ( connectPrintfulButton ) {
                connectPrintfulButton.on( 'click', this.connect );
            }
            if ( disconnectPrintfulButton ) {
                disconnectPrintfulButton.on( 'click', this.disconnect );
            }
            if ( enableShippingToggle ) {
                enableShippingToggle.on( 'change', this.enableShipping );
            }
            if ( enableRatesToggle ) {
                enableRatesToggle.on( 'change', this.enableRates );
            }
            if ( addSizeGuideButton ) {
                addSizeGuideButton.on( 'click', this.addSizeGuide );
            }

            this.handleDependentFieldVisibility( enableRatesToggle, enableShippingToggle );
        },

        connect( event ) {
            event.preventDefault();
            const element = $( event.target );
            const spinner = $( '#dokan-pro-connect-printful-spiner' );
            const _nonce = element.data( 'nonce' );
            const data = {
                _nonce,
                action: 'dokan_printful_connect_vendor_to_store',
            };

            spinner.show();
            $.post( dokan.ajaxurl, data, ( response ) => {
                if ( response.success === true ) {
                    spinner.hide();
                    window.location.replace( response.data );
                } else {
                    spinner.hide();
                    dokan_sweetalert( '', {
                        icon: 'error',
                        html: response.data,
                    } );
                }
            } );
        },
        async disconnect( event ) {
            event.preventDefault();
            const element = $( event.target );
            const spinner = $( '#dokan-pro-disconnect-printful-spiner' );
            const _nonce = element.data( 'nonce' );
            const data = {
                _nonce,
                action: 'dokan_printful_disconnect_vendor_to_store',
            };

            const answer = await dokan_sweetalert( DokanPrintful.vendor_disconnect_alert_msg, {
                action: 'confirm',
                icon: 'warning',
            } );

            if ( 'undefined' !== answer && answer.isConfirmed ) {
                spinner.show();
                $.post( dokan.ajaxurl, data, ( response ) => {
                    if ( response.success === true ) {
                        dokan_sweetalert( response.data, {
                            position: 'bottom-end',
                            toast: true,
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true,
                            didOpen: ( toast ) => {
                                setTimeout( () => {
                                    spinner.hide();
                                    window.location.reload();
                                }, 2000 );
                            },
                        } );
                    } else {
                        spinner.hide();
                        dokan_sweetalert( '', {
                            icon: 'error',
                            html: response.data,
                        } );
                    }
                } );
            }
        },
        enableShipping( event ) {
            const self = this;
            const element = $( event.target );
            const _nonce = element.data( 'nonce' );
            const data = {
                _nonce,
                action: 'dokan_printful_enable_shipping_toggle',
                value: self.checked ? 'yes' : 'no',
            };

            const shippingInputsWrapper = element.closest( '#dokan-pro-printful-shipping-inputs-wrapper' );
            const enableRatesInput = shippingInputsWrapper.find( '#dokan-pro-printful-enable-rates-toggle' );
            const enableRatesInputSwitch = enableRatesInput.closest( '.dokan-switch' );

            if ( ! self.checked ) {
                if ( ! enableRatesInput.prop( 'checked' ) ) {
                    enableRatesInput.prop( 'checked', true );
                    enableRatesInput.trigger( 'change' );
                }
                enableRatesInput.prop( 'disabled', true );
                enableRatesInputSwitch.addClass( 'printful-settings-overlay' );
            } else {
                enableRatesInput.prop( 'disabled', false );
                enableRatesInputSwitch.removeClass( 'printful-settings-overlay' );
            }

            shippingInputsWrapper.block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6,
                },
            });

            $.post(
                dokan.ajaxurl,
                data,
                async ( response ) =>  {
                    if ( true === response.success ) {
                        await dokan_sweetalert( response.data, {
                            position: 'bottom-end',
                            toast: true,
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                setTimeout(() => {
                                    shippingInputsWrapper.unblock();
                                }, 2000 );
                            },
                        } );
                    } else {
                        dokan_sweetalert( '', {
                            icon: 'error',
                            html: response.data,
                        } );
                        shippingInputsWrapper.unblock();
                    }
                }
            );
        },
        enableRates( event ) {
            const self = this;
            const element = $( event.target );
            const _nonce = element.data( 'nonce' );
            const data = {
                _nonce,
                action: 'dokan_printful_enable_rates_toggle',
                value: self.checked ? 'yes' : 'no',
            };

            const shippingInputsWrapper = element.closest( '#dokan-pro-printful-shipping-inputs-wrapper' );

            shippingInputsWrapper.block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6,
                },
            });

            $.post(
                dokan.ajaxurl,
                data,
                async ( response ) =>  {
                    if ( true === response.success ) {
                        await dokan_sweetalert( response.data, {
                            position: 'bottom-end',
                            toast: true,
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                setTimeout(() => {
                                    shippingInputsWrapper.unblock();
                                }, 2000 );
                            },
                        } );
                    } else {
                        dokan_sweetalert( '', {
                            icon: 'error',
                            html: response.data,
                        } );
                        shippingInputsWrapper.unblock();
                    }
                }
            );
        },
        addSizeGuide( event ) {
            event.preventDefault();
            const element = $( event.target );
            const spinner = $( '#dokan-printful-add-size-guide-spiner' );
            const _nonce = element.data( 'nonce' );
            const productId = element.data( 'product_id' );
            const catalogId = element.data( 'catalog_id' );
            const vendorId = element.data( 'vendor_id' );
            const data = {
                _nonce,
                product_id: productId,
                catalog_id: catalogId,
                vendor_id: vendorId,
                action: 'dokan_printful_add_size_guide',
            };

            spinner.show();
            $.post(
                dokan.ajaxurl,
                data,
                async ( response ) =>  {
                    if ( true === response.success ) {
                        await dokan_sweetalert( response.data, {
                            position: 'bottom-end',
                            toast: true,
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true,
                            didOpen: ( toast ) => {
                                setTimeout( () => {
                                    spinner.hide();
                                }, 2000 );
                            },
                        } );
                    } else {
                        spinner.hide();
                        dokan_sweetalert( '', {
                            icon: 'error',
                            html: response.data,
                        } );
                    }
                }
            );
        },

        handleDependentFieldVisibility( targetField, parentField ) {
            const parentAvailability = parentField.prop( 'checked' );
            targetField.closest( '.dokan-switch' ).toggleClass( 'printful-settings-overlay', ! parentAvailability );

            if ( ! parentAvailability ) { // If parent is not available, disable the target field.
                targetField.prop( 'checked', true ).prop( 'disabled', true );
            }
        },
    };

    DokanPrintfulVendor.init();
} )( jQuery );
