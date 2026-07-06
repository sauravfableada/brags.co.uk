import { twMerge } from 'tailwind-merge';

import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

import { Card, SimpleSelect, useToast } from '@getdokan/dokan-ui';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { DateTimeHtml, DokanAlert, DokanButton } from '@dokan/components';

import '../../../../../../../src/definitions/window-types';
import { useWarrantyStatuses } from '../../hooks/useWarrantyStatuses';
import { WarrantyRequest } from '../../../types/warranty-request';
import RefundModal from '../Popups/RefundModal';
import CouponModal from '../Popups/CouponModal';
import StatusManageSectionSkeleton from './StatusManageSectionSkeleton';

type StatusManageSectionProps = {
    request: WarrantyRequest;
    onRequestUpdate: ( _response: WarrantyRequest ) => void;
    syncRequest: () => Promise< void >;
};

// prettier-ignore
export default function StatusManageSection( { request, onRequestUpdate, syncRequest }: StatusManageSectionProps ) {
    const [ showRefundModal, setShowRefundModal ] = useState( false );
    const [ showCouponModal, setShowCouponModal ] = useState( false );
    const [ currentStatus, setCurrentStatus ] = useState( request.status );
    const [ isUpdating, setIsUpdating ] = useState( false );

    const toast = useToast();
    const {
        statuses,
        isLoading: isLoadingStatuses,
        fetchStatuses,
    } = useWarrantyStatuses();


    const { is_coupon_enable: isCouponEnable = false, is_refund_enable: isRefundEnable = false } = window.DokanRMAPanel;

    useEffect( () => {
        void fetchStatuses();
    }, [] );

    // Update currentStatus when request status changes
    useEffect( () => {
        setCurrentStatus( request.status );
    }, [ request.status ] );

    const handleStatusUpdate = async () => {
        if ( currentStatus === request.status ) {
            return;
        }

        setIsUpdating( true );
        try {
            const response = await apiFetch< WarrantyRequest >( {
                path: `/dokan/v1/rma/warranty-requests/${ request.id }`,
                method: 'PUT',
                data: { status: currentStatus },
            } );

            onRequestUpdate( response );
            toast( {
                title: __( 'Status updated successfully', 'dokan' ),
                type: 'success',
            } );
        } catch ( err ) {
            toast( {
                title: __( 'Failed to update status', 'dokan' ),
                type: 'error',
            } );
            setCurrentStatus( request.status );
        } finally {
            setIsUpdating( false );
        }
    };

    const handleRefundProcessed = () => {
        setShowRefundModal( false );
        void handleStatusUpdate();
        void syncRequest();
    };

    const handleCouponSent = () => {
        setShowCouponModal( false );
        void handleStatusUpdate();
        void syncRequest();
    };

    if ( isLoadingStatuses || ! statuses ) {
        return <StatusManageSectionSkeleton />;
    }

    const alignment: string = (
        request.status === 'processing' && [ 'refund', 'coupon' ].includes( request.type ) && ! request.is_refund_pending
    ) ? 'justify-between' : 'justify-end';

    const isOrderDeleted = request.is_order_deleted;
    const availableStatuses: any = Object.entries(statuses).reduce((acc, [key, value]) => {
        if ( ( isOrderDeleted && key === 'info_removed' ) || ( ! isOrderDeleted && key !== 'info_removed' ) ) {
            acc[ key ] = value;
        }

        return acc;
    }, {});

    return (
        <>
            <Card>
                <Card.Header className="px-4 py-2 flex justify-between items-center">
                    <Card.Title className="p-0 m-0 mb-0">
                        { __( 'Status', 'dokan' ) }
                    </Card.Title>
                </Card.Header>
                <Card.Body className="px-4 py-4">
                    <div className="flex justify-start mb-4 text-sm">
                        <div className="text-gray-900 font-medium mr-2">
                            { __( 'Last Updated:', 'dokan' ) }
                        </div>
                        <DateTimeHtml.Date date={ request.created_at } />
                    </div>
                    <div className="mb-4">
                        <SimpleSelect
                            className="w-full border border-gray-300 rounded p-2 mb-4"
                            label={ __( 'Change Status', 'dokan' ) }
                            value={ currentStatus }
                            disabled={ isOrderDeleted || request.is_refund_pending || ( 'completed' === request.status ) }
                            onChange={ ( e ) =>
                                setCurrentStatus( e.target.value )
                            }
                            options={ Object.entries( availableStatuses ).map(
                                ( [ value, label ] ) => ( {
                                    value,
                                    label,
                                } )
                            ) as any }
                        />
                    </div>

                    { ! isOrderDeleted && (
                        <div className={ twMerge( 'w-full flex gap-4', alignment ) }>
                            { isCouponEnable && request.type === 'coupon' && request.status === 'processing' && (
                                <DokanButton
                                    variant="secondary"
                                    loading={ isUpdating }
                                    onClick={ () => setShowCouponModal( true ) }
                                >
                                    { __( 'Send Coupon', 'dokan' ) }
                                </DokanButton>
                            ) }
                            {isRefundEnable && request.type === 'refund' && request.status === 'processing' && ! request.is_refund_pending && (
                                <DokanButton
                                    variant="secondary"
                                    loading={ isUpdating }
                                    onClick={ () => setShowRefundModal( true ) }
                                >
                                    { __( 'Send Refund', 'dokan' ) }
                                </DokanButton>
                            ) }

                            <DokanButton
                                onClick={ handleStatusUpdate }
                                loading={ isUpdating }
                                disabled={ currentStatus === request.status }
                            >
                                { __( 'Update', 'dokan' ) }
                            </DokanButton>
                        </div>
                    )}

                    { request.is_refund_pending && (
                        <DokanAlert
                            variant="info"
                            className="mt-4"
                            label={ __( 'Already send refund request. Wait for admin approval', 'dokan' ) }
                        />
                    ) }
                </Card.Body>
            </Card>

            { showRefundModal && (
                <RefundModal
                    request={ request }
                    closeModal={ () => setShowRefundModal( false ) }
                    onRefundProcessed={ handleRefundProcessed }
                    isOpen={ showRefundModal }
                />
            ) }

            { showCouponModal && (
                <CouponModal
                    request={ request }
                    closeModal={ () => setShowCouponModal( false ) }
                    onCouponSent={ handleCouponSent }
                    isOpen={ showCouponModal }
                />
            ) }
        </>
    );
}
