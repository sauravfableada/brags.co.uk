import {
    RawHTML,
    useCallback,
    useEffect,
    useMemo,
    useState,
} from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { twMerge } from 'tailwind-merge';
import { dateI18n, getSettings } from '@wordpress/date';

import {
    DataViews,
    VendorAsyncSelect,
    AsyncSelect,
    DateRangePicker,
    SearchInput,
    DokanTooltip as Tooltip,
    getActionLabel,
} from '@dokan/components';
import { truncate } from '@dokan/utilities';
import { useToast, DokanToaster, SimpleInput } from '@getdokan/dokan-ui';
import {
    Home,
    SquareUserRound,
    Calendar,
    RotateCcw,
    Check,
    BookOpenCheck,
    BookOpenText,
} from 'lucide-react';

type Ticket = {
    ID: number;
    post_title: string;
    post_status: 'open' | 'closed' | string;
    vendor_name?: string;
    customer_name?: string;
    ticket_date?: string;
    order_id?: string | number;
    vendor_id?: string | number;
    reading?: 'yes' | 'no' | string;
    store_url?: string;
};

type VendorOption = { label: string; value: string | number; raw?: any } | null;

const defaultLayouts = {
    table: {},
    grid: {},
    list: {},
};
const TICKET_STATUSES = [
    { value: 'open', label: __( 'Open', 'dokan' ) },
    { value: 'closed', label: __( 'Closed', 'dokan' ) },
    { value: 'all', label: __( 'All', 'dokan' ) },
];

