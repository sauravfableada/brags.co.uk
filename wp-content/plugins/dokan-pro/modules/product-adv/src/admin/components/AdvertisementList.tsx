import { addQueryArgs } from '@wordpress/url';
import { __, sprintf } from '@wordpress/i18n';
import { useCallback, useEffect, useState, RawHTML } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { formatPrice, truncate } from '@dokan/utilities';
import { SimpleInput, useToast, DokanToaster } from '@getdokan/dokan-ui';
import { dateI18n, getSettings } from '@wordpress/date';
import {
    Select,
    DataViews,
    VendorAsyncSelect,
    DateRangePicker,
    SearchInput,
    DokanButton,
    DokanTooltip as Tooltip,
    getActionLabel,
    // @ts-ignore
    // eslint-disable-next-line import/no-unresolved
} from '@dokan/components';

import AddAdvertisementModal from './AddAdvertisementModal';

import { Trash, Clock, Home, Calendar, Plus, FunnelPlus } from 'lucide-react';
import { twMerge } from 'tailwind-merge';

// Define advertisement statuses for tab filtering
const ADVERTISEMENT_STATUSES = [
    { value: 0, label: __( 'All', 'dokan' ) },
    { value: 1, label: __( 'Active', 'dokan' ) },
    { value: 2, label: __( 'Expired', 'dokan' ) },
];

// Define created via options for filter
const CREATED_VIA_OPTIONS = [
    { value: '', label: __( 'Created Via', 'dokan' ) },
    { value: 'admin', label: __( 'Admin', 'dokan' ) },
    { value: 'order', label: __( 'Purchase', 'dokan' ) },
    { value: 'subscription', label: __( 'Subscription', 'dokan' ) },
    { value: 'free', label: __( 'Free Purchase', 'dokan' ) },
];

type VendorSelect = {
    label: string;
    value: string;
    raw: unknown;
};

