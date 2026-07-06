import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

type SuccessResponse = {
    message: string;
};

type CouponItem = {
    item_id: string;
    quantity: string;
    refund_total: number;
    refund_tax: number;
};

type SendCouponParams = {
    request_id: number;
    refund_amount: number;
    line_item_qtys: { [ key: string ]: number };
    line_item_totals: { [ key: string ]: number };
    line_item_tax_totals: { [ key: string ]: { [ key: string ]: number } };
};

type useSendCouponReturnType = {
    sendCouponRequest: (
        params: SendCouponParams
    ) => Promise< SuccessResponse >;
    prepareCouponData: ( couponItems: CouponItem[] ) => SendCouponParams;
    isLoading: boolean;
};

/* eslint-disable camelcase */

export const useCoupon = (): useSendCouponReturnType => {
    const [ isLoading, setIsLoading ] = useState( false );

    const sendCouponRequest = async (
        params: SendCouponParams
    ): Promise< SuccessResponse > => {
        setIsLoading( true );

        try {
            return await apiFetch( {
                path: `/dokan/v1/rma/warranty-requests/${ params.request_id }/send-coupon`,
                method: 'POST',
                data: params,
            } );
        } catch ( err ) {
            throw err;
        } finally {
            setIsLoading( false );
        }
    };

    const prepareCouponData = (
        couponItems: CouponItem[]
    ): SendCouponParams => {
        const line_item_qtys: { [ key: string ]: number } = {};
        const line_item_totals: { [ key: string ]: number } = {};
        const line_item_tax_totals: {
            [ key: string ]: { [ key: string ]: number };
        } = {};
        let total_refund = 0;

        couponItems.forEach( ( item ) => {
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
            request_id: 0,
            refund_amount: total_refund,
            line_item_qtys,
            line_item_totals,
            line_item_tax_totals,
        };
    };

    return {
        sendCouponRequest,
        prepareCouponData,
        isLoading,
    };
};
