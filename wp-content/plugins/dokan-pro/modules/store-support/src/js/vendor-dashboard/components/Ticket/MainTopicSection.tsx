import { __ } from '@wordpress/i18n';

// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { DateTimeHtml } from '@dokan/components';

import { MainTopicSectionProps } from '../../../types/store-support';
import { RawHTML } from '@wordpress/element';

export default function MainTopicSection( { ticket }: MainTopicSectionProps ) {
    return (
        <div className="dokan-dss-chat-box main-topic bg-white border border-gray-300 rounded-lg p-6">
            <div className="flex space-x-4">
                { /* Customer Avatar - matches REST API customer object structure */ }
                <div className="dokan-chat-image-box flex-shrink-0">
                    <div className="dokan-chat-image-container dokan-chat-image-container-topic">
                        <img
                            src={
                                ticket.customer?.avatar ??
                                `https://www.gravatar.com/avatar/?s=90&d=identicon`
                            }
                            alt={ ticket.customer?.name ?? '' }
                            className="w-20 h-20 rounded-full border-2 border-gray-200"
                        />
                    </div>
                </div>

                { /* Chat Info Box - matches REST API data structure */ }
                <div className="dokan-chat-info-box flex-1">
                    { /* Sender Info */ }
                    <div className="dokan-chat-sender-info mb-3">
                        <div className="dokan-chat-user-box">
                            <span className="chat-user text-lg font-semibold text-gray-900">
                                { ticket.customer?.name ??
                                    __( 'Unknown', 'dokan' ) }
                            </span>
                            { ticket.customer?.email && (
                                <span className="text-sm text-gray-500 ml-2">
                                    ({ ticket.customer?.email })
                                </span>
                            ) }
                        </div>
                    </div>

                    { /* Main Topic Content */ }
                    <div className="dokan-chat-text dokan-customer-chat-text mb-4">
                        <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <RawHTML className="prose max-w-none text-gray-800 whitespace-pre-line">
                                { ticket.content }
                            </RawHTML>
                        </div>
                    </div>

                    { /* Timestamp */ }
                    <div className="dokan-chat-time-box">
                        <span className="dokan-chat-time text-sm text-gray-500">
                            <DateTimeHtml date={ ticket.date_created } />
                        </span>
                    </div>
                </div>
            </div>
        </div>
    );
}
