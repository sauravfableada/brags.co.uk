import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect, RawHTML } from '@wordpress/element';
import { Notice } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { truncate } from '@dokan/utilities';
import { Eye, Trash, CircleHelp, Box, House } from 'lucide-react';
import { useToast, DokanToaster } from '@getdokan/dokan-ui';

// Import Dokan components
import {
    DataViews,
    DateTimeHtml,
    DokanModal,
    Select,
    VendorAsyncSelect,
    ProductAsyncSelect,
    DokanTooltip as Tooltip,
    getActionLabel,
} from '@dokan/components';

interface AbuseReport {
    id: number;
    reason: string;
    description: string;
    product: {
        id: number;
        title: string;
        admin_url: string;
    };
    vendor: {
        id: number;
        name: string;
        admin_url: string;
    };
    reported_by: {
        id: number;
        name: string;
        email: string;
        admin_url: string;
    };
    reported_at: string;
}

interface AbuseReason {
    id: string;
    value: string;
}

interface AsyncSelectInterface {
    label: string;
    value: string | number;
}

const ReportAbusePage = () => {
    const [ reports, setReports ] = useState< AbuseReport[] >( [] );
    const [ reasons, setReasons ] = useState< AbuseReason[] >( [] );
    const [ loading, setLoading ] = useState( true );
    const [ error, setError ] = useState< string | null >( null );
    const [ totalReports, setTotalReports ] = useState( 0 );
    const [ selectedReason, setSelectedReason ] =
        useState< null | AsyncSelectInterface >( null );
    const [ selectedVendor, setSelectedVendor ] =
        useState< null | AsyncSelectInterface >( null );
    const [ selectedProduct, setSelectedProduct ] =
        useState< null | AsyncSelectInterface >( null );
    const [ selectedReports, setSelectedReports ] = useState< number[] >( [] );
    const [ modalOpen, setModalOpen ] = useState( false );
    const [ modalItem, setModalItem ] = useState< AbuseReport | null >( null );
    const [ filterObject, setFilterObject ] = useState< {
        reason?: string | null;
        vendor_id?: string | null;
        product_id?: string | null;
    } >();

    const toast = useToast();

    // Set data view default layout
    const defaultLayouts = {
        table: {},
        grid: {},
        list: {},
        density: 'comfortable',
    };

    // Define fields for the table columns
    const fields = [
        {
            id: 'reason',
            label: __( 'Reason', 'dokan' ),
            enableGlobalSearch: true,
            render: ( { item }: { item: AbuseReport } ) =>
                loading ? (
                    <span className="block w-16 h-3 rounded bg-gray-200 animate-pulse"></span>
                ) : (
                    <Tooltip content={ <RawHTML>{ item.reason }</RawHTML> }>
                        <div
                            className="font-medium text-[#7047EB] m-0 space-x-2 flex flex-wrap max-w-64 text-wrap cursor-pointer"
                            onClick={ () => {
                                setModalItem( item );
                                setModalOpen( true );
                            } }
                        >
                            <RawHTML>
                                { truncate
                                    ? truncate( item.reason, 22 )
                                    : item.reason }
                            </RawHTML>
                        </div>
                    </Tooltip>
                ),
        },
        {
            id: 'product',
            label: __( 'Product', 'dokan' ),
            render: ( { item }: { item: AbuseReport } ) => {
                if ( loading ) {
                    return (
                        <span className="block w-16 h-3 rounded bg-gray-200 animate-pulse"></span>
                    );
                }

                if ( ! item.product ) {
                    return (
                        <div className="text-gray-500">
                            { __( 'N/A', 'dokan' ) }
                        </div>
                    );
                }
                return (
                    <Tooltip
                        content={ <RawHTML>{ item.product.title }</RawHTML> }
                    >
                        <a
                            href={ item.product.admin_url }
                            target="_blank"
                            rel="noreferrer"
                        >
                            <div className="font-medium text-[#575757] m-0 space-x-2 flex flex-wrap max-w-40 text-wrap">
                                <RawHTML>
                                    { truncate
                                        ? truncate( item.product.title, 22 )
                                        : item.product.title }
                                </RawHTML>
                            </div>
                        </a>
                    </Tooltip>
                );
            },
        },
        {
            id: 'vendor',
            label: __( 'Vendor', 'dokan' ),
            render: ( { item }: { item: AbuseReport } ) => {
                if ( loading ) {
                    return (
                        <span className="block w-16 h-3 rounded bg-gray-200 animate-pulse"></span>
                    );
                }
                if ( ! item.vendor ) {
                    return (
                        <div className="text-gray-500">
                            { __( 'N/A', 'dokan' ) }
                        </div>
                    );
                }
                return (
                    <Tooltip
                        content={ <RawHTML>{ item.vendor.name }</RawHTML> }
                    >
                        <a
                            href={ item.vendor.admin_url }
                            target="_blank"
                            rel="noreferrer"
                        >
                            <div className="font-medium text-[#575757] m-0 space-x-2 flex flex-wrap max-w-40 text-wrap">
                                <RawHTML>
                                    { truncate
                                        ? truncate( item.vendor.name, 22 )
                                        : item.vendor.name }
                                </RawHTML>
                            </div>
                        </a>
                    </Tooltip>
                );
            },
        },
        {
            id: 'reported_by',
            label: __( 'Reported By', 'dokan' ),
            render: ( { item }: { item: AbuseReport } ) => {
                if ( loading ) {
                    return (
                        <span className="block w-16 h-3 rounded bg-gray-200 animate-pulse"></span>
                    );
                }
                if ( ! item.reported_by ) {
                    return (
                        <div className="text-gray-500">
                            { __( 'Anonymous', 'dokan' ) }
                        </div>
                    );
                }
                return (
                    <Tooltip
                        content={
                            <RawHTML>
                                { sprintf(
                                    '%1$s(%2$s)',
                                    item.reported_by.name,
                                    item.reported_by.email
                                ) }
                            </RawHTML>
                        }
                    >
                        <a
                            href={ item.reported_by.admin_url }
                            target="_blank"
                            rel="noreferrer"
                        >
                            <div className="font-medium text-[#575757] m-0 space-x-2 flex flex-wrap max-w-40 text-wrap">
                                <RawHTML>
                                    { truncate
                                        ? truncate( item.reported_by.name, 22 )
                                        : item.reported_by.name }
                                </RawHTML>
                            </div>
                        </a>
                    </Tooltip>
                );
            },
        },
        {
            id: 'reported_at',
            label: __( 'Reported At', 'dokan' ),
            render: ( { item }: { item: AbuseReport } ) =>
                loading ? (
                    <span className="block w-16 h-3 rounded bg-gray-200 animate-pulse"></span>
                ) : (
                    <div className="text-[#575757]">
                        <DateTimeHtml date={ item.reported_at } />
                    </div>
                ),
        },
    ];

    // Set view state for handling the table view
    const [ view, setView ] = useState( {
        perPage: 20,
        page: 1,
        search: '',
        type: 'table',
        titleField: 'reason',
        fields: fields.map( ( field ) =>
            field.id !== 'reason' ? field.id : ''
        ),
        layout: {
            table: {},
            grid: {},
            list: {},
            density: 'comfortable',
        },
    } );

    // Define actions for table rows
    const actions = [
        {
            id: 'view',
            label: () => getActionLabel( <Eye size={ 16 } className="fill-none!" />, __( 'View', 'dokan' ) ),
            icon: <Eye size={ 16 } className="fill-none!" />,
            isPrimary: false,
            supportsBulk: false,
            callback: ( items: AbuseReport[] ) => {
                if ( items.length === 1 ) {
                    setModalItem( items[ 0 ] );
                    setModalOpen( true );
                }
            },
        },
        {
            id: 'delete',
            label: () => getActionLabel( <Trash size={ 16 } className="fill-none!" />, __( 'Delete', 'dokan' ) ),
            icon: <Trash size={ 16 } className="fill-none!" />,
            isPrimary: false,
            supportsBulk: true,
            isDestructive: true,
            confirmTitle: __( 'Delete Abuse Report', 'dokan' ),
            confirmMessage: __(
                'Are you sure you want to delete the selected abuse report(s)? This action cannot be undone.',
                'dokan'
            ),
            confirmButtonLabel: __( 'Delete', 'dokan' ),
            callback: async ( items: AbuseReport[] ) => {
                if ( items.length > 1 ) {
                    await handleBulkDelete( items );
                } else if ( items.length === 1 ) {
                    await handleSingleDelete( items[ 0 ].id );
                }
            },
        },
    ];

    // Load abuse reasons for AsyncSelect
    const loadAbuseReasons = async () => {
        try {
            const response = await apiFetch( {
                path: 'dokan/v1/abuse-reports/abuse-reasons',
            } );
            const data = response as AbuseReason[];
            return data.map( ( reason: AbuseReason ) => ( {
                value: reason.id,
                label: reason.value,
            } ) );
        } catch ( err ) {
            return [];
        }
    };

    // Fetch abuse reasons
    useEffect( () => {
        const fetchReasons = async () => {
            try {
                const response = await apiFetch( {
                    path: 'dokan/v1/abuse-reports/abuse-reasons',
                } );
                setReasons( response as AbuseReason[] );
            } catch ( err ) {
                setError( __( 'Error fetching abuse reasons', 'dokan' ) );
            }
        };

        fetchReasons();
    }, [] );

    // Fetch reports
    const fetchReports = async () => {
        setLoading( true );
        setReports( [] );
        try {
            const params = new URLSearchParams( {
                page: view.page?.toString() || '1',
                per_page: view.perPage?.toString() || '10',
                ...filterObject,
            } );

            const response = ( await apiFetch( {
                path: `/dokan/v1/abuse-reports?${ params.toString() }`,
                parse: false,
            } ) ) as Response;

            const data = await response.json();
            const total = response.headers.get( 'X-Dokan-AbuseReports-Total' );

            setReports( data );
            setTotalReports( total ? parseInt( total ) : 0 );
            setError( null );
        } catch ( err ) {
            setError( __( 'Error fetching abuse reports', 'dokan' ) );
        } finally {
            setLoading( false );
        }
    };
    // AdminFilter config
    const filters = {
        fields: [
            {
                id: 'reason',
                label: __( 'Reason', 'dokan-lite' ),
                field: (
                    <Select
                        icon={ <CircleHelp size={ 16 } /> }
                        key="reason-select"
                        cacheOptions
                        options={ reasons.map( ( reason ) => ( {
                            value: reason.id,
                            label: reason.value,
                        } ) ) }
                        value={ selectedReason }
                        onChange={ ( selectedOption ) => {
                            const filter = { ...filterObject };
                            if ( selectedOption ) {
                                filter.reason = selectedOption.label;
                            } else {
                                delete filter.reason;
                            }
                            setSelectedReason( selectedOption );
                            setFilterObject( filter );
                        } }
                        placeholder={ __( 'Select Reason', 'dokan' ) }
                    />
                ),
            },
            {
                id: 'product',
                label: __( 'Product', 'dokan-lite' ),
                field: (
                    <ProductAsyncSelect
                        icon={ <Box size={ 16 } /> }
                        key="product-select"
                        cacheOptions
                        defaultOptions
                        isClearable
                        value={ selectedProduct }
                        onChange={ ( selectedOption ) => {
                            const filter = { ...filterObject };
                            if ( selectedOption ) {
                                filter.product_id = selectedOption.value;
                            } else {
                                delete filter.product_id;
                            }
                            setSelectedProduct( selectedOption );
                            setFilterObject( filter );
                        } }
                        placeholder={ __( 'Select Product', 'dokan' ) }
                    />
                ),
            },
            {
                id: 'vendor',
                label: __( 'Vendor', 'dokan-lite' ),
                field: (
                    <VendorAsyncSelect
                        icon={ <House size={ 16 } /> }
                        key="vendor-select"
                        value={ selectedVendor }
                        onChange={ (
                            selectedVendorObj: null | {
                                value: string;
                                label: string;
                            }
                        ) => {
                            const filter = { ...filterObject };
                            if ( selectedVendorObj ) {
                                filter.vendor_id = selectedVendorObj.value;
                            } else {
                                delete filter.vendor_id;
                            }
                            setSelectedVendor( selectedVendorObj );
                            setFilterObject( filter );
                        } }
                        placeholder={ __( 'Select Vendor', 'dokan' ) }
                        isClearable
                        prefetch
                        defaultOptions
                        cacheOptions
                    />
                ),
            },
        ],
        onReset: () => {
            clearFilters();
        },
        onFilterRemove: ( id: string ) => {
            const args = { ...filterObject };
            switch ( id ) {
                case 'vendor':
                    setSelectedVendor( null );
                    delete args.vendor_id;
                    break;
                case 'product':
                    setSelectedProduct( null );
                    delete args.product_id;
                    break;
                case 'reason':
                    setSelectedReason( null );
                    delete args.reason;
                    break;
                default:
                    break;
            }
            setFilterObject( args );
            setView( ( prevView ) => ( { ...prevView, page: 1 } ) );
        },
    };

    useEffect( () => {
        fetchReports();
    }, [ view ] );

    // Handle bulk delete
    const handleBulkDelete = async ( items: AbuseReport[] ) => {
        if ( items.length === 0 ) {
            return;
        }

        try {
            await apiFetch( {
                path: '/dokan/v1/abuse-reports/batch',
                method: 'DELETE',
                data: {
                    items: items.map( ( item ) => item.id ),
                },
            } );

            // Refresh reports after deletion
            await fetchReports();
            setSelectedReports( [] );
            toast( {
                type: 'success',
                title: __(
                    'Selected reports have been deleted successfully.',
                    'dokan'
                ),
            } );
        } catch ( err ) {
            setError( __( 'Error deleting reports', 'dokan' ) );
            toast( {
                type: 'error',
                title: __(
                    'Error deleting reports. Please try again later.',
                    'dokan'
                ),
            } );
        } finally {
        }
    };

    // Handle single delete
    const handleSingleDelete = async ( reportId: number ) => {
        try {
            await apiFetch( {
                path: `/dokan/v1/abuse-reports/${ reportId }`,
                method: 'DELETE',
            } );

            toast( {
                type: 'success',
                title: __(
                    'Selected report have been deleted successfully.',
                    'dokan'
                ),
            } );
            // Refresh reports after deletion
            await fetchReports();
        } catch ( err ) {
            setError( __( 'Error deleting report', 'dokan' ) );
            toast( {
                type: 'error',
                title: __(
                    'Error deleting report. Please try again later.',
                    'dokan'
                ),
            } );
        } finally {
        }
    };

    useEffect( () => {
        setView( ( prevView ) => ( {
            ...prevView,
            page: 1,
        } ) );
    }, [ filterObject ] );

    // Clear filters
    const clearFilters = () => {
        setSelectedReason( null );
        setSelectedVendor( null );
        setSelectedProduct( null );
        setFilterObject( {} );
        setView( ( prevView ) => ( {
            ...prevView,
            page: 1,
        } ) );
    };

    return (
        <div className="dokan-report-abuse-page">
            <div className="flex items-center justify-between mb-6 pt-8">
                <h2 className="text-xl font-bold text-gray-900 leading-8">
                    { __( 'Abuse Reports', 'dokan' ) }
                </h2>
            </div>

            { error && (
                <Notice status="error" isDismissible={ false }>
                    { error }
                </Notice>
            ) }
            { /* Data Table */ }
            <div className="dokan-admin-dashboard-datatable">
                <DataViews
                    data={ reports }
                    namespace="dokan-admin-dashboard-abuse-reports-table"
                    defaultLayouts={ defaultLayouts }
                    fields={ fields }
                    getItemId={ ( item: AbuseReport ) => item.id }
                    onChangeView={ setView }
                    paginationInfo={ {
                        totalItems: totalReports,
                        totalPages: Math.ceil( totalReports / view.perPage ),
                    } }
                    view={ view }
                    selection={ selectedReports }
                    onChangeSelection={ setSelectedReports }
                    actions={ actions }
                    isLoading={ loading }
                    filter={ filters }
                    tabs={ {
                        items: [
                            {
                                value: 'all',
                                label: __( 'All', 'dokan' ),
                                count: totalReports,
                            },
                        ],
                        onSelect: () => setView( { ...view, page: 1 } ),
                        defaultValue: 'all',
                    } }
                />
            </div>

            <DokanModal
                className={ `max-w-full w-[600px]` }
                isOpen={ modalOpen }
                namespace={ `view-abuse-reports-${ modalItem?.id ?? 0 }` }
                onClose={ () => setModalOpen( false ) }
                onConfirm={ async () => {
                    setModalOpen( false );
                    setModalItem( null );
                } }
                dialogTitle={ __( 'Product Abuse Report', 'dokan' ) }
                dialogIcon={ <></> }
                dialogContent={
                    <div className="flex -m-4">
                        { modalItem && (
                            <div className="flex gap-0 w-full divide-x divide-gray-200">
                                <div className="grow space-y-5 p-6">
                                    { /* Reason Section */ }
                                    <div className="bg-gray-50 p-3 rounded border border-gray-200">
                                        <h4 className="text-sm font-medium text-gray-400 mb-2">
                                            { __( 'Reason', 'dokan' ) }
                                        </h4>
                                        <div className="text-sm font-medium text-gray-800">
                                            <RawHTML>
                                                { modalItem.reason }
                                            </RawHTML>
                                        </div>
                                    </div>

                                    { /* Description Section */ }
                                    { modalItem.description && (
                                        <div>
                                            <h4 className="text-sm font-medium text-gray-400 mb-2">
                                                { __( 'Description', 'dokan' ) }
                                            </h4>
                                            <div className="text-sm text-gray-900 leading-relaxed">
                                                <RawHTML>
                                                    { modalItem.description }
                                                </RawHTML>
                                            </div>
                                        </div>
                                    ) }
                                </div>
                                <div className="flex flex-col flex-1 w-48! min-w-48 max-w-48 basis-48 shrink-0 p-6 space-y-5">
                                    { /* Reported Product */ }
                                    <div className="flex flex-col items-start space-y-1">
                                        <div className="flex items-center justify-center">
                                            <svg
                                                width="20"
                                                height="20"
                                                viewBox="0 0 20 20"
                                                fill="none"
                                                xmlns="http://www.w3.org/2000/svg"
                                            >
                                                <path
                                                    d="M2.75 5.83293L10 9.99959M10 9.99959L17.25 5.83293M10 9.99959V18.3329M17.5 6.66626C17.4997 6.37399 17.4225 6.08693 17.2763 5.8339C17.13 5.58086 16.9198 5.37073 16.6667 5.22459L10.8333 1.89126C10.58 1.74498 10.2926 1.66797 10 1.66797C9.70744 1.66797 9.42003 1.74498 9.16667 1.89126L3.33333 5.22459C3.08022 5.37073 2.86998 5.58086 2.72372 5.8339C2.57745 6.08693 2.5003 6.37399 2.5 6.66626V13.3329C2.5003 13.6252 2.57745 13.9123 2.72372 14.1653C2.86998 14.4183 3.08022 14.6285 3.33333 14.7746L9.16667 18.1079C9.42003 18.2542 9.70744 18.3312 10 18.3312C10.2926 18.3312 10.58 18.2542 10.8333 18.1079L16.6667 14.7746C16.9198 14.6285 17.13 14.4183 17.2763 14.1653C17.4225 13.9123 17.4997 13.6252 17.5 13.3329V6.66626Z"
                                                    stroke="#828282"
                                                    strokeWidth="2"
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                />
                                            </svg>
                                        </div>
                                        <div className="flex-1">
                                            <h4 className="text-sm text-gray-400">
                                                { __(
                                                    'Reported Product',
                                                    'dokan'
                                                ) }
                                            </h4>
                                            { modalItem.product ? (
                                                <a
                                                    href={
                                                        modalItem.product
                                                            .admin_url
                                                    }
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="text-sm text-gray-600! wrap-break-word"
                                                >
                                                    <RawHTML>
                                                        {
                                                            modalItem.product
                                                                .title
                                                        }
                                                    </RawHTML>
                                                </a>
                                            ) : (
                                                <span className="text-sm text-gray-500">
                                                    { __( 'N/A', 'dokan' ) }
                                                </span>
                                            ) }
                                        </div>
                                    </div>

                                    { /* Reported By */ }
                                    <div className="flex flex-col items-start space-y-1">
                                        <div className="flex items-center justify-center">
                                            <svg
                                                width="20"
                                                height="20"
                                                viewBox="0 0 20 20"
                                                fill="none"
                                                xmlns="http://www.w3.org/2000/svg"
                                            >
                                                <path
                                                    d="M15.8337 17.5V15.8333C15.8337 14.9493 15.4825 14.1014 14.8573 13.4763C14.2322 12.8512 13.3844 12.5 12.5003 12.5H7.50033C6.61627 12.5 5.76842 12.8512 5.1433 13.4763C4.51818 14.1014 4.16699 14.9493 4.16699 15.8333V17.5M13.3337 5.83333C13.3337 7.67428 11.8413 9.16667 10.0003 9.16667C8.15938 9.16667 6.66699 7.67428 6.66699 5.83333C6.66699 3.99238 8.15938 2.5 10.0003 2.5C11.8413 2.5 13.3337 3.99238 13.3337 5.83333Z"
                                                    stroke="#828282"
                                                    strokeWidth="2"
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                />
                                            </svg>
                                        </div>
                                        <div className="flex-1">
                                            <h4 className="text-sm font-medium text-gray-400">
                                                { __( 'Reported By', 'dokan' ) }
                                            </h4>
                                            { modalItem.reported_by ? (
                                                <div>
                                                    <a
                                                        href={
                                                            modalItem
                                                                .reported_by
                                                                .admin_url
                                                        }
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="text-sm text-gray-600! hover:text-gray-800!"
                                                    >
                                                        <RawHTML>
                                                            {
                                                                modalItem
                                                                    .reported_by
                                                                    .name
                                                            }
                                                        </RawHTML>
                                                    </a>
                                                    <div className="text-xs text-gray-500">
                                                        {
                                                            modalItem
                                                                .reported_by
                                                                .email
                                                        }
                                                    </div>
                                                </div>
                                            ) : (
                                                <span className="text-sm text-gray-500">
                                                    { __(
                                                        'Anonymous',
                                                        'dokan'
                                                    ) }
                                                </span>
                                            ) }
                                        </div>
                                    </div>

                                    { /* Reported At */ }
                                    <div className="flex flex-col items-start space-y-1">
                                        <div className="flex items-center justify-center">
                                            <svg
                                                width="20"
                                                height="20"
                                                viewBox="0 0 20 20"
                                                fill="none"
                                                xmlns="http://www.w3.org/2000/svg"
                                            >
                                                <path
                                                    d="M6.66667 1.66797V5.0013M13.3333 1.66797V5.0013M2.5 8.33464H17.5M4.16667 3.33464H15.8333C16.7538 3.33464 17.5 4.08083 17.5 5.0013V16.668C17.5 17.5884 16.7538 18.3346 15.8333 18.3346H4.16667C3.24619 18.3346 2.5 17.5884 2.5 16.668V5.0013C2.5 4.08083 3.24619 3.33464 4.16667 3.33464Z"
                                                    stroke="#828282"
                                                    strokeWidth="2"
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                />
                                            </svg>
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <h4 className="text-sm font-medium text-gray-400">
                                                { __( 'Reported at', 'dokan' ) }
                                            </h4>
                                            <div className="text-sm text-gray-700">
                                                <DateTimeHtml
                                                    date={
                                                        modalItem.reported_at
                                                    }
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    { /* Product Vendor */ }
                                    <div className="flex flex-col items-start space-y-1">
                                        <div className="flex items-center justify-center">
                                            <svg
                                                width="20"
                                                height="20"
                                                viewBox="0 0 20 20"
                                                fill="none"
                                                xmlns="http://www.w3.org/2000/svg"
                                            >
                                                <path
                                                    d="M12.5 17.5009V10.8342C12.5 10.6132 12.4122 10.4012 12.2559 10.2449C12.0996 10.0887 11.8877 10.0009 11.6667 10.0009H8.33333C8.11232 10.0009 7.90036 10.0887 7.74408 10.2449C7.5878 10.4012 7.5 10.6132 7.5 10.8342V17.5009M2.5 8.3342C2.49994 8.09176 2.55278 7.85222 2.65482 7.6323C2.75687 7.41238 2.90566 7.21737 3.09083 7.06087L8.92417 2.0617C9.22499 1.80746 9.60613 1.66797 10 1.66797C10.3939 1.66797 10.775 1.80746 11.0758 2.0617L16.9092 7.06087C17.0943 7.21737 17.2431 7.41238 17.3452 7.6323C17.4472 7.85222 17.5001 8.09176 17.5 8.3342V15.8342C17.5 16.2762 17.3244 16.7002 17.0118 17.0127C16.6993 17.3253 16.2754 17.5009 15.8333 17.5009H4.16667C3.72464 17.5009 3.30072 17.3253 2.98816 17.0127C2.67559 16.7002 2.5 16.2762 2.5 15.8342V8.3342Z"
                                                    stroke="#828282"
                                                    strokeWidth="2"
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                />
                                            </svg>
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <h4 className="text-sm font-medium text-gray-400">
                                                { __(
                                                    'Product Vendor',
                                                    'dokan'
                                                ) }
                                            </h4>
                                            { modalItem.vendor ? (
                                                <a
                                                    href={
                                                        modalItem.vendor
                                                            .admin_url
                                                    }
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="text-sm text-gray-600! hover:text-gray-800! wrap-break-word break-all"
                                                >
                                                    <RawHTML>
                                                        {
                                                            modalItem.vendor
                                                                .name
                                                        }
                                                    </RawHTML>
                                                </a>
                                            ) : (
                                                <span className="text-sm text-gray-500">
                                                    { __( 'N/A', 'dokan' ) }
                                                </span>
                                            ) }
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ) }
                    </div>
                }
                hideCancelButton={ true }
                confirmButtonText={ __( 'Close', 'dokan' ) }
            />

            <DokanToaster />
        </div>
    );
};

export default ReportAbusePage;
