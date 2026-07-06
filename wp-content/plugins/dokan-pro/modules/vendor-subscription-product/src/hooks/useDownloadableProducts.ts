import { useCallback, useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { Product } from '../../../../src/Definitions/Product';

interface UseDownloadableProductsParams {
    searchTerm?: string;
    perPage?: number;
    initialLoad?: boolean;
}

interface GrantAccessResponse {
    granted_files: {
        [ key: number ]: Array< {
            download_id: string;
            product_id: number;
            file_name: string;
            downloads_remaining: number | 'unlimited';
            access_expires: string | null;
        } >;
    };
    errors?: string[];
}

interface UseDownloadableProductsReturn {
    products: Product[];
    isLoading: boolean;
    error: Error | null;
    searchProducts: ( term: string ) => Promise< Product[] >;
    refresh: () => void;
    grantAccess: (
        orderId: number,
        productIds: number[]
    ) => Promise< GrantAccessResponse >;
    isGranting: boolean;
    grantError: Error | null;
}

export const useDownloadableProducts = ( {
    searchTerm = '',
    perPage = 30,
    initialLoad = false,
}: UseDownloadableProductsParams = {} ): UseDownloadableProductsReturn => {
    const [ products, setProducts ] = useState< Product[] >( [] );
    const [ isLoading, setIsLoading ] = useState( false );
    const [ error, setError ] = useState< Error | null >( null );
    const [ search, setSearch ] = useState( searchTerm );
    const [ isGranting, setIsGranting ] = useState( false );
    const [ grantError, setGrantError ] = useState< Error | null >( null );

    const fetchProducts = useCallback(
        async ( searchQuery: string ) => {
            setIsLoading( true );
            setError( null );

            try {
                const response = await apiFetch( {
                    path: addQueryArgs( '/dokan/v1/products', {
                        only_downloadable: true,
                        search: searchQuery,
                        per_page: perPage,
                    } ),
                } );

                const productsList = Array.isArray( response ) ? response : [];
                setProducts( productsList );
                return productsList;
            } catch ( err ) {
                const errorObj =
                    err instanceof Error
                        ? err
                        : new Error( 'Failed to fetch products' );
                setError( errorObj );
                setProducts( [] );
                throw errorObj;
            } finally {
                setIsLoading( false );
            }
        },
        [ perPage ]
    );

    // Initial load
    useEffect( () => {
        if ( initialLoad ) {
            fetchProducts( search );
        }
    }, [ fetchProducts, initialLoad, search ] );

    // Search function that can be called from outside and returns products
    const searchProducts = useCallback(
        async ( term: string ) => {
            setSearch( term );
            return await fetchProducts( term );
        },
        [ fetchProducts ]
    );

    // Refresh function to reload the current search
    const refresh = useCallback( () => {
        fetchProducts( search );
    }, [ fetchProducts, search ] );

    // Grant access function
    const grantAccess = useCallback(
        async (
            orderId: number,
            productIds: number[]
        ): Promise< GrantAccessResponse > => {
            setIsGranting( true );
            setGrantError( null );

            try {
                return await apiFetch( {
                    path: `/dokan/v1/product-subscriptions/orders/${ orderId }/grant-download-access`,
                    method: 'POST',
                    data: {
                        product_ids: productIds,
                    },
                } );
            } catch ( err ) {
                const errorObj =
                    err instanceof Error
                        ? err
                        : new Error( 'Failed to grant download access' );
                setGrantError( errorObj );
                throw errorObj;
            } finally {
                setIsGranting( false );
            }
        },
        []
    );

    return {
        products,
        isLoading,
        error,
        searchProducts,
        refresh,
        grantAccess,
        isGranting,
        grantError,
    };
};
