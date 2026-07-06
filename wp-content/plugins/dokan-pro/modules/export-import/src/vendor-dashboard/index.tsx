import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';
import { addFilter, doAction } from '@wordpress/hooks';

interface ProductListingConfig {
    can_add_product?: boolean;
    new_product_url?: string;
    can_import?: boolean;
    can_export?: boolean;
    import_url?: string;
    export_url?: string;
}

domReady( () => {
    addFilter(
        'dokan_product_list_header_buttons',
        'dokan-pro/export-import-buttons',
        ( buttons: JSX.Element[], config: ProductListingConfig ) => {
            const extra: JSX.Element[] = [];

            if ( config.can_import && config.import_url ) {
                extra.push(
                    <a
                        key="import-products"
                        href={ config.import_url }
                        className="dokan-btn dokan-btn-secondary"
                    >
                        { __( 'Import', 'dokan' ) }
                    </a>
                );
            }

            if ( config.can_export && config.export_url ) {
                extra.push(
                    <a
                        key="export-products"
                        href={ config.export_url }
                        className="dokan-btn dokan-btn-secondary"
                    >
                        { __( 'Export', 'dokan' ) }
                    </a>
                );
            }

            return [ ...buttons, ...extra ];
        }
    );

    /**
     * Signal to the React product list component that filters have been registered
     *
     * @since 5.0.0
     */
    doAction( 'dokan_product_list_header_buttons_registered' );
} );
