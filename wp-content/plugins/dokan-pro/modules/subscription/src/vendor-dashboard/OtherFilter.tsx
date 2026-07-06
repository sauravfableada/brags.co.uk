import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
// @ts-ignore
import { Select } from '@dokan/components';
import type { ProductFilterState } from './types';

interface FilterOption {
    value: string;
    label: string;
}

interface Props {
    filterArgs: ProductFilterState;
    setFilterArgs: React.Dispatch< React.SetStateAction< ProductFilterState > >;
}

export function OtherFilter( { filterArgs, setFilterArgs }: Props ) {
    const options = applyFilters( 'dokan_get_other_product_filters', [
        {
            value: 'featured',
            label: __( 'Featured', 'dokan' ),
        },
        {
            value: 'top_rated',
            label: __( 'Top Rated', 'dokan' ),
        },
        {
            value: 'best_selling',
            label: __( 'Best Selling', 'dokan' ),
        },
        {
            value: 'low_stock',
            label: __( 'Low on Stock', 'dokan' ),
        },
        {
            value: 'out_of_stock',
            label: __( 'Out of Stock', 'dokan' ),
        },
    ] ) as FilterOption[];

    return (
        <Select
            isClearable
            placeholder={ __( 'Select filter', 'dokan' ) }
            options={ options }
            value={
                options.find(
                    ( o ) => o.value === filterArgs.filter_by_other
                ) ?? null
            }
            onChange={ ( option: FilterOption | null ) => {
                setFilterArgs( ( prev ) => ( {
                    ...prev,
                    filter_by_other: option?.value ?? '',
                    page: 1,
                } ) );
            } }
        />
    );
}
