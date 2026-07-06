import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

import { WarrantyRequestStatuses } from '../../types/warranty-request';

type UseWarrantyStatusesReturn = {
    statuses: WarrantyRequestStatuses;
    isLoading: boolean;
    fetchStatuses: () => Promise< void >;
};

export const useWarrantyStatuses = (): UseWarrantyStatusesReturn => {
    const [ statuses, setStatuses ] = useState< WarrantyRequestStatuses >( {} );
    const [ isLoading, setIsLoading ] = useState( true );

    const fetchStatuses = async () => {
        setIsLoading( true );

        try {
            const statusesData = await apiFetch< WarrantyRequestStatuses >( {
                path: '/dokan/v1/rma/warranty-requests/statuses',
            } );

            setStatuses( statusesData );
        } catch ( err ) {
            throw err;
        } finally {
            setIsLoading( false );
        }
    };

    return {
        statuses,
        isLoading,
        fetchStatuses,
    };
};