type CreatedBySelect = {
    value: string;
    label: string;
};
const AdvertisementPage = () => {
    const [ data, setData ] = useState( [] );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ totalItems, setTotalItems ] = useState( 0 );
    const [ statusCounts, setStatusCounts ] = useState( {
        all: 0,
        active: 0,
        expired: 0,
    } );
    const [ filterArgs, setFilterArgs ] = useState( {
        vendor_id: null,
        created_via: '',
        expires_at: null,
    } );
    const [ activeStatus, setActiveStatus ] = useState( 0 );
    const [ vendorFilter, setVendorFilter ] = useState< VendorSelect | null >(
        null
    );
    const [ createdByFilter, setCreatedByFilter ] =
        useState< CreatedBySelect | null >( null );
    const [ after, setAfter ] = useState( '' );
    const [ afterText, setAfterText ] = useState( '' );
    const [ before, setBefore ] = useState( '' );
    const [ beforeText, setBeforeText ] = useState( '' );
    const [ focusInput, setFocusInput ] = useState( 'startDate' );
    const [ searchText, setSearchText ] = useState( '' );
    const [ addModalOpen, setAddModalOpen ] = useState( false );
    const toast = useToast();

    const RenderContent = ( { content, className = '', length = 22 } ) => {
        if ( content.length > length ) {
            return (
                <Tooltip content={ content }>
                    <div
                        className={ twMerge(
                            'line-clamp-2 text-wrap text-sm text-gray-600',
                            className
                        ) }
                    >
                        <RawHTML>{ truncate( content, length ) }</RawHTML>
                    </div>
                </Tooltip>
            );
        }
        return (
            <div
                className={ twMerge(
                    'text-sm text-gray-600 text-wrap',
                    className
                ) }
            >
                <RawHTML>{ content }</RawHTML>
            </div>
        );
    };

    // Define fields for the table columns
    const fields = [
        {
            id: 'product_title',
            label: __( 'Product Name', 'dokan' ),
            enableGlobalSearch: true,
            enableSorting: false,
            render: ( { item } ) => (
                <RenderContent
                    content={ item.product_title }
                    className="font-semibold"
                />
            ),
        },
        {
            id: 'store_name',
            label: __( 'Store Name', 'dokan' ),
            enableGlobalSearch: true,
            enableSorting: false,
            render: ( { item } ) => (
                <RenderContent content={ item.store_name } />
            ),
        },
        {
            id: 'created_via',
            label: __( 'Created Via', 'dokan' ),
            enableGlobalSearch: true,
            render: ( { item } ) => (
                <div className="text-gray-600 capitalize">
                    { formatCreatedVia( item.created_via ) }
                </div>
            ),
        },
        {
            id: 'order_id',
            label: __( 'Order ID', 'dokan' ),
            enableSorting: false,
            render: ( { item } ) => (
                <div className="text-gray-600">
                    { item.order_id || __( '-', 'dokan' ) }
                </div>
            ),
        },
        {
            id: 'price',
            label: __( 'Cost', 'dokan' ),
            enableSorting: false,
            render: ( { item } ) => (
                <div className="text-gray-900 font-medium">
                    <RawHTML>{ formatPrice( item.price ) }</RawHTML>
                </div>
            ),
        },
        {
            id: 'expires_at',
            label: __( 'Expires', 'dokan' ),
            enableSorting: false,
            render: ( { item } ) => (
                <div className="text-gray-900">
                    { item.expires_at || __( '-', 'dokan' ) }
                </div>
            ),
        },
        {
            id: 'added',
            label: __( 'Date', 'dokan' ),
            enableSorting: false,
            render: ( { item } ) => (
                <div className="text-gray-900">
                    { item.added || __( '-', 'dokan' ) }
                </div>
            ),
        },
        {
            id: 'status',
            label: __( 'Status', 'dokan' ),
            enableSorting: false,
            render: ( { item } ) => {
                const statusColors = {
                    1: 'bg-green-100 text-green-800',
                    2: 'bg-gray-100 text-gray-600',
                };
                return (
                    <span
                        className={ `inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-medium ${
                            statusColors[ item.status ] ||
                            'bg-gray-100 text-gray-600'
                        }` }
                    >
                        { item.status === 1
                            ? __( 'Active', 'dokan' )
                            : __( 'Expired', 'dokan' ) }
                    </span>
                );
            },
        },
    ];

    const displayDateRange = ( startDate, endDate ) => {
        if ( ! startDate || ! endDate ) {
            return '';
        }
        // eslint-disable-next-line @wordpress/valid-sprintf
        return sprintf(
            // eslint-disable-next-line @wordpress/i18n-translator-comments
            __( '%s - %s', 'dokan' ),
            dateI18n( getSettings().formats.date, startDate ),
            dateI18n( getSettings().formats.date, endDate )
        );
    };

    const formatCreatedVia = ( type: string ) => {
        const createdBy = CREATED_VIA_OPTIONS.find(
            ( item ) => item.value === type
        );
        if ( createdBy ) {
            return createdBy.label;
        }
        return __( 'Admin', 'dokan' );
    };

    const actions = [
        {
            id: 'expire',
            label: () => getActionLabel( <Clock size={ 16 } className="fill-none!" />, __( 'Expire', 'dokan' ) ),
            icon: <Clock size={ 16 } className="fill-none!" />,
            isPrimary: false,
            supportsBulk: true,
            isDestructive: true,
            confirmTitle: __( 'Expire Advertisement', 'dokan' ),
            confirmMessage: __(
                'Are you sure you want to expire the selected advertisement(s)?',
                'dokan'
            ),
            confirmButtonLabel: __( 'Yes, Expire', 'dokan' ),
            isEligible: ( item ) => item?.status === 1,
            callback: async ( items: any[] ) => {
                await handleBulkAction(
                    'expire',
                    items.map( ( item ) => item.id )
                );
            },
        },
        {
            id: 'delete',
            label: () => getActionLabel( <Trash size={ 16 } className="fill-none!" />, __( 'Delete', 'dokan' ) ),
            icon: <Trash size={ 16 } className="fill-none!" />,
            supportsBulk: true,
            isDestructive: true,
            confirmTitle: __( 'Delete Advertisement', 'dokan' ),
            confirmMessage: __(
                'Are you sure you want to delete the selected advertisement(s)? This action cannot be undone.',
                'dokan'
            ),
            confirmButtonLabel: __( 'Yes, Delete', 'dokan' ),
            callback: async ( items: any[] ) => {
                await handleBulkAction(
                    'delete',
                    items.map( ( item ) => item.id )
                );
            },
        },
    ];

    // Set for handling bulk selection
    const [ selection, setSelection ] = useState( [] );

    // Set data view default layout
    const defaultLayouts = {
        table: {},
        grid: {},
        list: {},
        density: 'comfortable',
    };

    // Set view state for handling the table view
    const [ view, setView ] = useState( {
        perPage: 10,
        page: 1,
        search: searchText,
        type: 'table',
        titleField: 'product_title',
        status: 0,
        layout: defaultLayouts,
        fields: [
            'store_name',
            'created_via',
            'order_id',
            'price',
            'expires_at',
            'added',
            'status',
        ],
    } );

    // Handle tab selection for status filtering
    const handleTabSelect = ( tabName ) => {
        const statusValue = parseInt( tabName );
        setActiveStatus( statusValue );
        setView( ( prevView ) => ( {
            ...prevView,
            status: statusValue,
            page: 1,
        } ) );
    };

    const statusCountFor = ( value: number ) => {
        if ( value === 0 ) {
            return statusCounts.all;
        }
        if ( value === 1 ) {
            return statusCounts.active;
        }
        return statusCounts.expired;
    };
    const tabItems = ADVERTISEMENT_STATUSES.map( ( status ) => ( {
        value: status.value.toString(),
        label: status.label,
        count: statusCountFor( status.value ),
    } ) );

    const closeAddModal = () => {
        setAddModalOpen( false );
    };

    // Handle data fetching from the server
    const fetchAdvertisements = useCallback( async () => {
        setIsLoading( true );
        try {
            const queryArgs = {
                per_page: view?.perPage ?? 10,
                page: view?.page ?? 1,
                search: view?.search ?? '',
                status: view?.status,
                ...filterArgs,
            };

            // Fetch data from the REST API
            const response = await apiFetch< any >( {
                path: addQueryArgs( 'dokan/v1/product_adv', queryArgs ),
                // @ts-ignore
                parse: false,
            } );

            const jsonData = await response.json();
            setTotalItems(
                parseInt( response.headers.get( 'X-WP-Total' ) || 0 )
            );

            setData( jsonData );

            // Extract status counts from response headers
            const allCount = parseInt(
                response.headers.get( 'X-Status-All' ) || 0
            );
            const activeCount = parseInt(
                response.headers.get( 'X-Status-Active' ) || 0
            );
            const expiredCount = parseInt(
                response.headers.get( 'X-Status-Expired' ) || 0
            );

            const counts = {
                all: allCount,
                active: activeCount,
                expired: expiredCount,
            };
            setStatusCounts( counts );
        } catch ( error ) {
            // eslint-disable-next-line no-console
            console.error( 'Error fetching advertisements:', error );
        } finally {
            setIsLoading( false );
        }
    }, [ view.page, view.perPage, view.search, view.status, filterArgs ] );

    // Handle bulk actions
    const handleBulkAction = async ( action, ids ) => {
        try {
            await apiFetch( {
                path: '/dokan/v1/product_adv/batch',
                method: 'POST',
                data: {
                    action,
                    ids,
                },
            } );

            void fetchAdvertisements();
            const messages = {
                expire: __( 'Advertisements expired successfully', 'dokan' ),
                delete: __( 'Advertisements deleted successfully', 'dokan' ),
            };
            toast( {
                type: 'success',
                title:
                    messages[ action ] ||
                    __( 'Bulk action completed successfully', 'dokan' ),
            } );
            setSelection( [] );
        } catch ( error ) {
            toast( {
                type: 'error',
                title:
                    error?.message ||
                    __( 'Error performing bulk action', 'dokan' ),
            } );
        }
    };

    // Handle filter changes - reset to page 1
    useEffect( () => {
        setView( ( prevView ) => ( {
            ...prevView,
            page: 1,
        } ) );
    }, [ filterArgs ] );

    // Update search in view
    useEffect( () => {
        setView( ( prevView ) => ( {
            ...prevView,
            search: searchText,
            page: 1,
        } ) );
    }, [ searchText ] );

    // Clear filters
    const clearFilter = () => {
        setVendorFilter( null );
        setCreatedByFilter( null );
        setAfter( '' );
        setAfterText( '' );
        setBefore( '' );
        setBeforeText( '' );
        setSearchText( '' );
        setFilterArgs( {
            vendor_id: null,
            created_via: '',
            expires_at: null,
        } );

        setView( ( prevView ) => ( {
            ...prevView,
            page: 1,
        } ) );
    };

    const clearSingleFilter = ( filterId: string ) => {
        const args = { ...filterArgs };
        switch ( filterId ) {
            case 'vendor':
                setVendorFilter( null );
                delete args.vendor_id;
                break;
            case 'created-via':
                setCreatedByFilter( null );
                delete args.created_via;
                break;
            case 'date-range':
                setAfter( '' );
                setAfterText( '' );
                setBefore( '' );
                setBeforeText( '' );
                delete args.expires_at;
                break;
        }
        setFilterArgs( args );
        setView( ( prevView ) => ( { ...prevView, page: 1 } ) );
    };

    const filterFields = [
        {
            id: 'vendor',
            label: __( 'Vendor', 'dokan' ),
            field: (
                <VendorAsyncSelect
                    key="vendor-select"
                    icon={ <Home size={ 16 } /> }
                    value={ vendorFilter }
                    onChange={ ( selected ) => {
                        const args = { ...filterArgs };
                        if ( selected ) {
                            args.vendor_id = [ parseInt( selected.value ) ];
                        }
                        setVendorFilter( selected );
                        setFilterArgs( args );
                    } }
                    placeholder={ __( 'Select Vendor', 'dokan' ) }
                    isClearable
                    prefetch
                    defaultOptions
                    cacheOptions
                />
            ),
        },
        {
            id: 'created-via',
            label: __( 'Created Via', 'dokan' ),
            field: (
                <Select
                    icon={ <FunnelPlus size={ 16 } /> }
                    selectedTitle={ __( 'Created Via', 'dokan' ) }
                    className="min-w-44 rounded-md"
                    placeholder={ __( 'Select Created Via', 'dokan' ) }
                    options={ CREATED_VIA_OPTIONS }
                    styles={ {
                        input: ( base: any ) => ( {
                            ...base,
                            fontSize: 14,
                            lineHeight: '22px',
                            marginTop: 1,
                            marginBottom: 1,
                        } ),
                        control: ( base: any ) => ( {
                            ...base,
                            paddingLeft: 4,
                            borderRadius: 6,
                        } ),
                    } }
                    onChange={ ( createdViaObj ) => {
                        const value = createdViaObj.value;
                        const args = { ...filterArgs };

                        delete args.created_via;

                        if ( value ) {
                            setCreatedByFilter(
                                CREATED_VIA_OPTIONS.find(
                                    ( opt ) => opt.value === value
                                )
                            );
                            args.created_via = value;
                        } else {
                            setCreatedByFilter( null );
                        }
                        setFilterArgs( args );
                    } }
                />
            ),
        },
        {
            id: 'date-range',
            label: __( 'Date Range', 'dokan' ),
            field: (
                <DateRangePicker
                    key="date-range-select"
                    after={ after }
                    afterText={ afterText }
                    before={ before }
                    beforeText={ beforeText }
                    onUpdate={ ( update ) => {
                        if ( update.after ) {
                            setAfter( update.after );
                        }

                        if ( update.afterText ) {
                            setAfterText( update.afterText );
                        }

                        if ( update.before ) {
                            setBefore( update.before );
                        }

                        if ( update.beforeText ) {
                            setBeforeText( update.beforeText );
                        }

                        if ( update.focusedInput ) {
                            setFocusInput( update.focusedInput );

                            if ( update.focusedInput === 'endDate' && after ) {
                                setBefore( '' );
                                setBeforeText( '' );
                            }
                        }
                    } }
                    shortDateFormat="MM/DD/YYYY"
                    focusedInput={ focusInput }
                    isInvalidDate={ () => false }
                    wrapperClassName="w-full"
                    pickerToggleClassName="block"
                    wpPopoverClassName="dokan-layout"
                    popoverBodyClassName="p-4 w-auto text-sm/6"
                    onClear={ () => {
                        setAfter( '' );
                        setAfterText( '' );
                        setBefore( '' );
                        setBeforeText( '' );
                        const args = { ...filterArgs };
                        delete args.expires_at;
                        setFilterArgs( args );
                    } }
                    onOk={ () => {
                        const args = { ...filterArgs };
                        args.expires_at = {
                            min: dateI18n( 'Y-m-d', after ) || null,
                            max: dateI18n( 'Y-m-d', before ) || null,
                        };
                        setFilterArgs( args );
                    } }
                >
                    <SimpleInput
                        addOnLeft={ <Calendar size="16" /> }
                        className="border rounded px-3 py-1.5 w-full bg-white"
                        onChange={ () => {} }
                        input={ {
                            type: 'text',
                            value: displayDateRange( after, before ),
                            placeholder: __( 'Filter by expire date', 'dokan' ),
                            readOnly: true,
                        } }
                    />
                </DateRangePicker>
            ),
        },
    ];

    // Fetch advertisements when view changes
    useEffect( () => {
        void fetchAdvertisements();
    }, [ fetchAdvertisements ] );

    return (
        <div className="advertisement-admin-page mt-6">
            <div className="flex justify-between items-center mb-6">
                <h2 className="text-2xl leading-3 text-gray-900 font-bold">
                    { __( 'Product Advertising', 'dokan' ) }
                </h2>
                <DokanButton
                    type="button"
                    variant="primary"
                    className="inline-flex items-center gap-2"
                    onClick={ () => setAddModalOpen( true ) }
                >
                    <Plus size={ 16 } />
                    { __( 'Add New', 'dokan' ) }
                </DokanButton>
            </div>

            { /* Data Table */ }
            <div className="dokan-admin-dashboard-datatable">
                <DataViews
                    data={ data }
                    namespace="advertisement-admin-data-view"
                    defaultLayouts={ defaultLayouts }
                    fields={ fields }
                    getItemId={ ( item ) => item.id }
                    onChangeView={ setView }
                    paginationInfo={ {
                        totalItems,
                        totalPages: Math.ceil( totalItems / view.perPage ),
                    } }
                    view={ view }
                    selection={ selection }
                    onChangeSelection={ setSelection }
                    actions={ actions }
                    isLoading={ isLoading }
                    tabs={ {
                        items: tabItems,
                        onSelect: ( tabName ) => {
                            setSelection( [] );
                            handleTabSelect( tabName );
                        },
                        defaultValue: activeStatus.toString(),
                        headerContent: [
                            <SearchInput
                                key="search-input"
                                value={ searchText }
                                onChange={ setSearchText }
                            />,
                        ],
                    } }
                    searchLabel={ __( 'Search by product or order', 'dokan' ) }
                    filter={ {
                        fields: filterFields,
                        onFilterRemove: clearSingleFilter,
                        onReset: () => clearFilter(),
                    } }
                />
            </div>

            { /* Add Advertisement modal */ }
            <AddAdvertisementModal
                isOpen={ addModalOpen }
                onClose={ closeAddModal }
                onAdded={ fetchAdvertisements }
            />
            <DokanToaster />
        </div>
    );
};

export default AdvertisementPage;
