import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

import { WarrantyRequest } from '../../types/warranty-request';

type UseWarrantyRequestReturn = {
    request: WarrantyRequest;
    setRequest: ( request: WarrantyRequest ) => void;
    isLoading: boolean;
    fetchRequest: () => Promise< void >;
    isNotFound: boolean;
    isNotPermitted: boolean;
};

export const useWarrantyRequest = (
    requestId: string
): UseWarrantyRequestReturn => {
    const [ request, setRequest ] = useState< WarrantyRequest | null >( null );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ isNotFound, setIsNotFound ] = useState( false );
    const [ isNotPermitted, setIsNotPermitted ] = useState( false );

    const fetchRequest = async () => {
        setIsLoading( true );

        try {
            const requestData = await apiFetch< WarrantyRequest >( {
                path: `/dokan/v1/rma/warranty-requests/${ requestId }`,
            } );
            setRequest( requestData );
        } catch ( err ) {
            if ( err?.data?.status === 404 ) {
                setIsNotFound( true );
            }
            if ( err?.data?.status === 403 ) {
                setIsNotPermitted( true );
            }
        } finally {
            setIsLoading( false );
        }
    };

    return {
        request,
        setRequest,
        isLoading,
        fetchRequest,
        isNotFound,
        isNotPermitted,
    };
};
