import domReady from '@wordpress/dom-ready';
import { addFilter } from '@wordpress/hooks';

import ProductAddonsEdit from './ProductAddonsEdit';
import ExcludeGlobalEdit from './ExcludeGlobalEdit';
import { getAddonsValidationMessage } from './shared';

// Register the `product_addons` repeater + the dedicated `product_addons_exclude` checkbox variant; the latter keeps the (REQUIRED) badge inline.
domReady( () => {
    addFilter(
        'dokan_product_editor_ui_variant',
        'dokan-pro/product-addon/variant',
        ( variants: Record< string, any >, field: any ) => {
            variants.product_addons = () => ( {
                Edit: ProductAddonsEdit,
                type: 'array',
                isValid: {
                    required: false,
                    elements: false,
                    // The whole repeater is a single editor field, so the
                    // "needs attention" badge can only count it once; the
                    // message reports the real per-add-on scope instead.
                    custom: ( value: any ) =>
                        getAddonsValidationMessage(
                            value?.[ field?.id ?? 'product_addons' ],
                            field?.options?.subFields
                        ),
                },
            } );

            variants.product_addons_exclude = () => ( {
                Edit: ExcludeGlobalEdit,
                type: 'boolean',
            } );

            return variants;
        }
    );
} );
