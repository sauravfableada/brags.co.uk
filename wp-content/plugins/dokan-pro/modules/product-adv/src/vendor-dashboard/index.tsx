import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';
import { addFilter } from '@wordpress/hooks';
import { AdvertiseButton } from './AdvertiseButton';
import type { Field, ProductItem } from './types';

const advertiseField = {
    id: 'advertise',
    label: __( 'Advertise', 'dokan' ),
    enableSorting: false,
    render: ( { item }: { item: ProductItem } ) => (
        <AdvertiseButton item={ item } />
    ),
};

domReady( () => {
    /**
     * Inject the Advertise column into the React product list table.
     */
    addFilter(
        'dokan_product_list_table_fields',
        'dokan-pro/product-adv-column',
        ( fields: Field[] ) => {
            fields.push( advertiseField );
            return fields;
        }
    );

    /**
     * Inject the Advertise column into the React auction list table.
     * Uses the same AdvertiseButton — advertisement data is injected into
     * the auction REST response via the shared dokan_rest_product_advertisement_data filter.
     */
    addFilter(
        'dokan_auction_list_table_fields',
        'dokan-pro/auction-adv-column',
        ( fields: Field[] ) => {
            fields.push( advertiseField );
            return fields;
        }
    );

    /**
     * Add 'advertise' to the initial visible column list for the auction list table.
     */
    addFilter(
        'dokan_auction_list_view_fields',
        'dokan-pro/auction-adv-view-fields',
        ( fields: string[] ) => {
            fields.push( 'advertise' );
            return fields;
        }
    );
} );
