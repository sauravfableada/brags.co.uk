import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { DokanButton } from '@dokan/components';

import { TextArea, Card, useToast } from '@getdokan/dokan-ui';

import ConversationItem from './ConversationItem';
import { useWarrantyConversation } from '../../../hooks/useWarrantyConversation';
import { WarrantyRequest } from '../../../../types/warranty-request';

type ConversationsListProps = {
    request: WarrantyRequest;
};

export default function ConversationsList( {
    request,
}: ConversationsListProps ) {
    const [ message, setMessage ] = useState( '' );
    const toast = useToast();

    const { conversations, isLoading, fetchConversations, sendMessage } =
        useWarrantyConversation();

    useEffect( () => {
        if ( request?.id ) {
            void fetchConversations( request?.id );
        }
    }, [ request?.id ] );

    const handleOnChange = ( e: React.ChangeEvent< HTMLTextAreaElement > ) => {
        setMessage( e.target.value );
    };

    const handleSendMessage = async (
        e: React.FormEvent< HTMLFormElement >
    ) => {
        e.preventDefault();

        try {
            await sendMessage( request, message );
            setMessage( '' );
        } catch ( err ) {
            toast( {
                title: __( 'Failed to send message', 'dokan' ),
                type: 'error',
            } );
        }
    };

    // Textarea options
    const TextAreaOptions = {
        placeholder: __( 'Type your message here', 'dokan' ),
        value: message,
        rows: 4,
        disabled: isLoading,
    };

    return (
        <Card>
            <Card.Header className="px-4 py-2 flex justify-between items-center">
                <Card.Title className="p-0 m-0 mb-0">
                    { __( 'Conversations', 'dokan' ) }
                </Card.Title>
            </Card.Header>
            <Card.Body className="px-4 py-4">
                { conversations.length === 0 && (
                    <p>{ __( 'No conversations found', 'dokan' ) }</p>
                ) }
                <ul className="space-y-4 mb-4 !list-none m-0 !px-1.5 py-0 flex flex-col gap-4">
                    { conversations.map( ( conversation ) => (
                        <ConversationItem
                            key={ conversation.id }
                            conversation={ conversation }
                        />
                    ) ) }
                </ul>

                <form onSubmit={ handleSendMessage }>
                    <TextArea
                        input={ TextAreaOptions }
                        onChange={ handleOnChange }
                        className="mb-4"
                    />
                    <DokanButton
                        type="submit"
                        loading={ isLoading }
                        disabled={ ! message.trim() || isLoading }
                    >
                        { __( 'Send Message', 'dokan' ) }
                    </DokanButton>
                </form>
            </Card.Body>
        </Card>
    );
}
