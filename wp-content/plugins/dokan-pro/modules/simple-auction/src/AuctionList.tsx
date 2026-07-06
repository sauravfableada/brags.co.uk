import { useState, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useToast, SimpleInput, DokanToaster } from '@getdokan/dokan-ui';
import { applyFilters } from '@wordpress/hooks';
import { Fill } from '@wordpress/components';
import { Gavel, Plus, Calendar } from 'lucide-react';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import {
    DataViews,
    DokanBadge,
    DateRangePicker,
    DokanTooltip,
} from '@dokan/components';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { createDuplicateAction } from '@dokan-pro/features/products/actions/duplicate';
import type {
    AuctionProduct,
    AuctionStatus,
    AuctionFilterState,
    AuctionListingConfig,
} from './types';
import { useAuctionProducts } from './hooks/useAuctionProducts';
import { useDateRangeFilter } from './hooks/useDateRangeFilter';
import { displayDateRange } from './utils';

// ── Helpers ───────────────────────────────────────────────────────────────────

const getStatusBadgeVariant = ( status: string ) => {
    switch ( status ) {
        case 'publish':
            return 'success';
        case 'draft':
            return 'secondary';
        case 'pending':
            return 'warning';
        case 'reject':
            return 'danger';
        default:
            return 'info';
    }
};

const getStatusLabel = ( status: string ) => {
    switch ( status ) {
        case 'publish':
            return __( 'Published', 'dokan' );
        case 'draft':
            return __( 'Draft', 'dokan' );
        case 'pending':
            return __( 'Pending Review', 'dokan' );
        case 'reject':
            return __( 'Rejected', 'dokan' );
        default:
            return status;
    }
};

/**
 * Derive CSS class names for the auction type gavel icon,
 * mirroring the original PHP template logic.
 * @param item
 */
const getAuctionIconClass = ( item: AuctionProduct ): string => {
    const classes: string[] = [];
    if ( item.auction_is_closed ) {
        classes.push( 'finished' );
    }
    if ( item.auction_fail_reason === '1' ) {
        classes.push( 'no_bid', 'fail' );
    }
    if ( item.auction_fail_reason === '2' ) {
        classes.push( 'no_reserve', 'fail' );
    }
    if ( item.auction_closed_val === '3' ) {
        classes.push( 'sold' );
    }
    if ( item.auction_is_payed ) {
        classes.push( 'payed' );
    }
    return classes.join( ' ' );
};

// ── Component ─────────────────────────────────────────────────────────────────

