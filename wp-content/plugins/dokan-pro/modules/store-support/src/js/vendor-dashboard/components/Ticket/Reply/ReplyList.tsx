import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { DokanButton } from '@dokan/components';

import { TextArea, useToast } from '@getdokan/dokan-ui';

import ReplyItem from './ReplyItem';
import { useTicketReply } from '../../../hooks/useTicketReply';
import {
    ReplyListProps,
    SupportTicketReply,
} from '../../../../types/store-support';

export default function ReplyList( { ticket, setTicket }: ReplyListProps ) {
    const [ message, setMessage ] = useState( '' );
    const [ statusChange, setStatusChange ] = useState( '0' ); // 0 = no change, 1 = close, 2 = open
    const toast = useToast();

    const { addReply, isPending } = useTicketReply( ticket );

    const handleOnChange = ( e: React.ChangeEvent< HTMLTextAreaElement > ) => {
        setMessage( e.target.value );
    };

    const handleStatusChange = ( value: string ) => {
        setStatusChange( value );
    };

    const handleSendReply = async ( e: React.FormEvent< HTMLFormElement > ) => {
        e.preventDefault();

        if ( ! message.trim() ) {
            toast( {
                title: __( 'Please enter a message', 'dokan' ),
                type: 'error',
            } );
            return;
        }

        try {
            // Convert statusChange to boolean closeTicket parameter
            const closeTicket = statusChange === '1';

            // Update ticket status if needed
            if ( setTicket ) {
                let newStatus = ticket.status;

                if ( closeTicket ) {
                    newStatus = 'closed';
                } else if ( ticket.status === 'closed' ) {
                    // If ticket is closed and we're adding a reply without explicitly closing it,
                    // it should reopen per REST API behavior
                    newStatus = 'open';
                }

                // Create a temporary new reply object based on response
                const newReply: SupportTicketReply = {
                    id: 0,
                    content: message,
                    date: new Date().toISOString(),
                    date_formatted: new Date().toLocaleString(),
                    author: {
                        id: Number(
                            window?.DokanStoreSupport?.currentUserId || 0
                        ),
                        name:
                            window?.DokanStoreSupport?.currentUserName ||
                            __( 'Vendor', 'dokan' ),
                        type: 'vendor',
                        avatar:
                            window?.DokanStoreSupport?.currentUserAvatar || '',
                    },
                    human_time_diff: __( 'just now', 'dokan' ),
                };

                // Update the ticket with new status and add the reply to the list
                setTicket( {
                    ...ticket,
                    status: newStatus,
                    replies: [ ...( ticket.replies || [] ), newReply ],
                } );
            }

            // Use addReply from the hook
            await addReply( message, closeTicket );
            setMessage( '' );
            setStatusChange( '0' );
        } catch ( err ) {
            toast( {
                title: __( 'Failed to send reply', 'dokan' ),
                type: 'error',
            } );

            setTicket( {
                ...ticket,
                replies: ticket.replies.filter(
                    ( reply: SupportTicketReply ) => reply.id !== 0
                ),
            } );
        }
    };

    // Check if ticket is closed and determine form availability
    const isTicketClosed = ticket.status === 'closed';

    return (
        <div className="dokan-support-reply-section space-y-6">
            { /* Replies List - matches REST API structure */ }
            <ul className="dokan-support-commentlist space-y-4 list-none p-0 m-0">
                { ! ticket.replies || ticket.replies.length === 0 ? (
                    <li className="text-center py-8 text-gray-500">
                        { __(
                            'No replies yet. Be the first to reply!',
                            'dokan'
                        ) }
                    </li>
                ) : (
                    ticket.replies.map( ( reply: SupportTicketReply ) => (
                        <ReplyItem key={ reply.id } reply={ reply } />
                    ) )
                ) }
            </ul>

            { /* Reply Form - follows PHP template pattern */ }
            <div className="dokan-panel dokan-panel-default dokan-dss-panel-default bg-white border border-gray-300 rounded-lg overflow-hidden">
                <div className="dokan-dss-panel-heading px-6 py-4 border-b border-gray-300">
                    <strong>
                        { isTicketClosed
                            ? __( 'Ticket Closed', 'dokan' )
                            : __( 'Add Reply', 'dokan' ) }
                    </strong>
                    { isTicketClosed && (
                        <em className="text-sm text-gray-600 ml-2">
                            { __(
                                '(Adding reply will re-open the ticket)',
                                'dokan'
                            ) }
                        </em>
                    ) }
                </div>
                <div className="dokan-dss-panel-body dokan-support-reply-form p-6">
                    <form onSubmit={ handleSendReply }>
                        <div className="mb-4">
                            <TextArea
                                className="dokan-dss-comment-textarea w-full border border-gray-300 rounded p-3 min-h-[120px]"
                                value={ message }
                                onChange={ handleOnChange }
                                input={ {
                                    id: 'dokan-support-reply-textarea',
                                    placeholder: __(
                                        'Write your reply…',
                                        'dokan'
                                    ),
                                    required: true,
                                } }
                            />
                        </div>

                        { ! isTicketClosed && (
                            <div className="mb-4">
                                <select
                                    className="dokan-support-topic-select dokan-form-control w-1/3 border border-gray-300 rounded p-2"
                                    onChange={ ( e ) =>
                                        handleStatusChange( e.target.value )
                                    }
                                    value={ statusChange }
                                >
                                    <option value="0">
                                        { __( '-- Change Status --', 'dokan' ) }
                                    </option>
                                    <option value="1">
                                        { __( 'Close Ticket', 'dokan' ) }
                                    </option>
                                </select>
                            </div>
                        ) }

                        <div className="text-right">
                            <DokanButton
                                type="submit"
                                isLoading={ isPending }
                                disabled={ isPending }
                            >
                                { __( 'Submit Reply', 'dokan' ) }
                            </DokanButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}
