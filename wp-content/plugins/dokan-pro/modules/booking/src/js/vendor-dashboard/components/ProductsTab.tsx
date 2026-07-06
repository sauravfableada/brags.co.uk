import { useState, useMemo, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { addAction, applyFilters, removeAction } from '@wordpress/hooks';
import { Fill } from '@wordpress/components';
import { useToast } from '@getdokan/dokan-ui';
import { CalendarDays, Plus } from 'lucide-react';
import {
    DataViews,
    DokanBadge,
    PriceHtml,
    DokanLink,
    DokanTooltip,
    // @ts-ignore
    // eslint-disable-next-line import/no-unresolved
    Select,
} from '@dokan/components';
import { Button } from '@wedevs/plugin-ui';
import { createDuplicateAction } from '@dokan-pro/features/products/actions/duplicate';
import { useBookingProducts } from '../hooks/useBookingProducts';
import { useBookingProductCategories } from '../hooks/useBookingProductCategories';
import type { BookingProduct, BookingProductFilterState } from '../types';

// ── Helpers ──────────────────────────────────────────────────────────────────

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

// ── Localized data ───────────────────────────────────────────────────────────

interface BookingConfig {
    bookingUrl?: string;
    isGlobalAddonRmaActive?: boolean;
    isSellerEnabled?: boolean;
    canAddProduct?: boolean;
    canDuplicateProduct?: boolean;
    subscription?: {
        remaining_products: true | number;
        can_post_product: boolean;
        subscription_url?: string;
    };
}

interface ProductListingConfig {
    can_add_product?: boolean;
    subscription?: {
        remaining_products: true | number;
        can_post_product: boolean;
        subscription_url?: string;
    };
}

// ── Component ────────────────────────────────────────────────────────────────

const ProductsTab = () => {
    const toast = useToast();

    // @ts-ignore
    const bookingCfg: BookingConfig = window.dokanBooking ?? {};
    const bookingUrl = bookingCfg.bookingUrl || '';
    const isGlobalAddonRmaActive = !! bookingCfg.isGlobalAddonRmaActive;
    const isSellerEnabled = bookingCfg.isSellerEnabled !== false;
    const canAddProduct = bookingCfg.canAddProduct !== false;

    const subscriptionInfo = bookingCfg.subscription;

    // ── Filter state (matches ProductFilterState shape for shared hooks) ─────

    const [ filterArgs, setFilterArgs ] = useState< BookingProductFilterState >(
        {
            page: 1,
            per_page: 10,
            status: 'all',
            search: '',
            category: '',
            year_month: '',
        }
    );

    const [ view, setView ] = useState( {
        perPage: 10,
        page: 1,
        search: '',
        type: 'table',
        status: 'all',
        fields: [ 'name', 'type', 'stock', 'status', 'price', 'advertise' ],
        layout: {
            styles: {
                name: {
                    width: '20%',
                },
                type: {
                    width: '15%',
                },
                stock: {
                    width: '15%',
                },
                status: {
                    width: '15%',
                },
                price: {
                    width: '15%',
                },
                advertise: {
                    width: '20%',
                },
            },
        },
    } );

    // ── Data hooks ───────────────────────────────────────────────────────────

    const {
        data,
        isLoading,
        totalItems,
        totalPages,
        statusCounts,
        monthOptions,
        lowStockThreshold,
        subscriptionRemaining,
        fetchProducts,
        fetchStatusCounts,
        deleteProduct,
    } = useBookingProducts( filterArgs );

    const { options: categoryOptions } = useBookingProductCategories();

    // ── Subscription logic ───────────────────────────────────────────────────

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

    // ── Page notices (subscription notices via shared hook) ───────────────────

    const pageNotices = useMemo( () => {
        if ( ! subscriptionInfo ) {
            return [] as JSX.Element[];
        }

        const config: ProductListingConfig = {
            can_add_product: canAddProduct,
            subscription: {
                ...subscriptionInfo,
                remaining_products:
                    effectiveRemaining ?? subscriptionInfo.remaining_products,
                can_post_product: effectiveCanPost,
            },
        };

        return applyFilters(
            'dokan_product_list_page_notices',
            [] as JSX.Element[],
            config
        ) as JSX.Element[];
    }, [
        subscriptionInfo,
        effectiveRemaining,
        effectiveCanPost,
        canAddProduct,
    ] );

    // ── Header buttons ───────────────────────────────────────────────────────

    const showButtons =
        isSellerEnabled && canAddProduct && ! subscriptionLimitReached;

    // ── Fields (columns) — no earning ────────────────────────────────────────

    const fields = useMemo(
        () => [
            {
                id: 'name',
                label: __( 'Products', 'dokan' ),
                enableSorting: false,
                isPrimary: true,
                render: ( { item }: { item: BookingProduct } ) => (
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
                                href={ `${ bookingUrl }edit/?product_id=${ item.id }` }
                                className="font-medium text-dokan-link cursor-pointer block focus:outline-none!"
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
                render: () => (
                    <DokanTooltip content={ __( 'Booking', 'dokan' ) }>
                        <span className="inline-flex items-center">
                            <CalendarDays className="w-5 h-5 text-gray-500" />
                        </span>
                    </DokanTooltip>
                ),
            },
            {
                id: 'stock',
                label: __( 'Stock', 'dokan' ),
                enableSorting: false,
                render: ( { item }: { item: BookingProduct } ) => {
                    if ( item.manage_stock && item.stock_quantity !== null ) {
                        const qty = item.stock_quantity;
                        const isLow = qty <= lowStockThreshold;
                        return (
                            <span
                                className={
                                    isLow
                                        ? 'text-red-600 font-medium'
                                        : 'text-green-600 font-medium'
                                }
                            >
                                { qty }
                            </span>
                        );
                    }
                    return (
                        <span
                            className={
                                item.in_stock
                                    ? 'text-green-600'
                                    : 'text-red-600'
                            }
                        >
                            { item.in_stock
                                ? __( 'In stock', 'dokan' )
                                : __( 'Out of stock', 'dokan' ) }
                        </span>
                    );
                },
            },
            {
                id: 'status',
                label: __( 'Status', 'dokan' ),
                enableSorting: false,
                render: ( { item }: { item: BookingProduct } ) => (
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
                render: ( { item }: { item: BookingProduct } ) => {
                    if ( ! item.price ) {
                        return <span className="text-gray-400">{ '—' }</span>;
                    }
                    if (
                        item.on_sale &&
                        item.regular_price &&
                        item.sale_price
                    ) {
                        return (
                            <div className="flex flex-col">
                                <span className="line-through text-red-400">
                                    <PriceHtml price={ item.regular_price } />
                                </span>
                                <span className="text-green-600 font-medium">
                                    <PriceHtml price={ item.sale_price } />
                                </span>
                            </div>
                        );
                    }
                    return <PriceHtml price={ item.price } />;
                },
            },
        ],
        [ bookingUrl, lowStockThreshold ]
    );

    /**
     * Apply the shared product-list field hook so Pro modules (e.g. advertise)
     * can inject columns, then strip the earning column.
     */
    const filteredFields = useMemo( () => {
        const withHooks = applyFilters(
            'dokan_product_list_table_fields',
            fields,
            filterArgs
        ) as typeof fields;

        return withHooks.filter( ( f ) => f.id !== 'earning' );
    }, [ fields, filterArgs ] );

    // ── Filter fields (Date + Category base, Brand + Other via shared hook) ──

    const filterFields = useMemo(
        () => [
            {
                id: 'year_month',
                label: __( 'Date', 'dokan' ),
                field: (
                    <Select
                        key="month-select"
                        isClearable
                        placeholder={ __( 'All dates', 'dokan' ) }
                        options={ monthOptions }
                        value={
                            monthOptions.find(
                                ( o ) => o.value === filterArgs.year_month
                            ) ?? null
                        }
                        onChange={ ( option: { value: string } | null ) => {
                            setFilterArgs( ( prev ) => ( {
                                ...prev,
                                year_month: option?.value ?? '',
                                page: 1,
                            } ) );
                        } }
                    />
                ),
            },
            {
                id: 'category',
                label: __( 'Category', 'dokan' ),
                field: (
                    <Select
                        key="category-select"
                        isClearable
                        placeholder={ __( 'All categories', 'dokan' ) }
                        options={ categoryOptions }
                        value={
                            categoryOptions.find(
                                ( o ) => o.value === filterArgs.category
                            ) ?? null
                        }
                        onChange={ ( option: { value: number } | null ) => {
                            setFilterArgs( ( prev ) => ( {
                                ...prev,
                                category: option?.value ?? '',
                                page: 1,
                            } ) );
                        } }
                    />
                ),
            },
        ],
        [
            monthOptions,
            categoryOptions,
            filterArgs.year_month,
            filterArgs.category,
        ]
    );

    /**
     * Apply the shared filter hook so Brand and Other (from Pro subscription
     * and brand-filter modules) get added to the filter bar.
     */
    const allFilterFields = useMemo(
        () =>
            applyFilters(
                'dokan_product_list_filter_fields',
                filterFields,
                filterArgs,
                setFilterArgs
            ) as typeof filterFields,
        [ filterFields, filterArgs ]
    );

    // ── Tabs (status filter) ─────────────────────────────────────────────────

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
                    value: 'pending',
                    label: __( 'Pending Review', 'dokan' ),
                    count: countMap.pending ?? 0,
                },
                {
                    value: 'draft',
                    label: __( 'Draft', 'dokan' ),
                    count: countMap.draft ?? 0,
                },
                {
                    value: 'reject',
                    label: __( 'Rejected', 'dokan' ),
                    count: countMap.reject ?? 0,
                },
            ],
            onSelect: ( value: string ) => {
                setFilterArgs( ( prev ) => ( {
                    ...prev,
                    status: value,
                    page: 1,
                    search: '',
                } ) );
                setView( ( prev ) => ( {
                    ...prev,
                    page: 1,
                    status: value,
                    search: '',
                } ) );
            },
        };
    }, [ statusCounts ] );

    // ── Filter callbacks ─────────────────────────────────────────────────────

    const onFilterRemove = ( filterId: string ) => {
        setFilterArgs( ( prev ) => ( {
            ...prev,
            [ filterId ]: '',
            page: 1,
        } ) );
    };

    const onFilterReset = () => {
        setFilterArgs( ( prev ) => ( {
            ...prev,
            category: '',
            year_month: '',
            product_brand: '',
            filter_by_other: '',
            page: 1,
        } ) );
    };

    // ── Actions (row only — no bulk) ─────────────────────────────────────────

    const actions = useMemo( () => {
        const cfg: BookingConfig = ( window as any ).dokanBooking ?? {};
        const canDuplicate = !! cfg.canDuplicateProduct;

        const editAction = {
            id: 'edit-details',
            label: () => __( 'Edit', 'dokan' ),
            callback: ( [ item ]: BookingProduct[] ) => {
                window.location.href =
                    bookingUrl + 'edit/?product_id=' + item.id;
            },
        };

        const deleteAction = {
            id: 'delete',
            label: __( 'Delete Permanently', 'dokan' ),
            isDestructive: true,
            callback: async ( items: BookingProduct[] ) => {
                try {
                    await deleteProduct( items[ 0 ].id );
                    toast( {
                        type: 'success',
                        title: __(
                            'Product deleted successfully.',
                            'dokan'
                        ),
                    } );
                    fetchProducts();
                    fetchStatusCounts();
                } catch {
                    toast( {
                        type: 'error',
                        title: __( 'Failed to delete product.', 'dokan' ),
                    } );
                }
            },
        };

        const viewAction = {
            id: 'view-in-site',
            label: () => __( 'View in site', 'dokan' ),
            isEligible: ( item: BookingProduct ) =>
                item.status === 'publish' && !! item.permalink,
            callback: ( [ item ]: BookingProduct[] ) => {
                if ( item.permalink ) {
                    window.open( item.permalink, '_blank' );
                }
            },
        };

        const duplicateAction = canDuplicate
            ? [ createDuplicateAction( { fetchProducts, fetchStatusCounts } ) ]
            : [];

        return [ editAction, deleteAction, ...duplicateAction, viewAction ];
    }, [ bookingUrl, deleteProduct, fetchProducts, fetchStatusCounts, toast ] );

    // ── View change ──────────────────────────────────────────────────────────

    const onViewChange = ( newView: typeof view ) => {
        setView( newView );
        setFilterArgs( ( prev ) => ( {
            ...prev,
            page: newView.page,
            per_page: newView.perPage,
            search: newView.search ?? '',
        } ) );
    };

    // ── Refresh action ───────────────────────────────────────────────────────

    useEffect( () => {
        addAction(
            'dokan_product_list_refresh',
            'dokan/booking-product-list',
            () => {
                fetchProducts();
                fetchStatusCounts();
            }
        );
        return () => {
            removeAction(
                'dokan_product_list_refresh',
                'dokan/booking-product-list'
            );
        };
    }, [] );

    // ── Render ───────────────────────────────────────────────────────────────

    return (
        <>
            { /* Subscription notices — injected into the header area */ }
            { ! isLoading && pageNotices.length > 0 && (
                <Fill name="dokan-before-header">
                    { pageNotices.map( ( notice, i ) => (
                        <div key={ i } className="col-span-4">
                            { notice }
                        </div>
                    ) ) }
                </Fill>
            ) }

            { /* Header buttons — appear in the same row as the page title */ }
            { showButtons && (
                <Fill name="dokan-header-actions">
                    <DokanLink as="a" href={ bookingUrl + 'new-product' }>
                        <Button
                            variant="default"
                            className="inline-flex items-center gap-1"
                        >
                            <Plus size={ 16 } />
                            { __( 'Add New Booking Product', 'dokan' ) }
                        </Button>
                    </DokanLink>
                    { ! isGlobalAddonRmaActive && (
                        <DokanLink as="a" href={ bookingUrl + 'add-booking' }>
                            <Button
                                variant="secondary"
                                className="dokan-btn dokan-btn-secondary inline-flex items-center gap-1"
                            >
                                <CalendarDays size={ 16 } />
                                { __( 'Add Booking', 'dokan' ) }
                            </Button>
                        </DokanLink>
                    ) }
                </Fill>
            ) }

            <DataViews
                namespace="booking-products-data-view"
                data={ data }
                fields={ filteredFields }
                view={ view }
                onChangeView={ onViewChange }
                getItemId={ ( item: BookingProduct ) => item.id }
                isLoading={ isLoading }
                paginationInfo={ { totalItems, totalPages } }
                tabs={ tabs }
                filter={ {
                    fields: allFilterFields,
                    onFilterRemove,
                    onReset: onFilterReset,
                } }
                search={ true }
                actions={ actions }
            />
        </>
    );
};

export default ProductsTab;
