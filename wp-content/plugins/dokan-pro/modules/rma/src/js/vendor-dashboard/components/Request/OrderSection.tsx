import { __, sprintf } from '@wordpress/i18n';

import { Card } from '@getdokan/dokan-ui';

// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { DokanLink } from '@dokan/components';

import '../../../../../../../src/definitions/window-types';
import { WarrantyRequest } from '../../../types/warranty-request';

type OrderSectionProps = {
    request: WarrantyRequest;
};

export default function OrderSection( { request }: OrderSectionProps ) {
    const { orderUrl } = window.DokanRMAPanel;

    return (
        <Card className="dokan-rma-order-section">
            <Card.Header className="px-4 py-2 flex justify-between items-center">
                <Card.Title className="p-0 m-0 mb-0">
                    { __( 'Details', 'dokan' ) }
                </Card.Title>
            </Card.Header>
            <Card.Body className="px-4 py-4">
                <table className="w-full !border-collapse !border !border-gray-400 dokan-rma-reset-table">
                    <tbody>
                        <tr className="!border !border-gray-300">
                            <td className="!py-3 !px-2 !border text-gray-700 align-top w-1/4">
                                <span className="text-sm font-medium">
                                    { __( 'Order ID:', 'dokan' ) }
                                </span>
                            </td>
                            <td className="!py-3 !px-2 !border !text-sm">
                                <DokanLink
                                    href={ orderUrl.replace(
                                        '%7B%7BORDER_ID%7D%7D',
                                        request.order_id
                                    ) }
                                    className="font-medium"
                                >
                                    { sprintf(
                                        /* translators: %s: order id */
                                        __( 'Order #%s', 'dokan' ),
                                        request.order_id
                                    ) }
                                </DokanLink>
                            </td>
                        </tr>
                        <tr className="!border !border-gray-300">
                            <td className="!py-3 !px-2 !border text-gray-700 align-top">
                                <span className="text-sm font-medium">
                                    { __( 'Customer Name:', 'dokan' ) }
                                </span>
                            </td>
                            <td className="!py-3 !px-2 !border !text-sm">
                                { request.customer.name }
                            </td>
                        </tr>
                        <tr className="!border !border-gray-300">
                            <td className="!py-3 !px-2 !border text-gray-700 align-top">
                                <span className="text-sm font-medium">
                                    { __( 'Request Type:', 'dokan' ) }
                                </span>
                            </td>
                            <td className="!py-3 !px-2 !border !text-sm">
                                { request.type_label }
                            </td>
                        </tr>
                        <tr className="!border !border-gray-300">
                            <td className="!py-3 !px-2 !border text-gray-800 align-top">
                                <span className="text-sm font-medium">
                                    { __( 'Products:', 'dokan' ) }
                                </span>
                            </td>
                            <td className="!py-3 !px-2 !border !text-sm">
                                { request.items.map( ( item ) => (
                                    <div key={ item.id }>
                                        <DokanLink href={ item.url }>
                                            { item.title }
                                        </DokanLink>{ ' ' }
                                        × { item.quantity }
                                    </div>
                                ) ) }
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div className="mt-8">
                    <div className="mb-4 font-medium">
                        { __( 'Additional Details', 'dokan' ) }
                    </div>
                    <div>
                        { request.reasons && (
                            <div className="mb-4">
                                <div className="text-sm font-medium mb-1">
                                    { __( 'Reason', 'dokan' ) }
                                </div>
                                <div className="text-sm">
                                    { request.reasons_label }
                                </div>
                            </div>
                        ) }
                        <div>
                            <div className="text-sm font-medium mb-1">
                                { __( 'Reason Details', 'dokan' ) }
                            </div>
                            <div className="text-sm">{ request.details }</div>
                        </div>
                    </div>
                </div>
            </Card.Body>
        </Card>
    );
}
