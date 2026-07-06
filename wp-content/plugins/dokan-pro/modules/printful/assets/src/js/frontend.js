( function ( $ ) {
    const DokanPrintfulFrontend = {
        init() {
            const sizeGuidePreviewLink = $(
                '.dokan-printful-size-guide-preview-link'
            );

            if ( sizeGuidePreviewLink ) {
                sizeGuidePreviewLink.on( 'click', this.openSizeGuidePopup );
            }
        },

        openSizeGuidePopup( e ) {
            e.preventDefault();

            const productTemplate = wp.template( 'dokan-printful-product-size-guide' ),
                modalElem = $( '.dokan-printful-size-guide-popup-wrapper' );
                DokanPrintfulFrontend.modal = modalElem.iziModal( {
                    title: DokanPrintfulPopup.popup_title,
                    headerColor : dokan.modal_header_color,
                    overlayColor: 'rgba(0, 0, 0, 0.8)',
                    width       : 900,
                    top         : 32,
                    onOpening: function(modal){
                        modal.startLoading();
                    },
                    onOpened: function(modal){
                        modal.stopLoading();

                        // Initialize Tabs
                        DokanPrintfulFrontend.initializeTabs();
                    },
                } );

            DokanPrintfulFrontend.modal.iziModal( 'setContent', productTemplate().trim() );
            DokanPrintfulFrontend.modal.iziModal( 'open' );
        },

        initializeTabs() {
            const printfulSizeGuideTabs = $( "#dokan-printful-size-guide-tabs" );
            const printfulSizeTableTabs = $( ".dokan-printful-size-table-wrapper" );

            const sizeTablePopupData = {
                active: "inches" === DokanPrintfulPopup.primary_measurement_unit ? 0 : 1,
            };

            printfulSizeGuideTabs.tabs();
            printfulSizeTableTabs.tabs( sizeTablePopupData );
        },
    };

    DokanPrintfulFrontend.init();
} )( jQuery );
