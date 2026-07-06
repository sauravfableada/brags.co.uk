import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { useToast } from '@getdokan/dokan-ui';
import {
    DokanButton,
    DokanModal,
    // @ts-ignore
    // eslint-disable-next-line import/no-unresolved
} from '@dokan/components';

import {
    WarrantyProduct,
    WarrantyRequest,
} from '../../../types/warranty-request';
import { useCoupon } from '../../hooks/useCoupon';
import WarrantyItemsTable from './WarrantyItemsTable';

interface CouponItem {
    item_id: string;
    quantity: string;
    refund_total: number;
    refund_tax: number;
}

interface SendCouponModalProps {
    request: WarrantyRequest;
    isOpen: boolean;
    closeModal: () => void;
    onCouponSent: () => void;
}

export function CouponModal( {
    request,
    isOpen,
    closeModal,
    onCouponSent,
}: SendCouponModalProps ) {
    const { sendCouponRequest, prepareCouponData, isLoading } = useCoupon();
    const toast = useToast();

    const [ couponItems, setCouponItems ] = useState< CouponItem[] >( () =>
        request.items.map(
            ( item: WarrantyProduct ): CouponItem => ( {
                item_id: item.item_id,
                quantity: item.quantity,
                refund_total: 0,
                refund_tax: 0,
            } )
        )
    );
    const [ totalRefund, setTotalRefund ] = useState( 0 );
    const [ totalAmount, setTotalAmount ] = useState<
        Record< string, string >
    >( {} );
    const [ totalTaxAmount, setTotalTaxAmount ] = useState<
        Record< string, string >
    >( {} );

    const updateAmounts = (
        itemKey: string,
        itemId: string,
        value: number
    ) => {
        const updatedItems = couponItems.map( ( item ) =>
            item.item_id === itemId ? { ...item, [ itemKey ]: value } : item
        );
        setCouponItems( updatedItems );

        const total = updatedItems.reduce(
            ( sum, refundItem ) =>
                sum + refundItem.refund_tax + refundItem.refund_total,
            0
        );
        setTotalRefund( total );
    };

    const handleSubmit = async ( e: React.FormEvent ) => {
        e.preventDefault();

        try {
            const couponData = prepareCouponData( couponItems );
            couponData.request_id = request.id;

            const response = await sendCouponRequest( couponData );

            toast( {
                type: 'success',
                title:
                    response.message ??
                    __( 'Store credit coupon sent successfully', 'dokan' ),
            } );

            onCouponSent();
            closeModal();
        } catch ( err ) {
            toast( {
                type: 'error',
                title:
                    err?.message ??
                    __( 'Failed to send store credit coupon', 'dokan' ),
            } );
        }
    };

    const handleTaxAmountChange = ( itemId: string, value: string ) => {
        setTotalTaxAmount( ( prevState ) => ( {
            ...prevState,
            [ itemId ]: value,
        } ) );
    };

    const handleTotalAmountChange = ( itemId: string, value: string ) => {
        setTotalAmount( ( prevState ) => ( {
            ...prevState,
            [ itemId ]: value,
        } ) );
    };

    const taxEnabled = request.items.some( ( item ) => item.tax > 0 );

    return (
        <DokanModal
            namespace="rma-coupon-modal"
            isOpen={ isOpen }
            onClose={ closeModal }
            dialogTitle={ __( 'Coupon', 'dokan' ) }
            dialogContent={
                <WarrantyItemsTable
                    actionType="coupon"
                    items={ request.items }
                    taxEnabled={ taxEnabled }
                    totalAmount={ totalAmount }
                    totalTaxAmount={ totalTaxAmount }
                    updateTaxAmount={ handleTaxAmountChange }
                    updateTotalAmount={ handleTotalAmountChange }
                    updateAmounts={ updateAmounts }
                    totalRefund={ totalRefund }
                    isLoading={ isLoading }
                />
            }
            dialogFooter={
                <div className={ `flex items-center justify-end gap-3` }>
                    <DokanButton
                        variant="secondary"
                        onClick={ closeModal }
                        disabled={ isLoading }
                        label={ __( 'Cancel', 'dokan' ) }
                    />
                    <DokanButton
                        onClick={ handleSubmit }
                        disabled={ isLoading }
                        loading={ isLoading }
                        label={ __( 'Send Coupon', 'dokan' ) }
                    />
                </div>
            }
            className="w-full !max-w-3xl"
        />
    );
}

export default CouponModal;
