( function( $ ) {
    // Save Form Data.
    $( '#dokan-shipstation-settings-form' ).on( 'submit', function ( e ) {
        e.preventDefault();

        const spinner = $( '#dokan-shipstation-form-submit-spinner' );
        const data = {
            vendor_id: $( '#dokan_shipstation_vendor_id' ).val(),
            export_statuses: $( '#dokan-shipstation-export-statuses' ).val(),
            shipped_status: $( '#dokan-shipstation-order-status' ).val(),
        };

        spinner.show();
        $.ajax( {
            headers: {
                'X-WP-Nonce': dokan.rest.nonce
            },
            url: dokan.rest.root + dokan.rest.version + '/shipstation/order-statuses',
            method: 'PUT',
            data,
        } ).done( async function ( response ) {
            if ( 'undefined' != response.vendor_id ) {
                spinner.hide();
                dokan_sweetalert( DokanShipStation.save_settings_success_msg, {
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
                    html: response.message,
                } );
            }
        } ).fail( function ( jqXHR, status, error ) {
            if ( jqXHR.responseJSON.message ) {
                spinner.hide();
                dokan_sweetalert( '', {
                    icon: 'error',
                    html: jqXHR.responseJSON.message,
                } );
            }
        } );
    } );

    // Generate Credentials.
    $( '#dokan-shipstation-generate-credentials-btn' ).on( 'click', function ( e ) {
        e.preventDefault();

        const element = $( e.target );
        const spinner = $( '#dokan-shipstation-generate-credentials-spinner' );
        const vendorId = element.data( 'vendor_id' );
        const data = {
            vendor_id: vendorId,
        };

        spinner.show();

        $.ajax( {
            headers: {
                'X-WP-Nonce': dokan.rest.nonce,
            },
            url: dokan.rest.root + dokan.rest.version + '/shipstation/credentials/create',
            method: 'POST',
            data: data,
        } ).done( async function ( response ) {
            spinner.hide();

            if ( response.key_id ) {
                let prompt = await dokan_sweetalert( DokanShipStation.generate_credential_success_msg, {
                    icon: 'warning',
                    action: 'confirm',
                    confirmButtonText: DokanShipStation.generate_credential_warning_button_label,
                    showCancelButton: false,
                } );

                if ( prompt && prompt.isConfirmed ) {
                    window.location.reload();
                }
            } else {
                dokan_sweetalert( '', {
                    icon: 'error',
                    html: response.message,
                } );
            }
        } ).fail( function ( jqXHR, status, error ) {
            if ( jqXHR.responseJSON.message ) {
                spinner.hide();
                dokan_sweetalert( '', {
                    icon: 'error',
                    html: jqXHR.responseJSON.message,
                } );
            }
        } );
    } );

    // Revoke Credentials.
    $( '#dokan-shipstation-revoke-credentials-btn' ).on( 'click', async function ( e ) {
        e.preventDefault();

        const element = $( e.target );
        const spinner = $( '#dokan-shipstation-revoke-credentials-spinner' );
        const vendorId = element.data( 'vendor_id' );

        const answer = await dokan_sweetalert( '', {
            action: 'confirm',
            icon: 'warning',
            title: DokanShipStation.revoke_warning_title,
            text: DokanShipStation.revoke_warning_text,
            confirmButtonText: DokanShipStation.revoke_confirm_button_label,
            confirmButtonColor: '#DC3545',
            cancelButtonColor: '#C2CFD9',
        } );

        if ( 'undefined' !== answer && answer.isConfirmed ) {
            spinner.show();
            $.ajax( {
                headers: {
                    'X-WP-Nonce': dokan.rest.nonce,
                    'Content-Type': 'application/json'
                },
                url: dokan.rest.root + dokan.rest.version + '/shipstation/credentials/' + vendorId,
                method: 'DELETE',
            } ).done( function ( response ) {
                if ( 'undefined' != response.key_id ) {
                    spinner.hide();
                    dokan_sweetalert( DokanShipStation.revoke_credential_success_msg, {
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
                        html: response.message,
                    } );
                }
            } ).fail( function ( jqXHR, status, error ) {
                if ( jqXHR.responseJSON.message ) {
                    spinner.hide();
                    dokan_sweetalert( '', {
                        icon: 'error',
                        html: jqXHR.responseJSON.message,
                    } );
                }
            } );
        }
    } );
} )( jQuery );
