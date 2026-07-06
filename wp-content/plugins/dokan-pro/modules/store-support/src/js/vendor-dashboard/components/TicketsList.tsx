import { useEffect, useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { capitalCase } from '@dokan/utilities';
import {
    DokanBadge,
    DataViews,
    DateTimeHtml,
    ShortContent,
    CustomerFilter,
    // @ts-ignore
    // eslint-disable-next-line import/no-unresolved
} from '@dokan/components';
import { DokanToaster, useToast } from '@getdokan/dokan-ui';

import '../../../../../../src/definitions/window-types';
import {
    SupportTicket,
    SupportTicketStatus,
    TicketsListProps,
    FilterArgs,
    CustomerOption,
} from '../../types/store-support';
import DateRangeFilter from './Navigation/DateRangeFilter';
import { useTickets } from '../hooks/useTickets';
import { useStatusFilters } from '../hooks/useStatusFilters';

const DEFAULT_FILTERS: FilterArgs = {
    page: 1,
    per_page: 10,
    status: 'all' as SupportTicketStatus,
    customer_id: 0,
    start_date: '',
    end_date: '',
    search: '',
};

const DEFAULT_VIEW = {
    perPage: 10,
    page: 1,
    search: '',
    type: 'table',
    selection: null,
    layout: {
        styles: {
            topic: { width: '10%' },
            customer: { width: '25%' },
            title: { width: '25%' },
        },
    },
};

// https://momentjs.com/docs/#/parsing/string-format/
const DATE_FORMAT = 'YYYY-MM-DD';

export type DateRange = {
    startDate: any;
    endDate: any;
};

export default function TicketsList( { navigate }: TicketsListProps ) {
    const toast = useToast();

    // State management
    const [ loadFilters, setLoadFilters ] = useState< boolean >( false );
    const [ filterArgs, setFilterArgs ] =
        useState< FilterArgs >( DEFAULT_FILTERS );
    const [ selectedCustomer, setSelectedCustomer ] =
        useState< CustomerOption | null >( null );
    const [ selectedDateRange, setSelectedDateRange ] =
        useState< DateRange | null >( null );

    // Field definitions
    const fields = [
        {
            id: 'topic',
            label: __( 'Topic', 'dokan' ),
            render: ( { item }: { item: SupportTicket } ) => (
                <div className="topic-id-column">
                    <span
                        role="button"
                        onClick={ ( e ) => {
                            e.preventDefault();
                            navigate( `/support/${ item.id }/` );
                        } }
                        tabIndex={ 0 }
                        onKeyDown={ ( e ) => {
                            if ( e.key === 'Enter' || e.key === ' ' ) {
                                navigate( `/support/${ item.id }/` );
                            }
                        } }
                        className="font-bold hover:underline cursor-pointer text-dokan-link relative z-10"
                    >
                        #{ item.id }
                    </span>
                </div>
            ),
            enableSorting: false,
        },
        {
            id: 'title',
            label: __( 'Title', 'dokan' ),
            render: ( { item }: { item: SupportTicket } ) => (
                <div className="title-column">
                    <span
                        role="button"
                        onClick={ ( e ) => {
                            e.preventDefault();
                            navigate( `/support/${ item.id }/` );
                        } }
                        tabIndex={ 0 }
                        onKeyDown={ ( e ) => {
                            if ( e.key === 'Enter' || e.key === ' ' ) {
                                navigate( `/support/${ item.id }/` );
                            }
                        } }
                        className="text-dokan-link hover:underline cursor-pointer relative z-10"
                        title={ item.title }
                    >
                        <ShortContent content={ item.title } maxLength={ 22 } />
                    </span>

                    { /* Mobile responsive toggle button */ }
                    <button
                        type="button"
                        className="toggle-row md:hidden"
                        onClick={ () => navigate( `/support/${ item.id }/` ) }
                        aria-label={ __( 'View ticket details', 'dokan' ) }
                    />
                </div>
            ),
            enableSorting: false,
            isPrimary: true,
        },
        {
            id: 'customer',
            label: __( 'Customer', 'dokan' ),
            render: ( { item }: { item: SupportTicket } ) => (
                <div
                    className="customer-column"
                    data-title={ __( 'Customer', 'dokan' ) }
                >
                    <div className="flex items-center space-x-3">
                        <img
                            src={
                                item.customer?.avatar ||
                                `https://www.gravatar.com/avatar/?s=50&d=identicon`
                            }
                            alt={ item.customer?.name || '' }
                            className="w-12 h-12 rounded-full"
                        />
                        <strong>
                            { item.customer?.name || __( 'Unknown', 'dokan' ) }
                        </strong>
                    </div>
                </div>
            ),
            enableSorting: false,
        },
        {
            id: 'status',
            label: __( 'Status', 'dokan' ),
            render: ( { item }: { item: SupportTicket } ) => (
                <div
                    className="status-column"
                    data-title={ __( 'Status', 'dokan' ) }
                >
                    <DokanBadge
                        variant={
                            item.status === 'open' ? 'success' : 'danger'
                        }
                        label={ capitalCase( item.status ) }
                    />
                </div>
            ),
            enableSorting: false,
        },
        {
            id: 'created_at',
            label: __( 'Date', 'dokan' ),
            render: ( { item }: { item: SupportTicket } ) => (
                <div
                    className="date-column"
                    data-title={ __( 'Date', 'dokan' ) }
                >
                    <span className="text-sm text-gray-600">
                        <DateTimeHtml.Date date={ item.date_formatted } />
                    </span>
                </div>
            ),
            enableSorting: false,
        },
    ];

    // View state
    const [ view, setView ] = useState( {
        ...DEFAULT_VIEW,
        fields: fields.map( ( field ) => field.id ),
    } );

    // Data fetching hooks
    const {
        tickets,
        isLoading,
        fetchTickets,
        updateStatus,
        totalItems,
        totalPages,
    } = useTickets( filterArgs );

    const { statusCounts, fetchStatusCounts } = useStatusFilters();

    // Actions for DataViews dropdown
    const actions = [
        {
            id: 'support-ticket-status-close-toggle',
            label: () => __( 'Close', 'dokan' ),
            disabled: isLoading,
            isEligible: ( item: SupportTicket ) => item.status === 'open',
            callback: ( [ item ]: [ SupportTicket ] ) =>
                handleStatusToggle( item ),
        },
        {
            id: 'support-ticket-status-reopen-toggle',
            label: () => __( 'Re-open', 'dokan' ),
            disabled: isLoading,
            isEligible: ( item: SupportTicket ) => item.status === 'closed',
            callback: ( [ item ]: [ SupportTicket ] ) =>
                handleStatusToggle( item ),
        },
    ];

    // Event handlers
    const handleLoadComplete = useCallback( () => {
        setLoadFilters( false );
    }, [] );

    const onStatusClick = ( status: SupportTicketStatus ) => {
        setFilterArgs( ( prev ) => ( {
            ...prev,
            status,
            page: 1,
            search: '',
            start_date: '',
            end_date: '',
            customer_id: 0,
        } ) );

        setSelectedCustomer( null );
        setSelectedDateRange( null );

        setView( ( prev ) => ( {
            ...prev,
            page: 1,
            search: '',
        } ) );
    };

    const onItemView = useCallback(
        ( item: SupportTicket ) => {
            navigate( `/support/${ item.id }/` );
        },
        [ navigate ]
    );

    const onViewChange = useCallback( ( newView: typeof view ) => {
        setView( newView );

        // Only update filterArgs — and trigger a refetch — when API-relevant
        setFilterArgs( ( prevState ) => {
            const nextPage    = newView.page    ?? prevState.page;
            const nextPerPage = newView.perPage ?? prevState.per_page;
            const nextSearch  = newView.search  ?? prevState.search;

            if (
                nextPage    === prevState.page &&
                nextPerPage === prevState.per_page &&
                nextSearch  === prevState.search
            ) {
                return prevState; // nothing API-relevant changed — no refetch
            }

            return {
                ...prevState,
                page: nextPage,
                per_page: nextPerPage,
                search: nextSearch,
            };
        } );
    }, [] );

    const handleStatusToggle = useCallback(
        async ( ticket: SupportTicket ) => {
            try {
                const newStatus =
                    ticket.status === 'open' ? 'closed' : 'open';

                await updateStatus( ticket, newStatus );

                // Refetch status counts after a status change
                void fetchStatusCounts();

                toast( {
                    type: 'success',
                    title: __( 'Ticket status updated successfully', 'dokan' ),
                } );
            } catch ( error ) {
                toast( {
                    type: 'error',
                    title: __( 'Failed to update ticket status', 'dokan' ),
                } );
            }
        },
        [ updateStatus, fetchStatusCounts, toast ]
    );

    // Fetch tickets whenever filters change
    useEffect( () => {
        setLoadFilters( true );
        void fetchTickets( filterArgs?.status );
    }, [ filterArgs ] );

    // Fetch status counts once on mount only
    useEffect( () => {
        void fetchStatusCounts();
    }, [] );

    // Tabs configuration — matches the established pattern used across DataViews usages
    const tabs = {
        tabs: statusCounts.map( ( s ) => ( {
            ...s,
            value: s.key,
        } ) ),
        onSelect: onStatusClick,
        initialTabName: 'all',
    };

    // Filter configuration
    const filter = {
        fields: [
            {
                id: 'customer-filter',
                label: __( 'Customer', 'dokan' ),
                field: (
                    <CustomerFilter
                        key="customer-filter"
                        id="dokan-filter-by-customer"
                        value={ selectedCustomer }
                        onChange={ ( selected: CustomerOption ) => {
                            setSelectedCustomer( selected );
                            setFilterArgs( ( prev ) => ( {
                                ...prev,
                                customer_id: selected?.value || 0,
                                page: 1,
                            } ) );
                            setView( ( prev ) => ( { ...prev, page: 1 } ) );
                        } }
                        placeholder={ __( 'Search customer', 'dokan' ) }
                        label={ '' }
                    />
                ),
            },
            {
                id: 'date-range',
                label: __( 'Date Range', 'dokan' ),
                field: (
                    <DateRangeFilter
                        key="date-range-filter"
                        startDate={ selectedDateRange?.startDate ?? null }
                        endDate={ selectedDateRange?.endDate ?? null }
                        onChange={ ( startDate: any, endDate: any ) => {
                            setSelectedDateRange( { startDate, endDate } );
                            setFilterArgs( ( prev ) => ( {
                                ...prev,
                                start_date: startDate
                                    ? startDate.format( DATE_FORMAT )
                                    : '',
                                end_date: endDate
                                    ? endDate.format( DATE_FORMAT )
                                    : '',
                                page: 1,
                            } ) );
                            setView( ( prev ) => ( { ...prev, page: 1 } ) );
                        } }
                        loadFilters={ loadFilters }
                        onLoadComplete={ handleLoadComplete }
                    />
                ),
            },
        ],
        onReset: () => {
            setSelectedCustomer( null );
            setSelectedDateRange( null );
            setFilterArgs( DEFAULT_FILTERS );
            setView( ( prev ) => ( { ...prev, page: 1 } ) );
        },
        onFilterRemove: ( filterId: string ) => {
            if ( filterId === 'customer-filter' ) {
                setSelectedCustomer( null );
                setFilterArgs( ( prev ) => ( {
                    ...prev,
                    customer_id: 0,
                    page: 1,
                } ) );
            }
            if ( filterId === 'date-range' ) {
                setSelectedDateRange( null );
                setFilterArgs( ( prev ) => ( {
                    ...prev,
                    start_date: '',
                    end_date: '',
                    page: 1,
                } ) );
            }
        },
    };

    return (
        <div className="dokan-support-wrapper">
            <div className="dokan-support-topics-list mt-6">
                <DataViews
                    namespace="dokan-support-tickets-data-view"
                    data={ tickets ?? [] }
                    tabs={ tabs }
                    filter={ filter }
                    fields={ fields }
                    search={ true }
                    view={ view }
                    actions={ actions }
                    isLoading={ isLoading }
                    paginationInfo={ {
                        totalItems,
                        totalPages,
                    } }
                    getItemId={ ( item: SupportTicket ) => item.id }
                    onChangeView={ onViewChange }
                    onClickItem={ onItemView }
                    isItemClickable={ () => true }
                />
            </div>
            <DokanToaster />
        </div>
    );
}
