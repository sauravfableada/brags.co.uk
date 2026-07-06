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
import { useRefund } from '../../hooks/useRefund';
import WarrantyItemsTable from './WarrantyItemsTable';

interface RefundItem {
    item_id: string;
    quantity: string;
    refund_total: number;
    refund_tax: number;
}

interface AddRefundModalProps {
    request: WarrantyRequest;
    isOpen: boolean;
    closeModal: () => void;
    onRefundProcessed: () => void;
}

export function RefundModal( {
    request,
    isOpen,
    closeModal,
    onRefundProcessed,
}: AddRefundModalProps ) {
    const { sendRefundRequest, prepareRefundData, isLoading } = useRefund();
    const toast = useToast();

    const [ refundItems, setRefundItems ] = useState< RefundItem[] >(
        request.items.map(
            ( item: WarrantyProduct ): RefundItem => ( {
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
        const updatedItems = refundItems.map( ( item ) =>
            item.item_id === itemId ? { ...item, [ itemKey ]: value } : item
        );
        setRefundItems( updatedItems );

        const total = updatedItems.reduce(
            ( sum, refundItem ) =>
                sum + refundItem.refund_tax + refundItem.refund_total,
            0
        );
        setTotalRefund( total );
    };

    const handleSubmit = async ( e: React.FormEvent< HTMLButtonElement > ) => {
        e.preventDefault();

        try {
            const refundData = prepareRefundData( refundItems );
            refundData.request_id = request.id;

            const response = await sendRefundRequest( refundData );
            toast( {
                type: 'success',
                title:
                    response.message ??
                    __( 'Refund sent successfully', 'dokan' ),
            } );

            onRefundProcessed();
            closeModal();
        } catch ( err ) {
            toast( {
                type: 'error',
                title:
                    err?.message ??
                    __( 'Failed to send refund request', 'dokan' ),
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
    const submissionDisabled = ! totalRefund || totalRefund <= 0 || isLoading;

    return (
        <DokanModal
            namespace="rma-refund-modal"
            isOpen={ isOpen }
            onClose={ closeModal }
            dialogTitle={ __( 'Refund', 'dokan' ) }
            dialogContent={
                <WarrantyItemsTable
                    actionType="refund"
                    items={ request.items }
                    taxEnabled={ taxEnabled }
                    totalAmount={ totalAmount }
                    totalTaxAmount={ totalTaxAmount }
                    updateAmounts={ updateAmounts }
                    updateTaxAmount={ handleTaxAmountChange }
                    updateTotalAmount={ handleTotalAmountChange }
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
                        disabled={ submissionDisabled }
                        loading={ isLoading }
                        label={ __( 'Send Refund', 'dokan' ) }
                    />
                </div>
            }
            className="w-full !max-w-3xl"
        />
    );
}

export default RefundModal;
