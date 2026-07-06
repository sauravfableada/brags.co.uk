import { __, sprintf } from '@wordpress/i18n';

// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { DokanLink, DokanBadge } from '@dokan/components';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { formatPrice } from '@dokan/utilities';

import '../../../../../../../src/definitions/window-types';
import { TicketHeaderProps } from '../../../types/store-support';
import { decodeEntities } from '@wordpress/html-entities';

export default function TicketHeader( { ticket }: TicketHeaderProps ) {
    return (
        <div className="dokan-support-single-title">
            { /* Header with title, ID and status - matches REST API structure */ }
            <div className="dokan-dss-chat-header bg-white border border-gray-300 rounded-lg p-4 mb-4">
                <div className="flex justify-between items-start">
                    <div className="dokan-chat-title-box flex-1">
                        <h2 className="dokan-chat-title text-lg font-semibold text-gray-900 mb-1">
                            { ticket.title }
                        </h2>
                        <span className="dokan-chat-status text-sm text-gray-600">
                            { sprintf(
                                /* translators: %s: ticket id */
                                __( '#%s', 'dokan' ),
                                ticket.id
                            ) }
                        </span>
                    </div>
                    <div className="dokan-chat-status-box">
                        { ticket.status === 'open' && (
                            <DokanBadge
                                variant="success"
                                label={ __( 'Open', 'dokan' ) }
                            />
                        ) }
                        { ticket.status === 'closed' && (
                            <DokanBadge
                                variant="danger"
                                label={ __( 'Closed', 'dokan' ) }
                            />
                        ) }
                    </div>
                </div>
            </div>

            { /* Order Reference Section - matches REST API order structure */ }
            { ticket.order && (
                <div className="order-reference bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
                    <h3 className="text-base font-medium text-gray-900 mb-0">
                        <DokanLink
                            href={ ticket.order.url || '#' }
                            className="text-blue-600 hover:text-blue-800 font-semibold"
                        >
                            { sprintf(
                                /* translators: %s: order number */
                                __( 'Referenced Order #%s', 'dokan' ),
                                ticket.order.number
                            ) }
                        </DokanLink>
                    </h3>
                    { ticket.order.status && (
                        <p className="text-sm text-gray-600 mt-1">
                            <span className="dokan-order-status">
                                { __( 'Status:', 'dokan' ) }{ ' ' }
                                { ticket.order.status }
                            </span>
                            { ticket.order.total && (
                                <span className="dokan-order-price ml-4">
                                    { __( 'Price:', 'dokan' ) }{ ' ' }
                                    { decodeEntities(
                                        formatPrice( ticket.order.total )
                                    ) }
                                </span>
                            ) }
                        </p>
                    ) }
                </div>
            ) }
        </div>
    );
}
