import { useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { DokanSubscription } from '../Types';

interface UpdateStatusResponse extends DokanSubscription {}

interface UseSubscriptionStatusReturn {
    updateStatus: ( newStatus: string ) => Promise< UpdateStatusResponse >;
    isUpdating: boolean;
    error: Error | null;
    statusResponse: UpdateStatusResponse | unknown;
}

/**
 * Hook for managing subscription status updates
 * @param subscriptionId
 */
const useUpdateSubscriptionStatus = (
    subscriptionId: number
): UseSubscriptionStatusReturn => {
    const [ isUpdating, setIsUpdating ] = useState< boolean >( false );
    const [ error, setError ] = useState< Error | null >( null );
    const [ statusResponse, setStatusResponse ] = useState<
        UpdateStatusResponse | unknown
    >( null );

    const updateStatus = useCallback(
        async ( newStatus: string ): Promise< UpdateStatusResponse > => {
            setIsUpdating( true );
            setError( null );

            try {
                const response: DokanSubscription = await apiFetch( {
                    path: `/dokan/v1/product-subscriptions/${ subscriptionId }`,
                    method: 'PUT',
                    data: {
                        status: newStatus,
                    },
                } );

                setStatusResponse( response );
                return response;
            } catch ( err ) {
                // eslint-disable-next-line @typescript-eslint/no-shadow
                const error =
                    err instanceof Error
                        ? err
                        : new Error( 'Failed to update subscription status' );
                setError( error );
                throw error;
            } finally {
                setIsUpdating( false );
            }
        },
        [ subscriptionId ]
    );

    return {
        updateStatus,
        isUpdating,
        error,
        statusResponse,
    };
};

export default useUpdateSubscriptionStatus;
