import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

type SuccessResponse = {
    message: string;
};

type RefundItem = {
    item_id: string;
    quantity: string;
    refund_total: number;
    refund_tax: number;
};

type SendRefundParams = {
    request_id: number;
    refund_amount: number;
    line_item_qtys: { [ key: string ]: number };
    line_item_totals: { [ key: string ]: number };
    line_item_tax_totals: { [ key: string ]: { [ key: string ]: number } };
};

type useSendRefundReturnType = {
    sendRefundRequest: (
        params: SendRefundParams
    ) => Promise< SuccessResponse >;
    prepareRefundData: ( refundItems: RefundItem[] ) => SendRefundParams;
    isLoading: boolean;
};

/* eslint-disable camelcase */

export const useRefund = (): useSendRefundReturnType => {
    const [ isLoading, setIsLoading ] = useState( false );

    const sendRefundRequest = async (
        params: SendRefundParams
    ): Promise< SuccessResponse > => {
        setIsLoading( true );

        try {
            return await apiFetch( {
                path: `/dokan/v1/rma/warranty-requests/${ params.request_id }/send-refund`,
                method: 'POST',
                data: params,
            } );
        } catch ( err ) {
            throw err;
        } finally {
            setIsLoading( false );
        }
    };

    const prepareRefundData = (
        refundItems: RefundItem[]
    ): SendRefundParams => {
        const line_item_qtys: { [ key: string ]: number } = {};
        const line_item_totals: { [ key: string ]: number } = {};
        const line_item_tax_totals: {
            [ key: string ]: { [ key: string ]: number };
        } = {};
        let total_refund = 0;

        refundItems.forEach( ( item ) => {
            // Convert item_id to string since API expects string keys
            const item_id = item.item_id.toString();

            // Set quantities
            line_item_qtys[ item_id ] = Number.parseInt( item.quantity );

            // Set totals - convert to string as per API requirement
            line_item_totals[ item_id ] = item.refund_total;

            // Set tax totals - using tax index "1" as per example
            line_item_tax_totals[ item_id ] = {
                '1': item.refund_tax || 0,
            };

            total_refund += item.refund_total;
            total_refund += item.refund_tax;
        } );

        return {
            request_id: 0, // This will be set later
            refund_amount: total_refund,
            line_item_qtys,
            line_item_totals,
            line_item_tax_totals,
        };
    };

    return {
        sendRefundRequest,
        prepareRefundData,
        isLoading,
    };
};
