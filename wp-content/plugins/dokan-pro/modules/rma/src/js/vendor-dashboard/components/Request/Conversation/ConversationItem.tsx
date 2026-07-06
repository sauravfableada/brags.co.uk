import { twMerge } from 'tailwind-merge';

import '../../../../../../../../src/definitions/window-types';
import { WarrantyRequestConversation } from '../../../../types/warranty-request';

type ConversationItemProps = {
    conversation: WarrantyRequestConversation;
};

// prettier-ignore
export default function ConversationItem( { conversation }: ConversationItemProps ) {
    const { currentUserId } = window.DokanRMAPanel;
    return (
        <li
            className={ twMerge(
                'border-b w-[85%] !my-2.5 !p-2.5 bg-[#eeee] relative',
                conversation.from === currentUserId ? 'self-end' : 'self-start'
            ) }
        >
            <p className={twMerge(
                'mt-1 after:content-[\'\'] after:block after:absolute after:bottom-0 after:w-0 after:h-0 after:border-solid after:border-t-[#efefef] after:border-b-[#efefef] after:border-l-transparent after:border-r-transparent',
                conversation.from === currentUserId ? 'after:-right-4 border-t-0 after:border-r-[21px] after:border-l-0 after:border-b-[25px]' : 'after:-left-4 border-t-0 after:border-r-0 after:border-l-[21px] after:border-b-[25px]',
            )}>
                { conversation.message }
            </p>
        </li>
    );
}
