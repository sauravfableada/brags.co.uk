;
( function ( $ ) {

    var feedback = $('#feedback');
    var phoneFeedback = $('#d_v_phone_feedback');
    // country to state select generate
    var dokan_address_wrapper = $( '.dokan-address-fields' );
                    var dokan_address_select = {
                        init: function () {

                            dokan_address_wrapper.on( 'change', 'select.country_to_state', this.state_select );
                        },
                        state_select: function () {
                            var states_json = wc_country_select_params.countries.replace( /&quot;/g, '"' ),
                                states = $.parseJSON( states_json ),
                                $statebox = $( '#dokan_address_state' ),
                                input_name = $statebox.attr( 'name' ),
                                input_id = $statebox.attr( 'id' ),
                                input_class = $statebox.attr( 'class' ),
                                value = $statebox.val(),
                                selected_state = $( '#dokan_selected_state' ).val(),
                                input_selected_state = $( '#dokan_selected_state' ).val(),
                                country = $( this ).val();

                            if ( states[ country ] ) {

                                if ( $.isEmptyObject( states[ country ] ) ) {

                                    $( 'div#dokan-states-box' ).slideUp( 2 );
                                    if ( $statebox.is( 'select' ) ) {
                                        $( 'select#dokan_address_state' ).replaceWith( '<input type="text" class="' + input_class + '" name="' + input_name + '" id="' + input_id + '" required />' );
                                    }

                                    $( '#dokan_address_state' ).val( 'N/A' );

                                } else {
                                    input_selected_state = '';

                                    var options = '',
                                        state = states[ country ];

                                    for ( var index in state ) {
                                        if ( state.hasOwnProperty( index ) ) {
                                            if ( selected_state ) {
                                                if ( selected_state == index ) {
                                                    var selected_value = 'selected="selected"';
                                                } else {
                                                    var selected_value = '';
                                                }
                                            }
                                            options = options + '<option value="' + index + '"' + selected_value + '>' + state[ index ] + '</option>';
                                        }
                                    }

                                    if ( $statebox.is( 'select' ) ) {
                                        $( 'select#dokan_address_state' ).html( '<option value="">' + wc_country_select_params.i18n_select_state_text + '</option>' + options );
                                    }
                                    if ( $statebox.is( 'input' ) ) {
                                        $( 'input#dokan_address_state' ).replaceWith( '<select type="text" class="' + input_class + '" name="' + input_name + '" id="' + input_id + '" required ></select>' );
                                        $( 'select#dokan_address_state' ).html( '<option value="">' + wc_country_select_params.i18n_select_state_text + '</option>' + options );
                                    }
                                    $( '#dokan_address_state' ).removeClass( 'dokan-hide' );
                                    $( 'div#dokan-states-box' ).slideDown();

                                }
                            } else {


                                if ( $statebox.is( 'select' ) ) {
                                    input_selected_state = '';
                                    $( 'select#dokan_address_state' ).replaceWith( '<input type="text" class="' + input_class + '" name="' + input_name + '" id="' + input_id + '" required="required"/>' );
                                }
                                $( '#dokan_address_state' ).val(input_selected_state);

                                if ( $( '#dokan_address_state' ).val() == 'N/A' ){
                                    $( '#dokan_address_state' ).val('');
                                }
                                $( '#dokan_address_state' ).removeClass( 'dokan-hide' );
                                $( 'div#dokan-states-box' ).slideDown();
                            }
                        }
                    };

    let DokanVendorVerification = {
        init() {
            // do initialization.
            // close all the closable panel
            $( '.dokan-vendor-verification-start' ).on( 'click', this.open );
            $( '.dokan_vendor_verification_cancel' ).on( 'click', this.close );
            $( '.dokan-vendor-verification-cancel-request' ).on( 'click', this.cancelRequest );
            $( '.dokan-vendor-verification-files-drag-button' ).on( 'click', this.file );
            $( '.dokan-vendor-verification-request-form' ).on( 'submit', this.formSubmit );

        },
        open( event ){
            let element = $( event.target );
            let method_id = element.data( 'method' );
            let innerContent = $( '#dokan-vendor-verification-inner-content-' + method_id );
            let fileContent = $( '#dokan-vendor-verification-file-container-' + method_id );

            element.slideUp('fast', () => {
                fileContent.slideUp( 'fast' );
                innerContent.slideDown( 'fast' );
            });
        },
        close( event ) {
            let element = $( event.target );
            let method_id = element.data( 'method' );
            let innerContent = $( '#dokan-vendor-verification-inner-content-' + method_id );
            let fileContent = $( '#dokan-vendor-verification-file-container-' + method_id );
            let button = $( '#dokan-vendor-verification-start-' + method_id );

            innerContent.slideUp('fast', () => {
                fileContent.slideDown( 'fast' );
                button.slideDown( 'fast' );
            });
        },
        async cancelRequest( event ) {
            let element = $( event.target );
            let request_id = element.data( 'request' );
            let nonce = element.data( 'nonce' );
            let message = element.data( 'message' );
            let data = { request_id, nonce, action: 'dokan_vendor_verification_request_cancellation' };
            let container = element.parent().parent();

            const answer = await dokan_sweetalert( message, {
                action : 'confirm',
                icon   : 'warning',
            } );

            if( 'undefined' !== answer && answer.isConfirmed ) {
                container.block({
                    message: null,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                    }
                });

                $.post(dokan.ajaxurl, data, (response) => {
                    if (response.success === true) {
                        dokan_sweetalert(response.data, {
                            position: 'bottom-end',
                            toast: true,
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                setTimeout(() => {
                                    container.unblock();
                                    window.location.reload();
                                }, 2000);
                            }
                        });
                    } else {
                        dokan_sweetalert('', {
                            icon: 'error',
                            html: response.data,
                        });
                        container.unblock();
                    }
                });
            }
        },
        file( event ) {
            event.preventDefault();
            let file_frame,
                self = $(this),
                method = self.data( 'method' );

            // If the media frame already exists, reopen it.
            if (file_frame) {
                file_frame.open();
                return;
            }

            // Create the media frame.
            file_frame = wp.media.frames.file_frame = wp.media({
                title: $(this).data('uploader_title'),
                button: {
                    text: $(this).data('uploader_button_text')
                },
                multiple: false,
            });

            // When an image is selected, run a callback.
            file_frame.on('select', function() {
                let attachment = file_frame
                    .state()
                    .get('selection')
                    .first()
                    .toJSON();

                const filesContainer = $( '#dokan-vendor-verification-method-files-' + method );

                const customId = 'dokan-vendor-verification-' + method + '-file-' + attachment.id;

                const html = `
                    <div class="dokan-vendor-verification-file-item" id="${customId}">
                        <a href="${attachment.url}" target="_blank" >${attachment.title}.${attachment.subtype}</a>
                        <a href="#" onclick="dokanVendorVerificationRemoveFile(event)" data-attachment_id="${customId}" class="dokan-btn disconnect dokan-btn-danger"><i class="fas fa-times" data-attachment_id="${customId}"></i></a>
                        <input type="hidden" name="vendor_verification_files_ids[]" value="${attachment.id}" />
                    </div>
                `;
                filesContainer.append(html);
            });

            // Finally, open the modal
            file_frame.open();
        },
        formSubmit( event ) {
            event.preventDefault();

            let self = $( this );
            let container = self.parent().parent();

            let data = self.serialize()

            container.block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });

            $.post( dokan.ajaxurl, data, ( response ) => {
                if ( response.success === true ) {
                    dokan_sweetalert( response.data, {
                        position: 'bottom-end',
                        toast: true,
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            setTimeout(() => {
                                container.unblock();
                                window.location.reload();
                            }, 2000 );
                        }
                    } );
                } else {
                    dokan_sweetalert( '', {
                        icon: 'error',
                        html: response.data,
                    } );
                    container.unblock();
                }
            } );

        },
    };

    $( document ).ready( function () {

        dokan_address_select.init();
        DokanVendorVerification.init();

    //Phone verification
        // send sms on submit
        $('.dokan_v_phone_box').on('submit', 'form#dokan-verify-phone-form', function(e) {
            e.preventDefault();

            if ( $( "input[name = 'phone']" ).val() == '' ) {
                dokan_sweetalert( dokan.i18n_phone_number, {
                    icon: 'warning',
                } );
                return;
            }

            var self = $(this),
                data  = {
                    action : 'dokan_v_send_sms',
                    data : self.serialize(),
                };

            $.post( dokan.ajaxurl, data, function( resp ) {

                if ( resp.success == true ) {
                    if(resp.data.success == true){
                      phoneFeedback.removeClass();
                      phoneFeedback.addClass('dokan-alert dokan-alert-success');
                      phoneFeedback.html(resp.data.message);

                      $( 'div.dokan_v_phone_box' ).slideUp();
                      $( 'div.dokan_v_phone_code_box' ).slideDown();
                    }else{
                      phoneFeedback.removeClass();
                      phoneFeedback.addClass('dokan-alert dokan-alert-danger');
                      phoneFeedback.html(resp.data.message);
                    }

                }else{
                    $('#feedback').addClass('dokan-alert dokan-alert-danger');
                    $('#feedback').html('failed');
                }

            })
        });

        // Sanitize phone number character inputs.
        $( '#phone' ).on( 'keydown', dokan_sanitize_phone_number );

        // submit verification code
        $('.dokan_v_phone_code_box').on('submit', 'form#dokan-v-phone-code-form', function(e) {
            e.preventDefault();

            if ( $( "input[name = 'sms_code']" ).val() == '' ) {
                dokan_sweetalert( dokan.i18n_sms_code, {
                    icon: 'warning',
                } );
                return;
            }

            var self = $(this),
                data  = {
                    action : 'dokan_v_verify_sms_code',
                    data : self.serialize(),
                };

            $.post( dokan.ajaxurl, data, function( resp ) {

                if ( resp.success == true ) {

                    if ( resp.data.success == true ) {

                        phoneFeedback.removeClass();
                        phoneFeedback.addClass('dokan-alert dokan-alert-success');
                        phoneFeedback.html(resp.data.message);
                        $('.dokan_v_phone_code_box').fadeOut();

                    } else {
                        phoneFeedback.removeClass();
                        phoneFeedback.addClass('dokan-alert dokan-alert-danger');
                        phoneFeedback.html(resp.data.message);
                    }

                } else {
                    $('#feedback').addClass('dokan-alert dokan-alert-danger');
                    $('#feedback').html('failed');
                }
            });
        });

    } );

} )( jQuery );

function dokanVendorVerificationRemoveFile(e) {
    e.preventDefault();
    jQuery(`#${e.target.dataset.attachment_id}`).remove();
}
