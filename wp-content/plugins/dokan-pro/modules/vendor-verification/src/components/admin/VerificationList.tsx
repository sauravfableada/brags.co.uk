import { useState, useEffect, useCallback, RawHTML } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import {
    Check,
    XCircle,
    Home,
    CreditCard,
    RotateCw,
    MessageSquare,
} from 'lucide-react';
import {
    DataViews,
    DokanModal,
    DateTimeHtml,
    VendorAsyncSelect,
    AsyncSelect,
    DokanTooltip as Tooltip,
    getActionLabel,
} from '@dokan/components';
import { truncate } from '@dokan/utilities';
import { TextArea } from '@getdokan/dokan-ui';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

interface VerificationRequest {
    id: number;
    vendor_id: number;
    method_id: number;
    status: string;
    status_title: string;
    note: string;
    additional_info: any;
    documents: number[];
    checked_by: number;
    created_at: string;
    updated_at: string;
    vendor?: {
        store_name: string;
        [ key: string ]: any;
    };
    method?: {
        id: number;
        title: string;
    };
    document_urls?: {
        [ key: string ]: {
            url: string;
            title: string;
        };
    };
}

interface VerificationListProps {
    currentStatus?: string;
    onStatusChange?: ( status: string ) => void;
}

const VerificationList = ( {
    currentStatus = 'pending',
    onStatusChange,
}: VerificationListProps ) => {
    const [ data, setData ] = useState< VerificationRequest[] >( [] );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ error, setError ] = useState< string | null >( null );
    const [ totalItems, setTotalItems ] = useState( 0 );
    const [ statusCounts, setStatusCounts ] = useState( {
        pending: 0,
        approved: 0,
        rejected: 0,
        cancelled: 0,
        total: 0,
    } );
    const [ filterArgs, setFilterArgs ] = useState( {} );
    const [ activeStatus, setActiveStatus ] = useState( currentStatus );
    const [ vendorFilter, setVendorFilter ] = useState( null );
    const [ methodFilter, setMethodFilter ] = useState( null );

    // Modal state management
    const [ modalState, setModalState ] = useState( {
        isOpen: false,
        type: '',
        items: [],
    } );

    // Note state for add-note modal
    const [ noteState, setNoteState ] = useState( '' );
    const [ localNoteState, setLocalNoteState ] = useState( '' );

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
        search: '',
        type: 'table',
        titleField: 'id',
        status: activeStatus,
        layout: { ...defaultLayouts },
        fields: [
            'method',
            'documents',
            'status',
            'vendor',
            'note',
            'created_at',
        ],
    } );

    // Optimized handlers to prevent re-renders
    const handleNoteChange = useCallback( ( event ) => {
        setLocalNoteState( event.target.value );
    }, [] );

    const handleNoteBlur = useCallback( () => {
        setNoteState( localNoteState );
    }, [ localNoteState ] );

    // Modal helper functions
    const openModal = ( type, items ) => {
        setModalState( {
            isOpen: true,
            type,
            items,
        } );

        // Initialize note state for add-note modal
        if ( type === 'add-note' && items.length > 0 ) {
            const initialNote = items[ 0 ].note || '';
            setNoteState( initialNote );
            setLocalNoteState( initialNote );
        }
    };

    const closeModal = () => {
        setModalState( {
            isOpen: false,
            type: '',
            items: [],
        } );
        setNoteState( '' );
        setLocalNoteState( '' );
    };

    // Handle data fetching from the server
    const fetchVerifications = async () => {
        setIsLoading( true );
        setError( null );

        try {
            const queryArgs = {
                per_page: view?.perPage ?? 20,
                page: view?.page ?? 1,
                search: view?.search ?? '',
                status: view?.status,
                ...filterArgs,
            };

            // Handle sorting
            if ( view?.sort?.field ) {
                queryArgs.orderby = view.sort.field;
            }
            if ( view?.sort?.direction ) {
                queryArgs.order = view.sort.direction;
            }

            // Handle filters
            if ( view?.filters ) {
                view.filters.forEach( ( filter ) => {
                    if (
                        filter.field === 'status' &&
                        filter.operator === 'isAny'
                    ) {
                        queryArgs.status = filter.value?.join( ',' );
                    }
                } );
            }

            // Fetch data from the REST API
            const response = await apiFetch( {
                path: addQueryArgs(
                    '/dokan/v1/verification-requests',
                    queryArgs
                ),
                parse: false, // Get raw response to access headers
            } );

            const responseData = await response.json();
            const totalItemsFromHeader = parseInt(
                response.headers.get( 'X-WP-Total' ) || '0'
            );

            // Extract status counts from response headers
            const pendingCount = parseInt(
                response.headers.get( 'X-Status-Pending' ) || '0'
            );
            const approvedCount = parseInt(
                response.headers.get( 'X-Status-Approved' ) || '0'
            );
            const rejectedCount = parseInt(
                response.headers.get( 'X-Status-Rejected' ) || '0'
            );
            const cancelledCount = parseInt(
                response.headers.get( 'X-Status-Cancelled' ) || '0'
            );

            const counts = {
                pending: pendingCount,
                approved: approvedCount,
                rejected: rejectedCount,
                cancelled: cancelledCount,
                total: totalItemsFromHeader,
            };

            setStatusCounts( { ...counts } );
            setTotalItems( counts?.[ activeStatus ] || 0 );
            setData( responseData );
        } catch ( err ) {
            setError(
                err instanceof Error
                    ? err.message
                    : __( 'Failed to fetch verifications', 'dokan' )
            );
        } finally {
            setIsLoading( false );
        }
    };

    // Handle bulk actions
    const handleBulkAction = async ( action: string, ids: number[] ) => {
        try {
            if ( ! ids?.length ) {
                return;
            }

            const update = ids.map( ( id ) => ( {
                id,
                status: action,
            } ) );

            await apiFetch( {
                path: `/dokan/v1/verification-requests/batch`,
                method: 'PATCH',
                body: JSON.stringify( { update } ),
                headers: {
                    'Content-Type': 'application/json',
                },
            } );

            fetchVerifications(); // Refresh data
            setSelection( [] ); // Clear selection
        } catch ( error ) {
            setError(
                error instanceof Error
                    ? error.message
                    : __( 'Failed to perform bulk action', 'dokan' )
            );
        }
    };

    // Handle update note
    const handleUpdateNote = async ( id, note ) => {
        try {
            await apiFetch( {
                path: `/dokan/v1/verification-requests/${ id }`,
                method: 'PATCH',
                body: JSON.stringify( { note } ),
                headers: {
                    'Content-Type': 'application/json',
                },
            } );
            fetchVerifications(); // Refresh data
        } catch ( error ) {
            setError(
                error instanceof Error
                    ? error.message
                    : __( 'Failed to update note', 'dokan' )
            );
        }
    };

    // Clear filters
    const clearFilter = () => {
        setVendorFilter( null );
        setMethodFilter( null );
        setFilterArgs( {} );

        setView( ( prevView ) => ( {
            ...prevView,
            page: 1, // Reset to first page when applying filters
        } ) );
    };

    const loadVerificationMethods = async ( inputValue ) => {
        try {
            const response = await apiFetch( {
                path: addQueryArgs( '/dokan/v1/verification-methods', {
                    search: inputValue,
                    per_page: 20,
                } ),
            } );

            if ( Array.isArray( response ) ) {
                return response.map( ( method ) => ( {
                    value: method.id,
                    label: method.title,
                    raw: method,
                } ) );
            }

            return [];
        } catch ( error ) {
            // Silently handle error and return empty array
            return [];
        }
    };

    // Handle tab selection for status filtering
    const handleTabSelect = ( tabName ) => {
        setActiveStatus( tabName );
        onStatusChange?.( tabName );
        setView( ( prevView ) => ( {
            ...prevView,
            status: tabName,
            page: 1, // Reset to first page when changing status
        } ) );
    };

    // Fetch verifications when view and/or filters change
    useEffect( () => {
        fetchVerifications();
    }, [ view, filterArgs ] );

    // Clear single filter handler
    const clearSingleFilter = ( filterId: string ) => {
        const args = { ...filterArgs };
        switch ( filterId ) {
            case 'vendor':
                setVendorFilter( null );
                delete args.vendor_id;
                break;
            case 'method':
                setMethodFilter( null );
                delete args.method_id;
                break;
            default:
                break;
        }
        setFilterArgs( args );
        setView( ( prevView ) => ( { ...prevView, page: 1 } ) );
    };

    // Define fields for the table columns
    const fields = [
        {
            id: 'id',
            label: __( 'Request ID', 'dokan' ),
            enableSorting: false,
            render: ( { item } ) => (
                <div className="font-medium text-[#575757]">
                    { isLoading ? (
                        <span className="block w-16 h-3 rounded bg-gray-200 animate-pulse"></span>
                    ) : (
                        // translators: %d: verification request ID
                        sprintf( __( '#%d', 'dokan' ), item?.id )
                    ) }
                </div>
            ),
        },
        {
            id: 'method',
            label: __( 'Method', 'dokan' ),
            enableSorting: false,
            render: ( { item } ) => (
                <div className="m-0 space-x-2 flex flex-wrap max-w-44 w-fit text-wrap leading-6 text-sm text-[#575757]">
                    { isLoading ? (
                        <span className="block w-24 h-3 rounded bg-gray-200 animate-pulse"></span>
                    ) : item?.method?.title?.length <= 22 ? (
                        <p className="flex flex-wrap text-wrap">
                            <RawHTML>{ item?.method?.title }</RawHTML>
                        </p>
                    ) : (
                        <Tooltip
                            content={
                                <RawHTML>{ item?.method?.title }</RawHTML>
                            }
                        >
                            <p className="flex flex-wrap text-wrap">
                                <RawHTML>
                                    { truncate
                                        ? truncate( item?.method?.title, 22 )
                                        : __( '-', 'dokan' ) }
                                </RawHTML>
                            </p>
                        </Tooltip>
                    ) }
                </div>
            ),
        },
        {
            id: 'documents',
            label: __( 'Documents', 'dokan' ),
            enableSorting: false,
            render: ( { item } ) => {
                if ( isLoading ) {
                    return (
                        <span className="block w-32 h-3 rounded bg-gray-200 animate-pulse"></span>
                    );
                }

                const docs = item?.document_urls
                    ? Object.values( item.document_urls )
                    : [];

                if ( ! docs.length ) {
                    return __( 'No documents found', 'dokan' );
                }

                return (
                    <div className="m-0 space-y-1 flex flex-col max-w-44 w-fit text-wrap leading-6 text-sm text-[#575757]">
                        { docs.map( ( doc, idx ) => (
                            <a
                                key={ idx }
                                href={ doc.url }
                                target="_blank"
                                rel="noreferrer"
                                className="text-blue-600 hover:underline"
                                title={ doc.title }
                            >
                                { doc?.title?.length <= 22 ? (
                                    <RawHTML>{ doc?.title }</RawHTML>
                                ) : (
                                    <Tooltip
                                        content={
                                            <RawHTML>{ doc?.title }</RawHTML>
                                        }
                                    >
                                        <span className="m-0 space-x-2 flex flex-wrap text-wrap leading-6">
                                            <RawHTML>
                                                { truncate
                                                    ? truncate( doc?.title, 22 )
                                                    : doc?.title }
                                            </RawHTML>
                                        </span>
                                    </Tooltip>
                                ) }
                            </a>
                        ) ) }
                    </div>
                );
            },
        },
        {
            id: 'status',
            label: __( 'Status', 'dokan' ),
            enableSorting: false,
            render: ( { item } ) => {
                if ( isLoading ) {
                    return (
                        <span className="block w-20 h-3 rounded bg-gray-200 animate-pulse"></span>
                    );
                }

                const statusColors = {
                    pending: 'bg-pink-50 text-pink-800',
                    approved: 'bg-green-100 text-green-800',
                    rejected: 'bg-red-50 text-red-800',
                    cancelled: 'bg-gray-100 text-gray-800',
                };

                return (
                    <div className="w-fit max-w-28">
                        <span
                            className={ `inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-medium ${
                                statusColors[ item?.status ] ||
                                'bg-gray-100 text-gray-800'
                            }` }
                        >
                            { item?.status_title || item?.status }
                        </span>
                    </div>
                );
            },
        },
        {
            id: 'vendor',
            label: __( 'Vendor', 'dokan' ),
            enableSorting: false,
            render: ( { item } ) => (
                <div className="text-sm text-[#575757] text-wrap flex flex-wrap w-fit max-w-44">
                    { isLoading ? (
                        <span className="block w-24 h-3 rounded bg-gray-200 animate-pulse"></span>
                    ) : item?.vendor?.store_name?.length <= 22 ? (
                        <p className="flex flex-wrap text-wrap">
                            <RawHTML>{ item?.vendor?.store_name }</RawHTML>
                        </p>
                    ) : (
                        <Tooltip
                            content={
                                <RawHTML>{ item?.vendor?.store_name }</RawHTML>
                            }
                        >
                            <p className="flex flex-wrap text-wrap">
                                <RawHTML>
                                    { truncate
                                        ? truncate(
                                              item?.vendor?.store_name,
                                              22
                                          )
                                        : __( '-', 'dokan' ) }
                                </RawHTML>
                            </p>
                        </Tooltip>
                    ) }
                </div>
            ),
        },
        {
            id: 'note',
            label: __( 'Note', 'dokan' ),
            enableSorting: false,
            render: ( { item } ) => {
                if ( isLoading ) {
                    return (
                        <span className="block w-32 h-3 rounded bg-gray-200 animate-pulse"></span>
                    );
                }

                if ( ! item?.note ) {
                    return __( '-', 'dokan' );
                }

                return (
                    <p className="m-0 space-x-2 flex flex-wrap max-w-44 w-fit text-wrap leading-6 text-sm text-[#575757]">
                        { item?.note?.length <= 22 ? (
                            <RawHTML>{ item?.note }</RawHTML>
                        ) : (
                            <Tooltip
                                content={
                                    <RawHTML>
                                        { item?.note ||
                                            __( 'No note found', 'dokan' ) }
                                    </RawHTML>
                                }
                            >
                                <span>
                                    <RawHTML>
                                        { truncate
                                            ? truncate( item?.note, 22 )
                                            : __( '-', 'dokan' ) }
                                    </RawHTML>
                                </span>
                            </Tooltip>
                        ) }
                    </p>
                );
            },
        },
        {
            id: 'created_at',
            label: __( 'Date', 'dokan' ),
            enableSorting: false,
            render: ( { item } ) => (
                <div className="text-[#575757]">
                    { isLoading ? (
                        <span className="block w-24 h-3 rounded bg-gray-200 animate-pulse"></span>
                    ) : (
                        <DateTimeHtml.Date date={ item?.created_at } />
                    ) }
                </div>
            ),
        },
    ];

    const actions = [
        {
            id: 'approved',
            label: () => getActionLabel( <Check size={ 16 } className="fill-none!" />, __( 'Approve', 'dokan' ) ),
            icon: <Check size={ 16 } className="fill-none!" />,
            isPrimary: false,
            supportsBulk: true,
            isDestructive: true,
            confirmTone: 'positive',
            confirmTitle: __( 'Approve Verification', 'dokan' ),
            confirmMessage: __(
                'Are you sure you want to approve the selected verification request(s)?',
                'dokan'
            ),
            confirmButtonLabel: __( 'Approve', 'dokan' ),
            isEligible: ( item ) => item?.status === 'pending',
            callback: async ( items: any[] ) => {
                await handleBulkAction(
                    'approved',
                    items.map( ( item ) => item.id )
                );
            },
        },
        {
            id: 'pending',
            label: () => getActionLabel( <RotateCw size={ 16 } className="fill-none!" />, __( 'Pending', 'dokan' ) ),
            icon: <RotateCw size={ 16 } className="fill-none!" />,
            isPrimary: false,
            supportsBulk: true,
            isDestructive: true,
            confirmTone: 'default',
            confirmTitle: __( 'Mark as Pending', 'dokan' ),
            confirmMessage: __(
                'Are you sure you want to mark the selected verification request(s) as pending?',
                'dokan'
            ),
            confirmButtonLabel: __( 'Mark as Pending', 'dokan' ),
            isEligible: ( item ) =>
                [ 'approved', 'rejected', 'cancelled' ].includes(
                    item?.status
                ),
            callback: async ( items: any[] ) => {
                await handleBulkAction(
                    'pending',
                    items.map( ( item ) => item.id )
                );
            },
        },
        {
            id: 'rejected',
            label: () => getActionLabel( <XCircle size={ 16 } className="fill-none!" />, __( 'Reject', 'dokan' ) ),
            icon: <XCircle size={ 16 } className="fill-none!" />,
            isPrimary: false,
            supportsBulk: true,
            isDestructive: true,
            confirmTitle: __( 'Reject Verification', 'dokan' ),
            confirmMessage: __(
                'Are you sure you want to reject the selected verification request(s)?',
                'dokan'
            ),
            confirmButtonLabel: __( 'Reject', 'dokan' ),
            isEligible: ( item ) =>
                [ 'pending', 'approved', 'cancelled' ].includes( item?.status ),
            callback: async ( items: any[] ) => {
                await handleBulkAction(
                    'rejected',
                    items.map( ( item ) => item.id )
                );
            },
        },
        {
            id: 'add-note',
            label: ( items ) => {
                const hasNote =
                    items?.[ 0 ]?.note && items?.[ 0 ]?.note.trim() !== '';
                return getActionLabel(
                    <MessageSquare size={ 16 } className="fill-none!" />,
                    hasNote
                        ? __( 'Edit Note', 'dokan' )
                        : __( 'Add Note', 'dokan' )
                );
            },
            icon: <MessageSquare size={ 16 } className="fill-none!" />,
            isPrimary: false,
            supportsBulk: false,
            callback: ( items ) => {
                openModal( 'add-note', items );
            },
        },
    ];

    // Status filter tabs
    const statusTabs = [
        {
            key: 'pending',
            label: __( 'Pending', 'dokan' ),
            count: statusCounts.pending,
        },
        {
            key: 'approved',
            label: __( 'Approved', 'dokan' ),
            count: statusCounts.approved,
        },
        {
            key: 'rejected',
            label: __( 'Rejected', 'dokan' ),
            count: statusCounts.rejected,
        },
        {
            key: 'cancelled',
            label: __( 'Cancelled', 'dokan' ),
            count: statusCounts.cancelled,
        },
    ];

    const tabItems = statusTabs.map( ( tab ) => ( {
        value: tab.key,
        label: tab.label,
        count: tab.count || 0,
    } ) );

    // Filter fields configuration
    const filterFields = [
        {
            id: 'vendor',
            label: __( 'Vendor', 'dokan' ),
            field: (
                <VendorAsyncSelect
                    icon={ <Home size={ 16 } /> }
                    key="vendor-select"
                    value={ vendorFilter }
                    onChange={ (
                        selectedVendorObj: null | {
                            value: string;
                            label: string;
                        }
                    ) => {
                        const args = { ...filterArgs };

                        delete args.vendor_id;

                        if ( selectedVendorObj ) {
                            args.vendor_id = selectedVendorObj.value;
                        }
                        setFilterArgs( args );
                        setVendorFilter( selectedVendorObj );
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
            id: 'method',
            label: __( 'Verification Method', 'dokan' ),
            field: (
                <AsyncSelect
                    key="method-select"
                    icon={ <CreditCard size={ 16 } /> }
                    loadOptions={ loadVerificationMethods }
                    cacheOptions
                    defaultOptions
                    isClearable
                    value={ methodFilter }
                    onChange={ ( method ) => {
                        const args = { ...filterArgs };
                        delete args.method_id;

                        if ( method ) {
                            args.method_id = method.value;
                        }
                        setMethodFilter( method );
                        setFilterArgs( args );
                    } }
                    placeholder={ __( 'Verification Method', 'dokan' ) }
                />
            ),
        },
    ];

    return (
        <div className="verification-admin-page">
            <h2 className="text-2xl leading-3 text-gray-900 font-bold mb-6">
                { __( 'Verification', 'dokan' ) }
            </h2>

            { /* Error Message */ }
            { error && (
                <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                    { error }
                </div>
            ) }

            { /* Data Table */ }
            <div className="dokan-admin-dashboard-datatable">
                <DataViews
                    data={ data }
                    namespace="verification-data-view"
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
                        onSelect: ( status ) => {
                            setSelection( [] );
                            handleTabSelect( status );
                        },
                        defaultValue: activeStatus,
                    } }
                    filter={ {
                        fields: filterFields,
                        onFilterRemove: ( filterId ) =>
                            clearSingleFilter( filterId ),
                        onReset: () => clearFilter(),
                    } }
                    responsive
                />
            </div>

            { /* DokanModal for add-note action */ }
            { modalState.isOpen &&
                modalState.type === 'add-note' &&
                modalState.items.length > 0 && (
                    <DokanModal
                        className={ `w-96 max-w-full` }
                        isOpen={ modalState.isOpen }
                        namespace={ `add-note-verification-${ modalState.items[ 0 ]?.id }` }
                        onClose={ closeModal }
                        onConfirm={ async () => {
                            await handleUpdateNote(
                                modalState.items[ 0 ]?.id,
                                noteState
                            );
                            closeModal();
                        } }
                        dialogTitle={
                            modalState.items[ 0 ]?.note
                                ? __(
                                      'Edit note for this verification',
                                      'dokan'
                                  )
                                : __(
                                      'Add note for this verification',
                                      'dokan'
                                  )
                        }
                        confirmButtonText={
                            modalState.items[ 0 ]?.note
                                ? __( 'Update Note', 'dokan' )
                                : __( 'Add Note', 'dokan' )
                        }
                        dialogIcon={ <></> }
                        dialogContent={
                            <div className="sm:text-left flex-1">
                                <div className="mt-2">
                                    <TextArea
                                        disabled={ isLoading }
                                        className="min-h-48"
                                        input={ {
                                            id: 'dokan-verification-note-modal',
                                            defaultValue: localNoteState,
                                            onChange: handleNoteChange,
                                            onBlur: handleNoteBlur,
                                            placeholder: __(
                                                'Write here',
                                                'dokan'
                                            ),
                                        } }
                                    />
                                </div>
                            </div>
                        }
                    />
                ) }
        </div>
    );
};

export default VerificationList;
