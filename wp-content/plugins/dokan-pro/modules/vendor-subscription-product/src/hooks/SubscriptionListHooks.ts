import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { useCallback, useState } from '@wordpress/element';

export interface PaginatedApiResponse {
    body: any[]; // Replace 'any' with your data type
    headers: {
        total: number;
        totalPages: number;
    };
}

export interface FetchError {
    message: string;
    code?: string;
}

// Separate fetch function
export const fetchSubscriptionList = async (
    args: Record< any, any > = {}
): Promise< PaginatedApiResponse > => {
    const response: Record< any, any > = await apiFetch( {
        method: 'GET',
        path: addQueryArgs( '/dokan/v1/product-subscriptions', {
            ...args,
        } ),
        parse: false,
    } );

    const body = await response.json();

    return {
        body,
        headers: {
            total: Number( response.headers.get( 'x-wp-total' ) ),
            totalPages: Number( response.headers.get( 'x-wp-totalpages' ) ),
        },
    };
};

// Custom Hook
export const useSubscriptionList = () => {
    const [ data, setData ] = useState< any[] >( [] ); // Replace 'any' with your data type
    const [ isLoading, setIsLoading ] = useState< boolean >( false );
    const [ error, setError ] = useState< FetchError | null >( null );
    const [ totalPages, setTotalPages ] = useState< number >( 0 );
    const [ totalItems, setTotalItems ] = useState< number >( 0 );

    const loadData = useCallback(
        async ( args: Record< string, any > = {} ) => {
            setIsLoading( true );
            setError( null );

            try {
                const response = await fetchSubscriptionList( args );

                setTotalPages( response.headers.totalPages );
                setTotalItems( response.headers.total );
                setData( response.body || [] );
            } catch ( err ) {
                setError( {
                    message:
                        err instanceof Error
                            ? err.message
                            : 'Failed to fetch data',
                } );
                console.error( 'Error fetching data:', err );
            } finally {
                setIsLoading( false );
            }
        },
        [ setIsLoading, setError, setData, setTotalPages, setTotalItems ]
    );

    const fetchList = useCallback(
        ( args: Record< string, any > = {} ) => {
            return loadData( args );
        },
        [ loadData ]
    );

    return {
        data,
        isLoading,
        error,
        totalPages,
        totalItems,
        fetchList,
    };
};
