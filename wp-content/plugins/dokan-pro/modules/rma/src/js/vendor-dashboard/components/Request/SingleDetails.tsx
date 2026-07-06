import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { DokanToaster } from '@getdokan/dokan-ui';

// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { DokanButton, NotFound, Forbidden } from '@dokan/components';

import { useWarrantyRequest } from '../../hooks/useWarrantyRequest';
import OrderSection from './OrderSection';
import ConversationsList from './Conversation/ConversationsList';
import StatusManageSection from './StatusManageSection';
import OrderSectionSkeleton from './OrderSectionSkeleton';
import StatusManageSectionSkeleton from './StatusManageSectionSkeleton';
import ConversationsListSkeleton from './Conversation/ConversationSkeletons';
import { WarrantyRequest } from '../../../types/warranty-request';

interface SingleDetailsProps {
    params: { requestId: string };
    navigate: any;
}

export default function SingleDetails( {
    navigate,
    params,
}: SingleDetailsProps ) {
    const {
        request,
        setRequest,
        isLoading,
        fetchRequest,
        isNotFound,
        isNotPermitted,
    } = useWarrantyRequest( params.requestId );

    useEffect( () => {
        if ( params.requestId ) {
            void fetchRequest();
        }
    }, [ params.requestId ] );

    if ( isNotFound && ! isLoading ) {
        return (
            <NotFound
                title={ __( 'Return Request Not Available', 'dokan' ) }
                message={ __(
                    "We couldn't find the return request you were looking for.",
                    'dokan'
                ) }
                navigateButton={
                    <DokanButton
                        onClick={ () => navigate( '/return-request' ) }
                    >
                        { __( 'Return to List', 'dokan' ) }
                    </DokanButton>
                }
            />
        );
    }

    if ( isNotPermitted && ! isLoading ) {
        return (
            <Forbidden
                title={ __( 'Access Denied', 'dokan' ) }
                message={ __(
                    "You don't have permission to access this area.",
                    'dokan'
                ) }
                navigateButton={
                    <DokanButton
                        onClick={ () => navigate( '/return-request' ) }
                    >
                        { __( 'Return to List', 'dokan' ) }
                    </DokanButton>
                }
            />
        );
    }

    return (
        <div className="dokan-rma-single-request transition-all duration-200 ease-in-out">
            <div className="grid grid-cols-3 gap-4">
                <div className="col-span-2">
                    { isLoading || ! request ? (
                        <OrderSectionSkeleton />
                    ) : (
                        <OrderSection request={ request } />
                    ) }
                </div>
                <div className="col-span-1">
                    { isLoading || ! request ? (
                        <StatusManageSectionSkeleton />
                    ) : (
                        <StatusManageSection
                            request={ request }
                            syncRequest={ fetchRequest }
                            onRequestUpdate={ ( update: WarrantyRequest ) => {
                                setRequest( update );
                            } }
                        />
                    ) }
                </div>
            </div>
            <div className="mt-6">
                { isLoading || ! request ? (
                    <ConversationsListSkeleton />
                ) : (
                    <ConversationsList request={ request } />
                ) }
            </div>
            <DokanToaster />
        </div>
    );
}
