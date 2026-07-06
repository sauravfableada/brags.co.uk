import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { DokanToaster } from '@getdokan/dokan-ui';

// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { DokanButton, NotFound, Forbidden } from '@dokan/components';

import { useTicket } from '../../hooks/useTicket';
import TicketHeader from './TicketHeader';
import MainTopicSection from './MainTopicSection';
import ReplyList from './Reply/ReplyList';
import TicketHeaderSkeleton from './TicketHeaderSkeleton';
import MainTopicSkeleton from './MainTopicSkeleton';
import ReplySkeletons from './Reply/ReplySkeletons';

interface TicketDetailsProps {
    params: { ticketId: string };
    navigate: any;
}

export default function TicketDetails( {
    navigate,
    params,
}: TicketDetailsProps ) {
    const {
        ticket,
        setTicket,
        isLoading,
        fetchTicket,
        isNotFound,
        isNotPermitted,
    } = useTicket( params.ticketId );

    // Effects
    useEffect( () => {
        void fetchTicket();
    }, [] );

    if ( isNotFound && ! isLoading ) {
        return (
            <NotFound
                title={ __( 'Support Ticket Not Available', 'dokan' ) }
                message={ __(
                    "We couldn't find the support ticket you were looking for.",
                    'dokan'
                ) }
                navigateButton={
                    <DokanButton onClick={ () => navigate( '/support' ) }>
                        { __( 'Return to Tickets', 'dokan' ) }
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
                    "You don't have permission to access this support ticket.",
                    'dokan'
                ) }
                navigateButton={
                    <DokanButton onClick={ () => navigate( '/support' ) }>
                        { __( 'Return to Tickets', 'dokan' ) }
                    </DokanButton>
                }
            />
        );
    }

    return (
        <div className="dokan-support-single-wrapper space-y-6">
            { /* Ticket Header Section */ }
            <div className="ticket-header-section">
                { isLoading || ! ticket ? (
                    <TicketHeaderSkeleton />
                ) : (
                    <TicketHeader ticket={ ticket } />
                ) }
            </div>

            { /* Main Topic Section - matches PHP main topic display */ }
            <div className="main-topic-section">
                { isLoading || ! ticket ? (
                    <MainTopicSkeleton />
                ) : (
                    <MainTopicSection ticket={ ticket } />
                ) }
            </div>

            { /* Replies Section */ }
            <div className="replies-section">
                { isLoading || ! ticket ? (
                    <ReplySkeletons />
                ) : (
                    <ReplyList ticket={ ticket } setTicket={ setTicket } />
                ) }
            </div>

            <DokanToaster />
        </div>
    );
}
