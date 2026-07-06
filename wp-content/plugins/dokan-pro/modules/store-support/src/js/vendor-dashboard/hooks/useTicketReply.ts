import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

import {
    SupportTicket,
    SupportTicketReply,
    UseTicketReplyReturn,
} from '../../types/store-support';

export const useTicketReply = (
    ticket: SupportTicket
): UseTicketReplyReturn => {
    const [ replies, setReplies ] = useState< SupportTicketReply[] >(
        ticket.replies
    );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ isPending, setIsPending ] = useState( false );

    const fetchReplies = async () => {
        setIsLoading( true );
        try {
            const supportTicket = await apiFetch< SupportTicket >( {
                path: `/dokan/v1/vendor/support-tickets/${ ticket.id }`,
                method: 'GET',
            } );

            // The API returns replies as part of the ticket object
            setReplies( supportTicket.replies || [] );
        } catch ( err ) {
            setReplies( [] );
        } finally {
            setIsLoading( false );
        }
    };

    const addReply = async (
        message: string,
        closeTicket: boolean = false
    ) => {
        setIsPending( true );
        try {
            return await apiFetch< any >( {
                path: `/dokan/v1/vendor/support-tickets/${ ticket.id }/replies`,
                method: 'POST',
                data: { message, close_ticket: closeTicket },
            } );
        } catch ( err ) {
            throw err;
        } finally {
            setIsPending( false );
        }
    };

    const editReply = async ( replyId: number, message: string ) => {
        setIsPending( true );
        try {
            const response = await apiFetch< any >( {
                path: `/dokan/v1/vendor/support-tickets/${ ticket.id }/replies/${ replyId }`,
                method: 'PUT',
                data: { message },
            } );

            await fetchReplies();
            return response;
        } catch ( err ) {
            throw err;
        } finally {
            setIsPending( false );
        }
    };

    const deleteReply = async ( replyId: number ) => {
        setIsPending( true );
        try {
            const response = await apiFetch< any >( {
                path: `/dokan/v1/vendor/support-tickets/${ ticket.id }/replies/${ replyId }`,
                method: 'DELETE',
            } );

            await fetchReplies();
            return response;
        } catch ( err ) {
            throw err;
        } finally {
            setIsPending( false );
        }
    };

    return {
        replies,
        isLoading,
        addReply,
        editReply,
        deleteReply,
        isPending,
    };
};
