import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { format, getSettings } from '@wordpress/date';

interface SubscriptionDate {
    date: string;
    label: string;
}

interface SubscriptionSchedule {
    id: number;
    billing_interval: number;
    billing_period: string;
    dates: Record< string, SubscriptionDate >;
    timezone: string;
}

interface UpdateScheduleParams {
    billing_interval?: number | string;
    billing_period?: string;
    dates?: Record< string, string | number >;
}

interface UseSubscriptionSchedulerReturn {
    isUpdating: boolean;
    error: Error | null;
    updateSchedule: (
        params: UpdateScheduleParams
    ) => Promise< SubscriptionSchedule >;
    clearError: () => void;
    getUnixTimestamp: ( date: string | null ) => number;
    formatTimestamp: ( timestamp: number ) => string;
}

export const useSubscriptionScheduler = (
    subscriptionId: number
): UseSubscriptionSchedulerReturn => {
    const [ isUpdating, setIsUpdating ] = useState( false );
    const [ error, setError ] = useState< Error | null >( null );

    const getUnixTimestamp = ( dateString = null ) => {
        // If no date provided, use current time
        if ( ! dateString ) {
            return Math.floor( Date.now() / 1000 );
        }

        // Convert date string to timestamp (in seconds)
        return Math.floor( new Date( dateString ).getTime() / 1000 );
    };

    // Format a Unix timestamp using WordPress date settings
    const formatTimestamp = ( timestamp: number ) => {
        const settings = getSettings();
        return format(
            settings.formats.datetime || 'F j, Y H:i:s',
            String( timestamp * 1000 ) // Convert seconds to milliseconds
        );
    };

    const updateSchedule = async (
        params: UpdateScheduleParams
    ): Promise< SubscriptionSchedule > => {
        setIsUpdating( true );
        setError( null );

        try {
            const response = await apiFetch( {
                path: `/dokan/v1/product-subscriptions/${ subscriptionId }/schedule`,
                method: 'PUT',
                data: params,
            } );

            // @ts-ignore
            if ( ! response.success ) {
                throw new Error(
                    // @ts-ignore
                    response.message || 'Failed to update subscription schedule'
                );
            }

            // @ts-ignore
            return response.subscription;
        } catch ( err ) {
            const errorMessage =
                err instanceof Error
                    ? err.message
                    : 'An error occurred while updating the subscription schedule';
            setError( new Error( errorMessage ) );
            throw err;
        } finally {
            setIsUpdating( false );
        }
    };

    const clearError = () => setError( null );

    return {
        isUpdating,
        error,
        updateSchedule,
        clearError,
        getUnixTimestamp,
        formatTimestamp,
    };
};
