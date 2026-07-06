import { useState, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ArrowLeft, Calendar } from 'lucide-react';
import { DokanToaster, SimpleInput } from '@getdokan/dokan-ui';
import { Fill } from '@wordpress/components';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { DataViews, DateRangePicker, DateTimeHtml } from '@dokan/components';
import type {
    AuctionActivity as AuctionActivityItem,
    AuctionActivityFilterState,
    AuctionListingConfig,
} from './types';
import { useAuctionActivity } from './hooks/useAuctionActivity';
import { useDateRangeFilter } from './hooks/useDateRangeFilter';
import { displayDateRange } from './utils';

// ── Component ────────────────────────────────────────────────────────────────

function AuctionActivity() {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const config: AuctionListingConfig =
        ( window as any ).dokanFrontend?.auction_listing ?? {};
    const canSeeCustomerInfo = config.can_see_customer_info ?? false;

    const defaultFields = canSeeCustomerInfo
        ? [ 'auction', 'user_name', 'user_email', 'bid', 'date', 'proxy' ]
        : [ 'auction', 'user_name', 'bid', 'date', 'proxy' ];

    const [ filterArgs, setFilterArgs ] =
        useState< AuctionActivityFilterState >( {
            page: 1,
            per_page: 10,
            search: '',
            start_date: '',
            end_date: '',
        } );

    const [ view, setView ] = useState( {
        type: 'table',
        perPage: 10,
        page: 1,
        search: '',
        fields: defaultFields,
        layout: {
            styles: {
                auction: '30%',
                user_name: '20%',
                ...( canSeeCustomerInfo ? { user_email: '20%' } : {} ),
                bid: '10%',
                date: '20%',
                proxy: '10%',
            },
        },
    } );

    const { data, isLoading, totalItems, totalPages } =
        useAuctionActivity( filterArgs );

    // ── Date range picker state ───────────────────────────────────────────────

    const {
        after,
        afterText,
        before,
        beforeText,
        focusedInput,
        applyDateFilter,
        clearDateFilter,
        onPickerUpdate,
    } = useDateRangeFilter( setFilterArgs );

    // ── View change ───────────────────────────────────────────────────────────

    const onViewChange = ( newView: typeof view ) => {
        setView( newView );
        setFilterArgs( ( prev ) => ( {
            ...prev,
            page: newView.page,
            per_page: newView.perPage,
            search: newView.search ?? '',
        } ) );
    };

    // ── Fields (columns) ─────────────────────────────────────────────────────

    const fields = useMemo(
        () => [
            {
                id: 'auction',
                label: __( 'Auction', 'dokan' ),
                enableSorting: false,
                isPrimary: true,
                render: ( { item }: { item: AuctionActivityItem } ) => {
                    const editUrl = config.auction_url
                        ? `${ config.auction_url }?product_id=${ item.post_id }&action=edit`
                        : '#';
                    return (
                        <a
                            href={ editUrl }
                            className="text-dokan-link no-underline focus:outline-none!"
                        >
                            { item.post_title }
                        </a>
                    );
                },
            },
            {
                id: 'user_name',
                label: __( 'User Name', 'dokan' ),
                enableSorting: false,
                render: ( { item }: { item: AuctionActivityItem } ) => (
                    <span>{ item.user_nicename }</span>
                ),
            },
            ...( canSeeCustomerInfo
                ? [
                      {
                          id: 'user_email',
                          label: __( 'User Email', 'dokan' ),
                          enableSorting: false,
                          render: ( {
                              item,
                          }: {
                              item: AuctionActivityItem;
                          } ) => <span>{ item.user_email }</span>,
                      },
                  ]
                : [] ),
            {
                id: 'bid',
                label: __( 'Bid', 'dokan' ),
                enableSorting: false,
                render: ( { item }: { item: AuctionActivityItem } ) => (
                    <span>{ item.bid }</span>
                ),
            },
            {
                id: 'date',
                label: __( 'Date', 'dokan' ),
                enableSorting: false,
                render: ( { item }: { item: AuctionActivityItem } ) => (
                    <DateTimeHtml date={ item.date } />
                ),
            },
            {
                id: 'proxy',
                label: __( 'Proxy', 'dokan' ),
                enableSorting: false,
                render: ( { item }: { item: AuctionActivityItem } ) => (
                    <span>
                        { item.proxy
                            ? __( 'Yes', 'dokan' )
                            : __( 'No', 'dokan' ) }
                    </span>
                ),
            },
            // eslint-disable-next-line react-hooks/exhaustive-deps
        ],
        [ canSeeCustomerInfo, config.auction_url ]
    );

    // ── Filter fields ─────────────────────────────────────────────────────────

    const filter = {
        fields: [
            {
                id: 'date-range',
                label: __( 'Date Range', 'dokan' ),
                field: (
                    <DateRangePicker
                        key="activity-date-range"
                        after={ after }
                        afterText={ afterText }
                        before={ before }
                        beforeText={ beforeText }
                        onUpdate={ onPickerUpdate }
                        shortDateFormat="MM/DD/YYYY"
                        focusedInput={ focusedInput }
                        isInvalidDate={ () => false }
                        wrapperClassName="w-full"
                        pickerToggleClassName="block"
                        wpPopoverClassName="dokan-layout"
                        onClear={ clearDateFilter }
                        onOk={ applyDateFilter }
                    >
                        <SimpleInput
                            addOnLeft={ <Calendar size="16" /> }
                            className="border rounded px-3 py-1.5 w-full bg-white"
                            onChange={ () => {} }
                            input={ {
                                type: 'text',
                                value:
                                    ! after || ! before
                                        ? ''
                                        : displayDateRange( after, before ),
                                placeholder: __( 'Date', 'dokan' ),
                                readOnly: true,
                            } }
                        />
                    </DateRangePicker>
                ),
            },
        ],
        onReset: clearDateFilter,
        onFilterRemove: ( filterId: string ) => {
            if ( filterId === 'date-range' ) {
                clearDateFilter();
            }
        },
    };

    // ── Render ────────────────────────────────────────────────────────────────

    return (
        <>
            <Fill name="dokan-header-actions">
                <a
                    href="#/auction"
                    className="dokan-btn dokan-btn-secondary inline-flex items-center gap-1 no-underline"
                >
                    <ArrowLeft className="w-4 h-4" />
                    { __( 'Auctions', 'dokan' ) }
                </a>
            </Fill>

            <DataViews
                namespace="dokan-auction-activity-data-view"
                data={ data }
                fields={ fields }
                view={ view }
                onChangeView={ onViewChange }
                getItemId={ ( item: AuctionActivityItem ) =>
                    `${ item.post_id }-${ item.user_nicename }-${ item.date }-${ item.bid }`
                }
                isLoading={ isLoading }
                paginationInfo={ { totalItems, totalPages } }
                filter={ filter }
                search={ true }
            />
            <DokanToaster />
        </>
    );
}

export default AuctionActivity;
