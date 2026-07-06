(function($){
    var wrapper = $( '.dokan-store-tabs' ),
        login_btn = $( '.dokan-live-chat-login' ),
        custom_login_btn = login_btn.html(),
        modal = $( '.dokan-live-chat-modals' ).iziModal( {
            width: 440,
            closeButton: true,
            appendTo: 'body',
            title: '',
            headerColor: dokan.modal_header_color
        } );

    var Dokan_Live_Chat = {

        init : function() {
            $('.dokan-live-chat-login').on( 'click', this.popUp.show );
            $('body').on( 'submit', '#dokan-chat-login', this.popUp.submitLogin );
            // $('body').on( 'submit', '#dokan-support-form', this.popUp.submitSupportMsg );
        },
        popUp : {
            show : function(e){
                e.preventDefault();
                login_btn.html( dokan_live_chat.wait );
                Dokan_Live_Chat.popUp.getForm( 'login_form' );
            },
            getForm : function( data ){

                var s_data = {
                    action: 'dokan_live_chat_login',
                    data: data,
                    store_id : login_btn.data( 'store_id' )
                };

                $.post( dokan.ajaxurl, s_data, function ( resp ) {
                    if ( resp.success == true ) {
                        modal.iziModal( 'setContent', '<div class="white-popup dokan-support-login-wrapper"><div id="ds-error-msg" ></div>' + resp.data + '</div>' );
                        modal.iziModal( 'open' );

                        login_btn.html(custom_login_btn);
                    } else {
                        dokan_sweetalert( dokan.i18n_invalid, {
                            icon: 'error',
                        } );
                        login_btn.html(custom_login_btn);
                    }
                } )
            },

            submitLogin : function(e){
                e.preventDefault();
                var self = $(this);
                var s_data = {
                    action : 'dokan_live_chat_login',
                    data : 'login_data_submit',
                    form_data : self.serialize(),
                };

                var $e_msg = $('#ds-error-msg');
                $e_msg.addClass('dokan-hide');
                $.post( dokan.ajaxurl, s_data, function ( resp ) {
                    if ( resp.success == true ) {
                        modal.iziModal( 'close' );
                        location.reload()
                    }
                    else {
                        dokan_sweetalert( dokan.i18n_invalid, {
                            icon: 'error',
                        } );
                        login_btn.html(custom_login_btn);
                    }
                } )
            },
        },
    };

    $(function() {
        Dokan_Live_Chat.init();
    });
})(jQuery);