function AuctionList() {
    const toast = useToast();

    const [ filterArgs, setFilterArgs ] = useState< AuctionFilterState >( {
        page: 1,
        per_page: 10,
        status: 'all',
        search: '',
        start_date: '',
        end_date: '',
    } );

    const [ view, setView ] = useState( () => ( {
        type: 'table',
        perPage: 10,
        page: 1,
        search: '',
        status: 'all',
        fields: applyFilters( 'dokan_auction_list_view_fields', [
            'name',
            'type',
            'stock',
            'status',
            'price',
        ] ) as string[],
        layout: {
            styles: {
                name: {
                    width: '30%',
                },
                type: {
                    width: '10%',
                },
                stock: {
                    width: '10%',
                },
                status: {
                    width: '10%',
                },
                price: {
                    width: '10%',
                },
            },
        },
    } ) );

    // Date range picker state (visual/picker state, separate from applied filter)
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

    const {
        data,
        isLoading,
        totalItems,
        totalPages,
        statusCounts,
        subscriptionRemaining,
        fetchProducts,
        fetchStatusCounts,
        deleteProduct,
    } = useAuctionProducts( filterArgs );

    // ── Subscription limits ───────────────────────────────────────────────────

    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const config: AuctionListingConfig =
        ( window as any ).dokanFrontend?.auction_listing ?? {};
    const subscriptionInfo = config.subscription;

    const effectiveRemaining: true | number | undefined =
        subscriptionRemaining?.remaining_products ??
        subscriptionInfo?.remaining_products;
    const effectiveCanPost: boolean =
        subscriptionRemaining?.can_post_product ??
        subscriptionInfo?.can_post_product ??
        true;

    const subscriptionLimitReached =
        subscriptionInfo !== undefined &&
        ( effectiveRemaining === 0 || ! effectiveCanPost );

    // ── Live config (merges runtime subscription state into page config) ─────

    const liveConfig = useMemo( (): AuctionListingConfig => {
        if ( subscriptionInfo && effectiveRemaining !== undefined ) {
            return {
                ...config,
                subscription: {
                    ...subscriptionInfo,
                    remaining_products: effectiveRemaining,
                    can_post_product: effectiveCanPost,
                },
            };
        }
        return config;
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [ effectiveRemaining, effectiveCanPost ] );

    // ── Page notices (subscription notice, etc.) ──────────────────────────────

    const pageNotices = useMemo( () => {
        return applyFilters(
            'dokan_product_list_page_notices',
            [] as JSX.Element[],
            liveConfig
        ) as JSX.Element[];
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [ liveConfig ] );

    // ── Header buttons ────────────────────────────────────────────────────────

    const headerButtons = useMemo( () => {
        const buttons: JSX.Element[] = [];

        if (
            ! subscriptionLimitReached &&
            liveConfig.can_add_product &&
            liveConfig.new_product_url
        ) {
            buttons.push(
                <a
                    key="add-auction"
                    href={ liveConfig.new_product_url }
                    className="dokan-btn dokan-btn-theme"
                >
                    <Plus className="inline-block w-4 h-4 mr-1 -mt-0.5" />
                    { __( 'Add New Auction Product', 'dokan' ) }
                </a>
            );
        }

        if ( liveConfig.activity_url ) {
            buttons.push(
                <a
                    key="auction-activity"
                    href={ liveConfig.activity_url }
                    className="dokan-btn dokan-btn-secondary"
                >
                    <Gavel className="inline-block w-4 h-4 mr-1 -mt-0.5" />
                    { __( 'Auctions Activity', 'dokan' ) }
                </a>
            );
        }

        return applyFilters(
            'dokan_auction_list_header_buttons',
            buttons,
            liveConfig
        ) as JSX.Element[];
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [ liveConfig, subscriptionLimitReached ] );

    // ── Status tabs ───────────────────────────────────────────────────────────

    const onTabSelect = ( value: string ) => {
        setFilterArgs( ( prev ) => ( {
            ...prev,
            status: value as AuctionStatus,
            page: 1,
            search: '',
        } ) );
        setView( ( prev ) => ( {
            ...prev,
            page: 1,
            status: value,
            search: '',
        } ) );
    };

    const tabs = useMemo( () => {
        const countMap: Record< string, number > = {};
        statusCounts.forEach( ( s ) => {
            countMap[ s.value ] = s.count;
        } );

        return {
            items: [
                {
                    value: 'all',
                    label: __( 'All', 'dokan' ),
                    count: countMap.all ?? 0,
                },
                {
                    value: 'publish',
                    label: __( 'Published', 'dokan' ),
                    count: countMap.publish ?? 0,
                },
                {
                    value: 'draft',
                    label: __( 'Draft', 'dokan' ),
                    count: countMap.draft ?? 0,
                },
                {
                    value: 'pending',
                    label: __( 'Pending Review', 'dokan' ),
                    count: countMap.pending ?? 0,
                },
                {
                    value: 'reject',
                    label: __( 'Rejected', 'dokan' ),
                    count: countMap.reject ?? 0,
                },
            ],
            onSelect: onTabSelect,
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [ statusCounts ] );

    // ── Fields (columns) ─────────────────────────────────────────────────────

    const fields = useMemo(
        () => [
            {
                id: 'name',
                label: __( 'Products', 'dokan' ),
                enableSorting: false,
                isPrimary: true,
                render: ( { item }: { item: AuctionProduct } ) => (
                    <div className="flex items-center gap-3">
                        <div className="w-12 h-12 rounded-lg overflow-hidden bg-gray-100 shrink-0">
                            { item.images?.[ 0 ]?.src ? (
                                <img
                                    src={ item.images[ 0 ].src }
                                    alt={ item.images[ 0 ].alt || item.name }
                                    className="w-full h-full object-cover"
                                />
                            ) : (
                                <div className="w-full h-full bg-gray-100" />
                            ) }
                        </div>
                        <div>
                            <a
                                href={ item.edit_url }
                                className="font-medium text-dokan-link no-underline block"
                            >
                                { item.name }
                            </a>
                            <span className="text-xs text-gray-500 block">
                                { __( 'SKU:', 'dokan' ) } { item.sku || '—' }
                            </span>
                        </div>
                    </div>
                ),
            },
            {
                id: 'type',
                label: __( 'Type', 'dokan' ),
                enableSorting: false,
                render: ( { item }: { item: AuctionProduct } ) => (
                    <DokanTooltip content={ __( 'Auction', 'dokan' ) }>
                        <span className="inline-flex items-center">
                            <Gavel
                                className={ `w-5 h-5 ${
                                    getAuctionIconClass( item )
                                        ? 'text-gray-400'
                                        : 'text-gray-500'
                                }` }
                            />
                        </span>
                    </DokanTooltip>
                ),
            },
            {
                id: 'stock',
                label: __( 'Stock', 'dokan' ),
                enableSorting: false,
                render: ( { item }: { item: AuctionProduct } ) => (
                    <span
                        className={
                            item.in_stock ? 'text-green-600' : 'text-red-600'
                        }
                    >
                        { item.in_stock
                            ? __( 'In stock', 'dokan' )
                            : __( 'Out of stock', 'dokan' ) }
                    </span>
                ),
            },
            {
                id: 'status',
                label: __( 'Status', 'dokan' ),
                enableSorting: false,
                render: ( { item }: { item: AuctionProduct } ) => (
                    <DokanBadge
                        variant={ getStatusBadgeVariant( item.status ) }
                        label={ getStatusLabel( item.status ) }
                    />
                ),
            },
            {
                id: 'price',
                label: __( 'Price', 'dokan' ),
                enableSorting: false,
                render: ( { item }: { item: AuctionProduct } ) => {
                    if ( ! item.price_html ) {
                        return <span className="text-gray-400">{ '—' }</span>;
                    }
                    return (
                        <span
                            dangerouslySetInnerHTML={ {
                                __html: item.price_html,
                            } }
                        />
                    );
                },
            },
        ],
        []
    );

    /**
     * Filter the auction list table fields.
     * Allows Pro modules (e.g. product-adv) to inject extra columns such as Advertise.
     */
    // eslint-disable-next-line react-hooks/exhaustive-deps
    const filteredFields = useMemo(
        () =>
            applyFilters(
                'dokan_product_list_table_fields',
                fields,
                filterArgs
            ) as typeof fields,
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [ fields, filterArgs ]
    );

    // ── Filter fields ─────────────────────────────────────────────────────────

    const filter = {
        fields: [
            {
                id: 'date-range',
                label: __( 'Date Range', 'dokan' ),
                field: (
                    <DateRangePicker
                        key="auction-date-range"
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

    // ── Row actions ───────────────────────────────────────────────────────────

    const actions = useMemo( () => {
        const cfg: AuctionListingConfig =
            ( window as any ).dokanFrontend?.auction_listing ?? {};
        const canDuplicate = cfg.can_duplicate_product ?? false;

        const editAction = {
            id: 'edit',
            label: () => __( 'Edit', 'dokan' ),
            isEligible: ( item: AuctionProduct ) => !! item.edit_url,
            callback: ( [ item ]: AuctionProduct[] ) => {
                if ( item.edit_url ) {
                    window.location.href = item.edit_url;
                }
            },
        };

        const viewAction = {
            id: 'view-in-site',
            label: () => __( 'View', 'dokan' ),
            isEligible: ( item: AuctionProduct ) =>
                item.status === 'publish' && !! item.permalink,
            callback: ( [ item ]: AuctionProduct[] ) => {
                if ( item.permalink ) {
                    window.open( item.permalink, '_blank' );
                }
            },
        };

        const deleteAction = {
            id: 'delete',
            label: () => __( 'Delete Permanently', 'dokan' ),
            isDestructive: true,
            confirmTitle: __( 'Delete Auction Product', 'dokan' ),
            confirmMessage: __(
                'This auction product will be permanently deleted. This action cannot be undone.',
                'dokan'
            ),
            callback: async ( [ item ]: AuctionProduct[] ) => {
                try {
                    await deleteProduct( item.id );
                    toast( {
                        type: 'success',
                        title: __(
                            'Auction product deleted successfully.',
                            'dokan'
                        ),
                    } );
                    fetchProducts();
                    fetchStatusCounts();
                } catch {
                    toast( {
                        type: 'error',
                        title: __(
                            'Failed to delete auction product.',
                            'dokan'
                        ),
                    } );
                }
            },
        };

        const duplicateAction = canDuplicate
            ? [ createDuplicateAction( { fetchProducts, fetchStatusCounts } ) ]
            : [];

        return [ editAction, viewAction, ...duplicateAction, deleteAction ];
    }, [ deleteProduct, fetchProducts, fetchStatusCounts, toast ] );

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

    // ── Render ────────────────────────────────────────────────────────────────

    return (
        <>
            { headerButtons.length > 0 && (
                <Fill name="dokan-header-actions">{ headerButtons }</Fill>
            ) }

            { pageNotices.length > 0 && (
                <Fill name="dokan-before-header">
                    { pageNotices.map( ( notice, i ) => (
                        <div key={ i } className="col-span-4">
                            { notice }
                        </div>
                    ) ) }
                </Fill>
            ) }

            <DataViews
                namespace="dokan-auction-data-view"
                data={ data }
                fields={ filteredFields }
                view={ view }
                onChangeView={ onViewChange }
                getItemId={ ( item: AuctionProduct ) => item.id }
                isLoading={ isLoading }
                paginationInfo={ { totalItems, totalPages } }
                tabs={ tabs }
                filter={ filter }
                search={ true }
                actions={ actions }
            />
            <DokanToaster />
        </>
    );
}

export default AuctionList;
