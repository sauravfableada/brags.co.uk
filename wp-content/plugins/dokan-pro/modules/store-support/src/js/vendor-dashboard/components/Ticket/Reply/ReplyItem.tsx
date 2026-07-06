import { __ } from '@wordpress/i18n';

// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { DateTimeHtml, DokanBadge } from '@dokan/components';

import '../../../../../../../../src/definitions/window-types';
import { SupportTicketReply } from '../../../../types/store-support';
import { RawHTML } from '@wordpress/element';

type ReplyItemProps = {
    reply: SupportTicketReply;
};

// Helper function to determine badge variant
function getUserBadgeVariant( type: string ) {
    switch ( type ) {
        case 'admin':
            return 'info';
        case 'vendor':
            return 'primary';
        default:
            return 'secondary';
    }
}

// Helper functions for chat styling
function getUserTypeClass( type: string ) {
    switch ( type ) {
        case 'admin':
            return 'dokan-admin-chat-text';
        case 'vendor':
            return 'dokan-vendor-chat-text';
        default:
            return 'dokan-customer-chat-text';
    }
}

function getMessageBoxClass( type: string ) {
    switch ( type ) {
        case 'admin':
            return 'bg-purple-50 border border-purple-200';
        case 'vendor':
            return 'bg-blue-50 border border-blue-200';
        default:
            return 'bg-gray-50 border border-gray-200';
    }
}

export default function ReplyItem( { reply }: ReplyItemProps ) {
    const userType = getUserTypeClass( reply.author.type );
    const badgeVariant = getUserBadgeVariant( reply.author.type );
    const messageBoxClass = getMessageBoxClass( reply.author.type );

    const userName = reply.author.site_name || reply.author.name;

    // Determine badge label based on user type
    let badgeLabel = '';
    switch ( reply.author.type ) {
        case 'admin':
            badgeLabel = __( 'Admin', 'dokan' );
            break;
        case 'vendor':
            badgeLabel = __( 'Vendor', 'dokan' );
            break;
        default:
            badgeLabel = __( 'Customer', 'dokan' );
    }

    return (
        <li className="dokan-support-comment-item mb-6">
            <div className="dokan-dss-chat-box bg-white border border-gray-300 rounded-lg p-4">
                <div className="flex space-x-4">
                    { /* User Avatar */ }
                    <div className="dokan-chat-image-box flex-shrink-0">
                        <div className="dokan-chat-image-container">
                            <img
                                src={
                                    reply.author.avatar ||
                                    'https://secure.gravatar.com/avatar/?s=48&d=mm&r=g'
                                }
                                alt={ userName }
                                className="dokan-chat-image w-12 h-12 rounded-full border border-gray-200"
                            />
                        </div>
                    </div>

                    { /* Chat Content */ }
                    <div className="dokan-chat-info-box flex-1">
                        { /* Sender Info */ }
                        <div className="dokan-chat-sender-info mb-2">
                            <div className="dokan-chat-user-box">
                                <span className="chat-user font-medium text-gray-900">
                                    { userName }
                                </span>
                                <span className="ml-2">
                                    <DokanBadge
                                        variant={ badgeVariant }
                                        label={ badgeLabel }
                                    />
                                </span>
                            </div>
                        </div>

                        { /* Message Content */ }
                        <div className={ `dokan-chat-text mb-3 ${ userType }` }>
                            <div
                                className={ `p-3 rounded-lg ${ messageBoxClass }` }
                            >
                                <RawHTML className="prose max-w-none text-sm text-gray-800 whitespace-pre-line">
                                    { reply.content }
                                </RawHTML>
                            </div>
                        </div>

                        { /* Timestamp */ }
                        <div className="dokan-chat-time-box">
                            <span className="dokan-chat-time text-xs text-gray-500">
                                <time>
                                    <DateTimeHtml date={ reply.date } />
                                    { reply.human_time_diff && (
                                        <span className="human-diff ml-1 text-gray-400">
                                            ({ reply.human_time_diff })
                                        </span>
                                    ) }
                                </time>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </li>
    );
}