const StoreSupportList = ( {
    navigate,
}: {
    navigate?: ( url: string ) => any;
} ) => {
    const [ activeTab, setActiveTab ] = useState< 'all' | 'open' | 'closed' >(
        'all'
    );
    const [ vendor, setVendor ] = useState< VendorOption >( null );
    const [ customer, setCustomer ] = useState< VendorOption >( null );
    const [ order, setOrder ] = useState< string >( '' );
    const [ after, setAfter ] = useState< string >( '' );
    const [ afterText, setAfterText ] = useState< string >( '' );
    const [ before, setBefore ] = useState< string >( '' );
    const [ beforeText, setBeforeText ] = useState< string >( '' );
    const [ focusedInput, setFocusedInput ] = useState< string >( 'startDate' );
    const [ tickets, setTickets ] = useState< Ticket[] >( [] );
    const [ isLoading, setIsLoading ] = useState< boolean >( false );
    const [ totalItems, setTotalItems ] = useState< number >( 0 );
    const [ selection, setSelection ] = useState( [] );
    const [ tabCounts, setTabCounts ] = useState< {
        all: number;
        open: number;
        closed: number;
    } >( { all: 0, open: 0, closed: 0 } );

    const toast = useToast();

    // Columns for DataViews
    const fields = useMemo(
        () => [
            {
                id: 'id',
                label: __( 'Ticket ID', 'dokan' ),
                render: ( { item }: { item: Ticket } ) =>
                    isLoading ? (
                        <span className="block w-16 h-3 rounded bg-gray-200 animate-pulse"></span>
                    ) : (
                        <Tooltip content={ item.ID }>
                            <div
                                className={ twMerge(
                                    'font-medium text-[#7047EB] m-0 space-x-2 flex flex-wrap max-w-64 text-wrap cursor-pointer',
                                    item.reading === 'no' ? 'font-bold' : ''
                                ) }
                                onClick={ () => {
                                    navigate(
                                        '/admin-store-support/' +
                                            item.ID +
                                            '/' +
                                            item.vendor_id
                                    );
                                } }
                            >
                                #
                                { truncate
                                    ? truncate( item.ID.toString(), 22 )
                                    : item.ID }
                            </div>
                        </Tooltip>
                    ),
                enableHiding: false,
            },
            {
                id: 'post_title',
                label: __( 'Title', 'dokan' ),
                render: ( { item }: { item: Ticket } ) =>
                    isLoading ? (
                        <span className="block w-16 h-3 rounded bg-gray-200 animate-pulse"></span>
                    ) : (
                        <Tooltip
                            content={ <RawHTML>{ item.post_title }</RawHTML> }
                        >
                            <div
                                className={ twMerge(
                                    'font-medium text-[#575757] m-0 space-x-2 flex flex-wrap max-w-64 text-wrap cursor-pointer',
                                    item.reading === 'no'
                                        ? 'font-bold text-black'
                                        : ''
                                ) }
                                onClick={ () => {} }
                            >
                                <RawHTML>
                                    { truncate
                                        ? truncate( item.post_title, 22 )
                                        : item.post_title }
                                </RawHTML>
                            </div>
                        </Tooltip>
                    ),
            },
            {
                id: 'vendor_name',
                label: __( 'Vendor', 'dokan' ),
                render: ( { item }: { item: Ticket } ) =>
                    isLoading ? (
                        <span className="block w-16 h-3 rounded bg-gray-200 animate-pulse"></span>
                    ) : (
                        <Tooltip
                            content={ <RawHTML>{ item.vendor_name }</RawHTML> }
                        >
                            <div
                                className="font-medium text-[#575757] m-0 space-x-2 flex flex-wrap max-w-64 text-wrap cursor-pointer"
                                onClick={ () => {} }
                            >
                                <RawHTML>
                                    { truncate
                                        ? truncate( item.vendor_name, 22 )
                                        : item.vendor_name }
                                </RawHTML>
                            </div>
                        </Tooltip>
                    ),
            },
            {
                id: 'customer_name',
                label: __( 'Customer', 'dokan' ),
                render: ( { item }: { item: Ticket } ) =>
                    isLoading ? (
                        <span className="block w-16 h-3 rounded bg-gray-200 animate-pulse"></span>
                    ) : (
                        <Tooltip
                            content={
                                <RawHTML>{ item.customer_name }</RawHTML>
                            }
                        >
                            <div
                                className="font-medium text-[#575757] m-0 space-x-2 flex flex-wrap max-w-64 text-wrap cursor-pointer"
                                onClick={ () => {} }
                            >
                                <RawHTML>
                                    { truncate
                                        ? truncate( item.customer_name, 22 )
                                        : item.customer_name }
                                </RawHTML>
                            </div>
                        </Tooltip>
                    ),
            },
            {
                id: 'post_status',
                label: __( 'Status', 'dokan' ),
                getValue: ( row: Ticket ) => row.post_status,
                render: ( { item } ) => {
                    const statusColors = {
                        open: 'bg-[#FDF2F8] text-[#9D174D]',
                        closed: 'bg-[#D4FBEF] text-[#00563F]',
                    };
                    return isLoading ? (
                        <span className="block w-16 h-3 rounded bg-gray-200 animate-pulse"></span>
                    ) : (
                        <span
                            className={ `inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-medium ${
                                statusColors[ item.post_status ] ||
                                'bg-[#F1F1F4] text-[#393939]'
                            }` }
                        >
                            {
                                TICKET_STATUSES.find(
                                    ( status ) =>
                                        status.value === item.post_status
                                )?.label
                            }
                        </span>
                    );
                },
            },
            {
                id: 'ticket_date',
                label: __( 'Date', 'dokan' ),
                getValue: ( row: Ticket ) => row.ticket_date,
                render: ( { item }: { item: Ticket } ) =>
                    isLoading ? (
                        <span className="block w-16 h-3 rounded bg-gray-200 animate-pulse"></span>
                    ) : (
                        <div className="text-gray-900">
                            { item.ticket_date }
                        </div>
                    ),
            },
        ],
        [ isLoading ]
    );
    const [ view, setView ] = useState< any >( {
        type: 'table',
        perPage: 20,
        page: 1,
        search: '',
        layout: {
            density: 'comfortable',
            table: {
                primaryField: 'id',
            },
            // Distribute column widths so the table fills its width evenly.
            // Without explicit widths, DataViews lets the last data column
            // (Date) expand to absorb all leftover space, leaving a large gap.
            styles: {
                id: { width: '12%' },
                post_title: { width: '22%' },
                vendor_name: { width: '16%' },
                customer_name: { width: '16%' },
                post_status: { width: '12%' },
                ticket_date: { width: '22%' },
            },
        },
        fields: fields.map( ( field ) => field.id ),
    } );
    const tabItems = useMemo(
        () =>
            TICKET_STATUSES.map( ( status ) => ( {
                value: status.value,
                label: status.label,
                count: tabCounts[ status.value as keyof typeof tabCounts ],
            } ) ),
        [ tabCounts ]
    );

    const fetchTickets = useCallback( async () => {
        setIsLoading( true );
        try {
            const post_status = activeTab; // 'all' | 'open' | 'closed'
            const filterParams: Record< string, any > = {};

            if ( vendor?.value ) {
                filterParams.vendor_id = vendor.value;
            }
            if ( customer?.value ) {
                filterParams.customer_id = customer.value;
            }
            if ( order ) {
                filterParams.order_id = order;
            }
            if ( after ) {
                filterParams.from_date = after;
            }
            if ( before ) {
                filterParams.to_date = before;
            }

            const query = {
                per_page: view?.perPage ?? 20,
                page: view?.page ?? 1,
                search: view?.search ?? '',
                post_status,
                ...( Object.keys( filterParams ).length > 0
                    ? { filter: filterParams }
                    : {} ),
            } as Record< string, any >;

            const response: Response = ( await apiFetch( {
                path: addQueryArgs(
                    '/dokan/v1/admin/support-ticket',
                    query
                ) as string,
                parse: false,
            } ) ) as unknown as Response;

            const data: Ticket[] = await ( response as any ).json();

            const total = parseInt(
                ( response as any ).headers.get( 'X-WP-Total' ) || '0',
                10
            );
            const openCount = parseInt(
                ( response as any ).headers.get( 'X-Status-Open' ) || '0',
                10
            );
            const closedCount = parseInt(
                ( response as any ).headers.get( 'X-Status-Closed' ) || '0',
                10
            );
            const allCount = parseInt(
                ( response as any ).headers.get( 'X-Status-All' ) ||
                    String( total ),
                10
            );

            if ( activeTab === 'open' ) {
                setTotalItems( openCount );
            } else if ( activeTab === 'closed' ) {
                setTotalItems( closedCount );
            } else {
                setTotalItems( total );
            }

            setTickets( data );
            setTabCounts( {
                all: allCount,
                open: openCount,
                closed: closedCount,
            } );
        } catch ( e ) {
            // eslint-disable-next-line no-console
            console.error( 'Failed to fetch support tickets', e );
        } finally {
            setIsLoading( false );
        }
    }, [
        activeTab,
        vendor,
        customer,
        order,
        after,
        before,
        view?.page,
        view?.perPage,
        view?.search,
    ] );

    useEffect( () => {
        fetchTickets();
    }, [ fetchTickets ] );

    // Row action helpers
    const refetchAfter = async ( fn: () => Promise< any > ) => {
        try {
            await fn();
        } catch ( e ) {
            // ignore, error already handled in fn
        } finally {
            fetchTickets();
        }
    };

    const doBatch = async ( payload: Record< string, number[] > ) => {
        return apiFetch( {
            path: '/dokan/v1/admin/support-ticket/batch',
            method: 'POST',
            data: payload,
        } )
            .then( () => {
                toast( {
                    title: __( 'Success', 'dokan' ),
                    subtitle: __(
                        'Support tickets updated successfully',
                        'dokan'
                    ),
                    type: 'success',
                    duration: 2000,
                } );
            } )
            .catch( ( e ) => {
                toast( {
                    title: __( 'Error', 'dokan' ),
                    subtitle: __( 'Failed to update support tickets', 'dokan' ),
                    type: 'error',
                    duration: 2000,
                } );
            } );
    };

    const changeStatus = async ( id: number, status: 'open' | 'closed' ) => {
        return apiFetch( {
            path: addQueryArgs(
                `/dokan/v1/admin/support-ticket/${ id }/status`,
                { status }
            ) as string,
            method: 'POST',
        } )
            .then( () => {
                toast( {
                    title: __( 'Success', 'dokan' ),
                    subtitle: __(
                        'Support ticket status updated successfully',
                        'dokan'
                    ),
                    type: 'success',
                    duration: 2000,
                } );
            } )
            .catch( ( e ) => {
                toast( {
                    title: __( 'Error', 'dokan' ),
                    subtitle: __(
                        'Failed to update support ticket status',
                        'dokan'
                    ),
                    type: 'error',
                    duration: 2000,
                } );
            } );
    };

    const actions = [
        {
            id: 'open',
            label: () => getActionLabel( <RotateCcw size={ 16 } className="fill-none!" />, __( 'Open', 'dokan' ) ),
            icon: <RotateCcw size={ 16 } className="fill-none!" />,
            supportsBulk: true,
            isEligible: ( item: Ticket ) => item?.post_status === 'closed',
            callback: ( rows: Ticket | Ticket[] ) => {
                const items = Array.isArray( rows ) ? rows : [ rows ];
                const ids = items.map( ( r ) => r.ID );
                if ( ids.length === 1 ) {
                    return refetchAfter( () =>
                        changeStatus( ids[ 0 ], 'open' )
                    );
                }
                return refetchAfter( async () => {
                    await Promise.all(
                        ids.map( ( id ) => changeStatus( id, 'open' ) )
                    );
                } );
            },
        },
        {
            id: 'close',
            label: () => getActionLabel( <Check size={ 16 } className="fill-none!" />, __( 'Close', 'dokan' ) ),
            icon: <Check size={ 16 } className="fill-none!" />,
            supportsBulk: true,
            isEligible: ( item: Ticket ) => item?.post_status === 'open',
            callback: ( rows: Ticket | Ticket[] ) => {
                const items = Array.isArray( rows ) ? rows : [ rows ];
                const ids = items.map( ( r ) => r.ID );
                if ( ids.length > 1 ) {
                    return refetchAfter( () => doBatch( { close: ids } ) );
                }
                return refetchAfter( () => changeStatus( ids[ 0 ], 'closed' ) );
            },
        },
        {
            id: 'mark_as_read',
            label: () => getActionLabel( <BookOpenCheck size={ 16 } className="fill-none!" />, __( 'Mark as Read', 'dokan' ) ),
            icon: <BookOpenCheck size={ 16 } className="fill-none!" />,
            supportsBulk: true,
            isEligible: ( item: Ticket ) => item?.reading === 'no',
            callback: ( rows: Ticket | Ticket[] ) => {
                const items = Array.isArray( rows ) ? rows : [ rows ];
                const ids = items.map( ( r ) => r.ID );
                return refetchAfter( () => doBatch( { mark_as_read: ids } ) );
            },
        },
        {
            id: 'mark_as_unread',
            label: () => getActionLabel( <BookOpenText size={ 16 } className="fill-none!" />, __( 'Mark as Unread', 'dokan' ) ),
            icon: <BookOpenText size={ 16 } className="fill-none!" />,
            isEligible: ( item: Ticket ) => item?.reading === 'yes',
            supportsBulk: true,
            callback: ( rows: Ticket | Ticket[] ) => {
                const items = Array.isArray( rows ) ? rows : [ rows ];
                const ids = items.map( ( r ) => r.ID );
                return refetchAfter( () => doBatch( { mark_as_unread: ids } ) );
            },
        },
    ];

    const displayDateRange = ( startDate, endDate ) => {
        return sprintf(
            // translators: %1$s: start date, %2$s: end date.
            __( '%1$s - %2$s', 'dokan' ),
            dateI18n( getSettings().formats.date, startDate ),
            dateI18n( getSettings().formats.date, endDate )
        );
    };

    const loadCustomers = async ( inputValue: string ) => {
        try {
            const url = addQueryArgs(
                '/dokan/v1/admin/support-ticket/customers',
                { search: inputValue }
            );

            const data: any = await apiFetch( {
                path: url,
            } );
            return Array.isArray( data )
                ? data.map( ( customer ) => ( {
                      value: customer.ID,
                      label: customer.display_name,
                  } ) )
                : [];
        } catch ( error ) {
            // eslint-disable-next-line no-console
            console.error( 'Failed to load customers', error );
            return [];
        }
    };

    const filter = useMemo(
        () => ( {
            fields: [
                {
                    id: 'vendor',
                    label: __( 'Vendor', 'dokan' ),
                    field: (
                        <VendorAsyncSelect
                            icon={ <Home size={ 16 } /> }
                            key="vendor-select"
                            value={ vendor }
                            onChange={ ( opt: VendorOption ) =>
                                setVendor( opt )
                            }
                            defaultOptions
                            placeholder={ __( 'Search vendors', 'dokan' ) }
                        />
                    ),
                },
                {
                    id: 'customer',
                    label: __( 'Customer', 'dokan' ),
                    field: (
                        <AsyncSelect
                            value={ customer }
                            key="customer-select"
                            icon={ <SquareUserRound size={ 16 } /> }
                            onChange={ ( opt: VendorOption ) =>
                                setCustomer( opt )
                            }
                            loadOptions={ loadCustomers }
                            cacheOptions
                            defaultOptions
                            placeholder={ __( 'Search customers', 'dokan' ) }
                        />
                    ),
                },
                {
                    id: 'date',
                    label: __( 'Date Range', 'dokan' ),
                    field: (
                        <DateRangePicker
                            key="date-range-picker"
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
                                    setFocusedInput( update.focusedInput );
                                    if (
                                        update.focusedInput === 'endDate' &&
                                        after
                                    ) {
                                        setBefore( '' );
                                        setBeforeText( '' );
                                    }
                                }
                            } }
                            shortDateFormat="MM/DD/YYYY"
                            focusedInput={ focusedInput }
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
                            } }
                            onOk={ () => {
                                // Date range applied
                            } }
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
                                    placeholder: 'Date',
                                    readOnly: true,
                                } }
                            />
                        </DateRangePicker>
                    ),
                },
                {
                    id: 'order',
                    label: __( 'Order', 'dokan' ),
                    field: (
                        <SearchInput
                            value={ order }
                            onChange={ ( val: string ) => setOrder( val ) }
                            input={ {
                                placeholder: __( 'Search order', 'dokan' ),
                            } }
                        />
                    ),
                },
            ],
            onReset: () => {
                setVendor( null );
                setCustomer( null );
                setOrder( '' );
                setAfter( '' );
                setAfterText( '' );
                setBefore( '' );
                setBeforeText( '' );
            },
            onFilterRemove: ( id: string ) => {
                if ( id === 'vendor' ) {
                    setVendor( null );
                } else if ( id === 'customer' ) {
                    setCustomer( null );
                } else if ( id === 'date' ) {
                    setAfter( '' );
                    setAfterText( '' );
                    setBefore( '' );
                    setBeforeText( '' );
                } else if ( id === 'order' ) {
                    setOrder( '' );
                }
            },
        } ),
        [
            vendor,
            customer,
            order,
            after,
            afterText,
            before,
            beforeText,
            focusedInput,
        ]
    );

    return (
        <div className="withdraw-admin-page">
            <h2 className="text-2xl leading-3 text-gray-900 font-bold mb-6">
                { __( 'Store Support', 'dokan' ) }
            </h2>
            <div className="dokan-admin-dashboard-datatable">
                <DataViews< Ticket >
                    namespace="admin_store_support"
                    view={ view }
                    onChangeView={ setView }
                    fields={ fields as any }
                    data={ tickets }
                    actions={ actions as any }
                    selection={ selection }
                    onChangeSelection={ setSelection }
                    paginationInfo={ {
                        totalItems,
                        totalPages: Math.max(
                            1,
                            Math.ceil( totalItems / ( view?.perPage || 20 ) )
                        ),
                    } }
                    defaultLayouts={ defaultLayouts as any }
                    tabs={ {
                        items: tabItems,
                        onSelect: ( name: string ) => {
                            setSelection( [] );
                            setActiveTab( name as any );
                        },
                        defaultValue: 'all',
                        headerContent: [
                            <SearchInput
                                key="search"
                                value={ view?.search || '' }
                                onChange={ ( val: string ) =>
                                    setView( ( v: any ) => ( {
                                        ...v,
                                        page: 1,
                                        search: val,
                                    } ) )
                                }
                                input={ {
                                    placeholder: __( 'Search ticket', 'dokan' ),
                                } }
                            />,
                        ],
                    } }
                    filter={ filter as any }
                    getItemId={ ( r: Ticket ) => String( r.ID ) }
                    isLoading={ isLoading as boolean }
                />
            </div>
            <DokanToaster />
        </div>
    );
};

export default StoreSupportList;
