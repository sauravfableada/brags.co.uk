import { AsyncSearchableSelect } from '@getdokan/dokan-ui';
import { __ } from '@wordpress/i18n';
import { debounce } from '@wordpress/compose';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import product from '@dokan/stores/products';
import { resolveSelect } from '@wordpress/data';

const ProductFilter = ( { onChange, selectProduct } ) => {
    const handleSearch = debounce( async ( inputValue: string ) => {
        if ( ! inputValue ) {
            return [];
        }
        try {
            const response = await resolveSelect( product ).getItems( {
                per_page: 10,
                search: inputValue,
                _fields: 'id,name',
            } );
            return response.map( ( item: any ) => ( {
                value: item.id,
                label: item.name,
            } ) );
        } catch ( error ) {
            return [];
        }
    }, 500 );

    return (
        <AsyncSearchableSelect
            loadOptions={ handleSearch }
            onChange={ onChange }
            value={ selectProduct }
            className="pt-[2px] shadow-none w-64 z-50"
            placeholder={ __( 'Search', 'dokan' ) }
            noOptionsMessage={ ( { inputValue } ) =>
                inputValue.length < 3
                    ? __( 'Type at least 3 characters to search', 'dokan' )
                    : __( 'No options found', 'dokan' )
            }
        />
    );
};

export default ProductFilter;
