import { __ } from '@wordpress/i18n';

import { Card } from '@getdokan/dokan-ui';

export default function OrderSectionSkeleton() {
    return (
        <Card className="dokan-rma-order-section transition-all duration-200 ease-in-out">
            <Card.Header className="px-4 py-2 flex justify-between items-center">
                <Card.Title className="p-0 m-0 mb-0">
                    { __( 'Details', 'dokan' ) }
                </Card.Title>
            </Card.Header>
            <Card.Body className="px-4 py-4">
                <table className="w-full !border-collapse !border !border-gray-400 dokan-rma-reset-table">
                    <tbody>
                        { /* Order ID Row */ }
                        <tr className="!border !border-gray-300">
                            <td className="!py-3 !px-2 !border text-gray-700 align-top w-1/4">
                                <span className="text-sm font-medium">
                                    { __( 'Order ID:', 'dokan' ) }
                                </span>
                            </td>
                            <td className="!py-3 !px-2 !border">
                                <div className="animate-pulse">
                                    <div className="h-4 bg-gray-200 rounded w-24"></div>
                                </div>
                            </td>
                        </tr>
                        { /* Customer Name Row */ }
                        <tr className="!border !border-gray-300">
                            <td className="!py-3 !px-2 !border text-gray-700 align-top">
                                <span className="text-sm font-medium">
                                    { __( 'Customer Name:', 'dokan' ) }
                                </span>
                            </td>
                            <td className="!py-3 !px-2 !border">
                                <div className="animate-pulse">
                                    <div className="h-4 bg-gray-200 rounded w-40"></div>
                                </div>
                            </td>
                        </tr>
                        { /* Request Type Row */ }
                        <tr className="!border !border-gray-300">
                            <td className="!py-3 !px-2 !border text-gray-700 align-top">
                                <span className="text-sm font-medium">
                                    { __( 'Request Type:', 'dokan' ) }
                                </span>
                            </td>
                            <td className="!py-3 !px-2 !border">
                                <div className="animate-pulse">
                                    <div className="h-4 bg-gray-200 rounded w-32"></div>
                                </div>
                            </td>
                        </tr>
                        { /* Products Row */ }
                        <tr className="!border !border-gray-300">
                            <td className="!py-3 !px-2 !border text-gray-700 align-top">
                                <span className="text-sm font-medium">
                                    { __( 'Products:', 'dokan' ) }
                                </span>
                            </td>
                            <td className="!py-3 !px-2 !border">
                                <div className="animate-pulse space-y-2">
                                    <div className="h-4 bg-gray-200 rounded w-48"></div>
                                    <div className="h-4 bg-gray-200 rounded w-40"></div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div className="mt-8">
                    <div className="mb-4 font-medium">
                        { __( 'Additional Details', 'dokan' ) }
                    </div>
                    <div>
                        <div className="mb-4">
                            <div className="text-sm font-medium mb-1">
                                { __( 'Reason', 'dokan' ) }
                            </div>
                            <div className="animate-pulse">
                                <div className="h-4 bg-gray-200 rounded w-3/4"></div>
                            </div>
                        </div>
                        <div>
                            <div className="text-sm font-medium mb-1">
                                { __( 'Reason Details', 'dokan' ) }
                            </div>
                            <div className="animate-pulse space-y-2">
                                <div className="h-4 bg-gray-200 rounded w-full"></div>
                                <div className="h-4 bg-gray-200 rounded w-5/6"></div>
                                <div className="h-4 bg-gray-200 rounded w-4/6"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </Card.Body>
        </Card>
    );
}
