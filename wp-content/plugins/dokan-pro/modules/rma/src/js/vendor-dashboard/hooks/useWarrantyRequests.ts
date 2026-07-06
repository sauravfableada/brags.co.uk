import { useState } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';

import { WarrantyRequest } from '../../types/warranty-request';

type ApiFetchResponse = {
    json: () => WarrantyRequest[];
    headers: Response[ 'headers' ] & {
        get: ( key: 'X-WP-Total' | 'X-WP-TotalPages' ) => string | null;
    };
};

type FilterArgs = {
    page: number | string;
    per_page: number | string;
    selectedDate?: string;
    selectedCustomer?: string;
    status?: string;
};

type UseWarrantyRequestsReturn = {
    requests: WarrantyRequest[];
    isLoading: boolean;
    fetchRequests: ( status: string ) => Promise< void >;
    deleteRequest: ( requestId: number ) => Promise< any >;
    totalItems: number;
    totalPages: number;
};

export const useWarrantyRequests = (
    initialFilterArgs: FilterArgs
): UseWarrantyRequestsReturn => {
    const [ requests, setRequests ] = useState< WarrantyRequest[] >( [] );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ totalItems, setTotalItems ] = useState( 0 );
    const [ totalPages, setTotalPages ] = useState( 0 );

    const fetchRequests = async ( status: string ) => {
        setIsLoading( true );

        try {
            const response = await apiFetch< Promise< ApiFetchResponse > >( {
                path: addQueryArgs( '/dokan/v1/rma/warranty-requests', {
                    status,
                    ...initialFilterArgs,
                } ),
                parse: false,
            } );

            const data = await response.json();
            const total = response.headers.get( 'X-WP-Total' );
            const pages = response.headers.get( 'X-WP-TotalPages' );

            setRequests( data );
            setTotalItems( total ? parseInt( total, 10 ) : 0 );
            setTotalPages( pages ? parseInt( pages, 10 ) : 0 );
        } catch ( err ) {
            window.console.error( 'Error fetching warranty requests:', err );
            return err;
        } finally {
            setIsLoading( false );
        }
    };

    const deleteRequest = async ( requestId: number ) => {
        setIsLoading( true );

        try {
            return await apiFetch( {
                path: `/dokan/v1/rma/warranty-requests/${ requestId }`,
                method: 'DELETE',
            } );
        } catch ( err ) {
            return err;
        } finally {
            setIsLoading( false );
        }
    };

    return {
        requests,
        isLoading,
        fetchRequests,
        deleteRequest,
        totalItems,
        totalPages,
    };
};
