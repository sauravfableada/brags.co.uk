import { __, sprintf } from '@wordpress/i18n';
import { RawHTML } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import { addFilter } from '@wordpress/hooks';
import { OtherFilter } from './OtherFilter';
import type { ProductFilterState } from './types';

domReady( () => {
    // Add the "Other" filter to the product list filters. Hooks into dokan_product_list_filter_fields.
    addFilter(
        'dokan_product_list_filter_fields',
        'dokan-pro/other-filter',
        (
            fields: any[],
            filterArgs: ProductFilterState,
            setFilterArgs: React.Dispatch<
                React.SetStateAction< ProductFilterState >
            >
        ) => {
            return [
                ...fields,
                {
                    id: 'filter_by_other',
                    label: __( 'Other', 'dokan' ),
                    field: (
                        <OtherFilter
                            filterArgs={ filterArgs }
                            setFilterArgs={ setFilterArgs }
                        />
                    ),
                },
            ];
        }
    );

    // Add a notice about subscription limits to the product list page. Hooks into dokan_product_list_page_notices.
    addFilter(
        'dokan_product_list_page_notices',
        'dokan-pro/subscription-notice',
        ( notices: JSX.Element[], config: any ) => {
            const subscription = config?.subscription;
            if ( ! subscription ) return notices;

            const { remaining_products, can_post_product, subscription_url } =
                subscription;

            let notice: JSX.Element;

            if ( remaining_products === true && can_post_product ) {
                notice = (
                    <p className="dokan-info">
                        { __( 'You can add unlimited products', 'dokan' ) }
                    </p>
                );
            } else if ( remaining_products === 0 || ! can_post_product ) {
                notice = (
                    <RawHTML className="dokan-alert dokan-alert-info">
                        { sprintf(
                            /* translators: %s: subscription page URL */
                            __(
                                'Sorry! You can not add or publish any more product. Please <a href="%s">update your package</a>.',
                                'dokan'
                            ),
                            subscription_url
                        ) }
                    </RawHTML>
                );
            } else {
                notice = (
                    <p className="dokan-alert dokan-alert-info">
                        { sprintf(
                            /* translators: %d: number of remaining products */
                            __( 'You can add %d more product(s).', 'dokan' ),
                            remaining_products as number
                        ) }
                    </p>
                );
            }

            return [ ...notices, notice ];
        }
    );
} );
