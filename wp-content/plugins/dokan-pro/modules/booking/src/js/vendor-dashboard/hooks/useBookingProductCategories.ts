import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

interface CategoryEntry {
    term_id: number;
    name: string;
    parent_id: number;
}

type CategoryMap = Record< string, CategoryEntry >;

interface CategoryOption {
    value: number;
    label: string;
}

const INDENT = '\u00A0\u00A0\u00A0';

function buildHierarchicalOptions( map: CategoryMap ): CategoryOption[] {
    const result: CategoryOption[] = [];

    function appendChildren( parentId: number, depth: number ) {
        const children = Object.values( map )
            .filter( ( cat ) => cat.parent_id === parentId )
            .sort( ( a, b ) => a.name.localeCompare( b.name ) );

        for ( const cat of children ) {
            result.push( {
                value: cat.term_id,
                label: INDENT.repeat( depth ) + cat.name,
            } );
            appendChildren( cat.term_id, depth + 1 );
        }
    }

    appendChildren( 0, 0 );
    return result;
}

export const useBookingProductCategories = () => {
    const [ options, setOptions ] = useState< CategoryOption[] >( [] );

    useEffect( () => {
        apiFetch< CategoryMap >( {
            path: '/dokan/v1/products/multistep-categories',
        } )
            .then( ( data ) => {
                if ( ! data || typeof data !== 'object' || Array.isArray( data ) ) {
                    setOptions( [] );
                    return;
                }
                setOptions( buildHierarchicalOptions( data ) );
            } )
            .catch( () => {
                // Silently handle errors; options remain empty.
            } );
    }, [] );

    return { options };
};
