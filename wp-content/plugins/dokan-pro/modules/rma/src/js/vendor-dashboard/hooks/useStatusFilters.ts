import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

type StatusFilter = {
    name: string;
    label: string;
    count: number;
};

type UseStatusFiltersReturn = {
    filters: StatusFilter[];
    isLoading: boolean;
    fetchFilters: () => Promise< void >;
};

export const useStatusFilters = (): UseStatusFiltersReturn => {
    const [ filters, setFilters ] = useState< StatusFilter[] >( [] );
    const [ isLoading, setIsLoading ] = useState( true );

    const fetchFilters = async () => {
        setIsLoading( true );

        try {
            const response = await apiFetch< StatusFilter[] >( {
                path: '/dokan/v1/rma/warranty-requests/statuses-filter',
            } );

            setFilters( response );
        } catch ( err ) {
            throw err;
        } finally {
            setIsLoading( false );
        }
    };

    return {
        filters,
        isLoading,
        fetchFilters,
    };
};
