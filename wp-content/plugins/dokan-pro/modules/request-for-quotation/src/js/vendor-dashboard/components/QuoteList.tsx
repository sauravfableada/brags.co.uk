import { useCallback, useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

import {
    DataViews,
    // @ts-ignore
    // eslint-disable-next-line import/no-unresolved
} from '@dokan/components';
import { DokanToaster, useToast } from '@getdokan/dokan-ui';
import { Quote, QuoteStatus, QuoteStatusCount } from '../types/quote';
import { useQuotes } from '../hooks/useQuotes';
import { getDefaultStatusCounts } from '../hooks/useStatusFilters';
import StatusPill from './StatusPill';

const DEFAULT_VIEW = {
    perPage: 10,
    page: 1,
    search: '',
    type: 'table',
    status: 'all' as QuoteStatus,
};

const DEFAULT_FILTERS = {
    page: 1,
    per_page: 10,
    status: 'all' as QuoteStatus,
    search: '',
};

// Navigate to the PHP quote detail template for a given quote ID.
const navigateToQuote = ( quoteId: number ) => {
    // @ts-ignore
    const base: string = window?.dokan?.rfq_vendor_quote_url ?? '';
    window.location.href = base + quoteId + '/';
};

export default function QuoteList() {
    const toast = useToast();

    const [ filterArgs, setFilterArgs ] = useState( DEFAULT_FILTERS );
    const [ statusCounts, setStatusCounts ] = useState< QuoteStatusCount[] >(
        getDefaultStatusCounts()
    );
    const [ selection, setSelection ] = useState< string[] >( [] );

    const [ view, setView ] = useState( {
        ...DEFAULT_VIEW,
        fields: [ 'quote_number', 'status', 'customer_name', 'created_at' ],
        layout: {
            styles: {
                quote_number: {
                    width: '25%',
                },
                status: {
                    width: '25%',
                },
                customer_name: {
                    width: '25%',
                },
                created_at: {
                    width: '25%',
                },
            },
        },
    } );

    const {
        quotes,
        isLoading,
        fetchQuotes,
        totalItems,
        totalPages,
        statusCounts: fetchedCounts,
    } = useQuotes( filterArgs );

    // Keep tabs counts in sync with the latest API response.
    useEffect( () => {
        if ( fetchedCounts.length > 0 ) {
            setStatusCounts( fetchedCounts );
        }
    }, [ fetchedCounts ] );

    // Helper: change a quote's status via the vendor endpoint.
    const changeStatus = async ( quoteId: number, status: string ) => {
        await apiFetch( {
            path: `/dokan/v1/vendor/request-for-quote/${ quoteId }`,
            method: 'POST',
            data: { status },
        } );
    };

    // Helper: bulk status change via the batch endpoint.
    const batchChange = async ( ids: number[], action: string ) => {
        await apiFetch( {
            path: '/dokan/v1/vendor/request-for-quote/batch',
            method: 'POST',
            data: { action, items: ids },
        } );
    };

    // ── Field definitions (columns matching PHP table) ───────────────────────

    const fields = [
        {
            id: 'quote_number',
            label: __( 'Quote #', 'dokan' ),
            enableSorting: false,
            isPrimary: true,
            render: ( { item }: { item: Quote } ) => (
                <div>
                    <span
                        role="button"
                        tabIndex={ 0 }
                        className="font-semibold text-dokan-link hover:underline cursor-pointer"
                        onClick={ () => navigateToQuote( item.id ) }
                        onKeyDown={ ( e ) => {
                            if ( e.key === 'Enter' || e.key === ' ' ) {
                                navigateToQuote( item.id );
                            }
                        } }
                    >
                        { __( 'Quote', 'dokan' ) } { item.id }
                    </span>
                    { item.status === 'expired' && item.expiry_display && (
                        <span className="ml-1 text-xs text-gray-500">
                            { sprintf(
                                /* translators: %s: expiry date */
                                __( '(Expired: %s)', 'dokan' ),
                                item.expiry_display
                            ) }
                        </span>
                    ) }
                </div>
            ),
        },
        {
            id: 'status',
            label: __( 'Status', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: Quote } ) => (
                <StatusPill value={ item.status } id={ item.id } />
            ),
        },
        {
            id: 'customer_name',
            label: __( 'Customer', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: Quote } ) => (
                <span>{ item.customer_name || __( '—', 'dokan' ) }</span>
            ),
        },
        {
            id: 'created_at',
            label: __( 'Date', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: Quote } ) => (
                <time title={ item.created_at }>{ item.created_at }</time>
            ),
        },
    ];

    // ── Status tabs ──────────────────────────────────────────────────────────

    const currentStatus = filterArgs.status;

    const onStatusClick = useCallback( ( status: string ) => {
        const quoteStatus = status as QuoteStatus;
        setFilterArgs( ( prev ) => ( {
            ...prev,
            status: quoteStatus,
            page: 1,
            search: '',
        } ) );
        setView( ( prev ) => ( { ...prev, page: 1, status: quoteStatus } ) );
        setSelection( [] );
    }, [] );

    const tabs = {
        items: statusCounts.map( ( s ) => ( { ...s, value: s.key } ) ),
        onSelect: onStatusClick,
    };

    // ── Actions ──────────────────────────────────────────────────────────────

    const trashQuotes = async ( items: Quote[] ) => {
        try {
            if ( items.length === 1 ) {
                await changeStatus( items[ 0 ].id, 'trash' );
            } else {
                await batchChange( items.map( ( i ) => i.id ), 'trash' );
            }
            toast( { type: 'success', title: __( 'Quote(s) moved to trash.', 'dokan' ) } );
            setSelection( [] );
            void fetchQuotes( currentStatus );
        } catch {
            toast( { type: 'error', title: __( 'Failed to move quote(s) to trash.', 'dokan' ) } );
        }
    };

    const restoreQuotes = async ( items: Quote[] ) => {
        try {
            await batchChange( items.map( ( i ) => i.id ), 'pending' );
            toast( { type: 'success', title: __( 'Quote(s) restored.', 'dokan' ) } );
            setSelection( [] );
            void fetchQuotes( currentStatus );
        } catch {
            toast( { type: 'error', title: __( 'Failed to restore quote(s).', 'dokan' ) } );
        }
    };

    const actions = [
        // View — available for all statuses.
        {
            id: 'quote-view',
            label: () => __( 'View', 'dokan' ),
            callback: ( items: Quote[] ) => navigateToQuote( items[ 0 ].id ),
        },
        // Move to Trash — DataViews shows built-in confirm dialog before callback.
        {
            id: 'quote-trash',
            label: () => __( 'Move to Trash', 'dokan' ),
            isDestructive: true,
            supportsBulk: true,
            isEligible: ( item: Quote ) => item.status !== 'trash',
            confirmTitle: __( 'Move to Trash', 'dokan' ),
            confirmMessage: __( 'Selected quote(s) will be moved to trash.', 'dokan' ),
            callback: ( items: Quote[] ) => void trashQuotes( items ),
        },
        // Restore — available only for trashed quotes.
        {
            id: 'quote-restore',
            label: () => __( 'Pending', 'dokan' ),
            supportsBulk: true,
            isEligible: ( item: Quote ) => item.status === 'trash',
            callback: ( items: Quote[] ) => void restoreQuotes( items ),
        },
    ];

    // ── View change ──────────────────────────────────────────────────────────

    const onViewChange = useCallback( ( newView: typeof view ) => {
        setView( newView );
        setFilterArgs( ( prev ) => {
            const newSearch = newView.search ?? '';
            if (
                prev.page === newView.page &&
                prev.per_page === newView.perPage &&
                prev.search === newSearch
            ) {
                return prev;
            }

            return {
                ...prev,
                page: newView.page,
                per_page: newView.perPage,
                search: newSearch,
            };
        } );
    }, [] );

    // ── Effects ──────────────────────────────────────────────────────────────

    useEffect( () => {
        void fetchQuotes( filterArgs.status );
    }, [ filterArgs ] );

    // ── Render ───────────────────────────────────────────────────────────────

    return (
        <div className="dokan-rfq-vendor-wrapper">
            <DataViews
                namespace="dokan-rfq-quotes-data-view"
                data={ quotes ?? [] }
                fields={ fields }
                view={ view }
                onChangeView={ onViewChange }
                getItemId={ ( item: Quote ) => item.id }
                isLoading={ isLoading }
                paginationInfo={ { totalItems, totalPages } }
                tabs={ tabs }
                search={ true }
                actions={ actions }
                selection={ selection }
                onChangeSelection={ setSelection }
                onClickItem={ ( item: Quote ) => navigateToQuote( item.id ) }
                isItemClickable={ () => true }
            />
            <DokanToaster />
        </div>
    );
}
