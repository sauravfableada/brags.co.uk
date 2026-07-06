import { RawHTML } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { DokanPriceInput, DokanLink } from '@dokan/components';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { formatPrice } from '@dokan/utilities';

import { WarrantyProduct } from '../../../types/warranty-request';

interface TableItemProps {
    item: WarrantyProduct;
    taxEnabled: boolean;
    totalAmount: Record< string, string >;
    totalTaxAmount: Record< string, string >;
    isLoading: boolean;
    updateTaxAmount: ( itemId: string, value: string ) => void;
    updateTotalAmount: ( itemId: string, value: string ) => void;
    updateAmounts: ( itemKey: string, itemId: string, value: number ) => void;
    actionType: 'refund' | 'coupon';
}

interface WarrantyItemsTableProps {
    items: WarrantyProduct[];
    taxEnabled: boolean;
    totalAmount: Record< string, string >;
    totalTaxAmount: Record< string, string >;
    isLoading: boolean;
    updateTaxAmount: ( itemId: string, value: string ) => void;
    updateTotalAmount: ( itemId: string, value: string ) => void;
    updateAmounts: ( itemKey: string, itemId: string, value: number ) => void;
    actionType: 'refund' | 'coupon';
    totalRefund: number;
}

export const TableItem = ( {
    actionType,
    item,
    taxEnabled,
    totalAmount,
    totalTaxAmount,
    updateTaxAmount,
    updateTotalAmount,
    updateAmounts,
    isLoading,
}: TableItemProps ) => {
    return (
        <tr key={ item.item_id } className="!border !border-gray-300">
            <td className="!py-4 !px-4 !border text-sm">
                <DokanLink href={ item.url } className="font-medium">
                    { item.title }
                </DokanLink>
            </td>
            <td className="!py-4 !px-4 !border text-sm text-center">
                { item.quantity }
            </td>
            { taxEnabled && (
                <td className="!py-4 !px-4 !border text-sm text-right">
                    <RawHTML>
                        { formatPrice(
                            item.tax * parseFloat( item.quantity )
                        ) }
                    </RawHTML>
                </td>
            ) }
            <td className="!py-4 !px-4 !border text-sm text-right">
                <RawHTML>
                    { formatPrice( Number( item.quantity ) * item.price ) }
                </RawHTML>
            </td>
            { taxEnabled && (
                <td className="!py-4 !px-4 !border">
                    <div className="relative">
                        <DokanPriceInput
                            namespace={ `rma-${ actionType }-tax` }
                            label=""
                            value={ totalTaxAmount[ item.item_id ] ?? '0' }
                            onChange={ ( value: string, amount: number ) => {
                                updateTaxAmount( item.item_id, value );
                                updateAmounts(
                                    'refund_tax',
                                    item.item_id,
                                    amount
                                );
                            } }
                            input={ {
                                id: 'refund_tax',
                                name: 'refund_tax',
                                required: false,
                                placeholder: formatPrice( '0.00' ),
                                disabled: isLoading,
                            } }
                        />
                    </div>
                </td>
            ) }
            <td className="!py-4 !px-4 !border">
                <div className="relative">
                    <DokanPriceInput
                        namespace={ `rma-${ actionType }-total` }
                        label=""
                        value={ totalAmount[ item.item_id ] ?? '0' }
                        onChange={ ( value: string, amount: number ) => {
                            updateTotalAmount( item.item_id, value );
                            updateAmounts(
                                'refund_total',
                                item.item_id,
                                amount
                            );
                        } }
                        input={ {
                            id: 'refund_total',
                            name: 'refund_total',
                            required: true,
                            placeholder: formatPrice( '0.00' ),
                            disabled: isLoading,
                        } }
                    />
                </div>
            </td>
        </tr>
    );
};

export const WarrantyItemsTable = ( {
    items,
    taxEnabled,
    totalAmount,
    totalTaxAmount,
    isLoading,
    updateTaxAmount,
    updateTotalAmount,
    updateAmounts,
    actionType,
    totalRefund,
}: WarrantyItemsTableProps ) => {
    const taxActionLabel =
        actionType === 'refund'
            ? __( 'Tax Refund', 'dokan' )
            : __( 'Tax Credit', 'dokan' );
    const totalActionLabel =
        actionType === 'refund'
            ? __( 'Total Refund', 'dokan' )
            : __( 'Store Credit', 'dokan' );

    return (
        <>
            <table className="w-full !border-collapse !border mb-6">
                <thead>
                    <tr className="!border !border-gray-300">
                        <th
                            className="!py-3 !px-4 !border text-left text-sm font-semibold text-gray-900"
                            style={ {
                                width: taxEnabled ? '25%' : '30%',
                            } }
                        >
                            { __( 'Product', 'dokan' ) }
                        </th>
                        <th
                            className="!py-3 !px-4 !border text-center text-sm font-semibold text-gray-900"
                            style={ { width: '10%' } }
                        >
                            { __( 'Qty', 'dokan' ) }
                        </th>
                        { taxEnabled && (
                            <th
                                className="!py-3 !px-4 !border text-right text-sm font-semibold text-gray-900"
                                style={ { width: '15%' } }
                            >
                                { __( 'Tax', 'dokan' ) }
                            </th>
                        ) }
                        <th
                            className="!py-3 !px-4 !border text-right text-sm font-semibold text-gray-900"
                            style={ { width: '15%' } }
                        >
                            { __( 'Total', 'dokan' ) }
                        </th>
                        { taxEnabled && (
                            <th
                                className="!py-3 !px-4 !border text-right text-sm font-semibold text-gray-900"
                                style={ { width: '15%' } }
                            >
                                { taxActionLabel }
                            </th>
                        ) }
                        <th
                            className="!py-3 !px-4 !border text-right text-sm font-semibold text-gray-900"
                            style={ { width: '20%' } }
                        >
                            { totalActionLabel }
                        </th>
                    </tr>
                </thead>
                <tbody>
                    { items.map( ( item ) => (
                        <TableItem
                            key={ item.item_id }
                            actionType={ actionType }
                            item={ item }
                            taxEnabled={ taxEnabled }
                            totalAmount={ totalAmount }
                            totalTaxAmount={ totalTaxAmount }
                            updateTaxAmount={ updateTaxAmount }
                            updateTotalAmount={ updateTotalAmount }
                            updateAmounts={ updateAmounts }
                            isLoading={ isLoading }
                        />
                    ) ) }
                </tbody>
            </table>

            <div className="flex justify-end items-center border-gray-200 pt-4">
                <div className="text-right dokan-rma-total-refund">
                    <div className="text-sm text-gray-700">
                        { __( 'Total Amount:', 'dokan' ) }
                        <RawHTML className="ml-2 text-lg inline-block">
                            { formatPrice( totalRefund || 0 ) }
                        </RawHTML>
                    </div>
                </div>
            </div>
        </>
    );
};

export default WarrantyItemsTable;
