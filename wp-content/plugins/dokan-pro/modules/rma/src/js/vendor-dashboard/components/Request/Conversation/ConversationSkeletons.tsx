import { __ } from '@wordpress/i18n';
import { twMerge } from 'tailwind-merge';

import { Card } from '@getdokan/dokan-ui';

export function ConversationItemSkeleton( { isRight = false } ) {
    return (
        <div
            className={ twMerge(
                'border-b w-[85%] !my-2.5 !p-2.5 bg-[#eeee] relative',
                isRight ? 'self-end' : 'self-start'
            ) }
        >
            <div
                className={ twMerge(
                    "mt-1 after:content-[''] after:block after:absolute after:bottom-0 after:w-0 after:h-0 after:border-solid after:border-t-[#efefef] after:border-b-[#efefef] after:border-l-transparent after:border-r-transparent",
                    isRight
                        ? 'after:-right-4 border-t-0 after:border-r-[21px] after:border-l-0 after:border-b-[25px]'
                        : 'after:-left-4 border-t-0 after:border-r-0 after:border-l-[21px] after:border-b-[25px]'
                ) }
                style={ {
                    backgroundColor: '#f0f0f0',
                } }
            >
                <div className="h-4 w-32"></div>
            </div>
        </div>
    );
}

export function ConversationsListSkeleton() {
    return (
        <Card>
            <Card.Header className="px-4 py-2 flex justify-between items-center">
                <Card.Title className="p-0 m-0 mb-0">
                    { __( 'Conversations', 'dokan' ) }
                </Card.Title>
            </Card.Header>
            <Card.Body className="px-4 py-4">
                <div className="space-y-4 mb-4 !list-none m-0 !px-1.5 py-0 flex flex-col gap-4">
                    <ConversationItemSkeleton isRight={ false } />
                    <ConversationItemSkeleton isRight={ true } />
                    <ConversationItemSkeleton isRight={ false } />
                    <ConversationItemSkeleton isRight={ true } />
                </div>

                { /* Message Input Area */ }
                <div className="animate-pulse">
                    { /* Textarea */ }
                    <div className="w-full h-24 mb-4 rounded border border-gray-200 bg-gray-100"></div>

                    { /* Send Button */ }
                    <div className="animate-pulse">
                        <div className="h-10 bg-gray-200 rounded w-32"></div>
                    </div>
                </div>
            </Card.Body>
        </Card>
    );
}

export default ConversationsListSkeleton;
