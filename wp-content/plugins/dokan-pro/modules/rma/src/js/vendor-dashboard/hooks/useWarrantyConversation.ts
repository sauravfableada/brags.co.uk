import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

import {
    WarrantyRequest,
    WarrantyRequestConversation,
} from '../../types/warranty-request';

type UseWarrantyConversationReturn = {
    conversations: WarrantyRequestConversation[];
    isLoading: boolean;
    fetchConversations: ( requestId: string | number ) => Promise< void >;
    sendMessage: (
        request: WarrantyRequest,
        message: string
    ) => Promise< void >;
};

export const useWarrantyConversation = (): UseWarrantyConversationReturn => {
    const [ conversations, setConversations ] = useState<
        WarrantyRequestConversation[]
    >( [] );
    const [ isLoading, setIsLoading ] = useState( false );
    const [ error, setError ] = useState< Error | null >( null );

    const fetchConversations = async ( requestId: string | number ) => {
        setIsLoading( true );
        setError( null );

        try {
            const response = await apiFetch< WarrantyRequestConversation[] >( {
                path: `/dokan/v1/rma/warranty-requests/${ requestId }/conversations`,
            } );
            setConversations( response );
        } catch ( err ) {
            setError(
                err instanceof Error
                    ? err
                    : new Error( 'Failed to fetch conversations' )
            );
            throw error;
        } finally {
            setIsLoading( false );
        }
    };

    const sendMessage = async ( request: WarrantyRequest, message: string ) => {
        if ( ! message.trim() ) {
            return;
        }

        setIsLoading( true );

        // Update conversions
        setConversations( ( prevState ) => [
            ...prevState,
            {
                to: request?.customer?.id?.toString(),
                from: request?.vendor?.store_id?.toString(),
                message,
                created_at: '',
                id: 0,
            },
        ] );

        try {
            await apiFetch( {
                path: `/dokan/v1/rma/warranty-requests/${ request?.id }/conversations`,
                method: 'POST',
                data: { message },
            } );

            // Refresh conversations after stored messages
            await fetchConversations( request?.id );
        } catch ( err ) {
            // restore preview message on error.
            setConversations( ( prevState ) =>
                prevState.filter( ( conversation ) => conversation.id !== 0 )
            );

            throw err;
        } finally {
            setIsLoading( false );
        }
    };

    return {
        conversations,
        isLoading,
        fetchConversations,
        sendMessage,
    };
};
