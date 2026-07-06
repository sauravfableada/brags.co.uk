import { __ } from '@wordpress/i18n';
import { useEffect, useMemo, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import {
    DataViews,
    SearchInput,
    DokanButton,
    getActionLabel,
} from '@dokan/components';
import SellerBadgePreviewModal from './SellerBadgePreviewModal';
import { addQueryArgs } from '@wordpress/url';
import { twMerge } from 'tailwind-merge';
import {
    Plus,
    Pencil,
    Eye,
    Users,
    CheckCircle,
    FileEdit,
    Trash2,
} from 'lucide-react';
import { DokanToaster, useToast } from '@getdokan/dokan-ui';

// Types
interface SellerBadgeEvent {
    id: string;
    title: string;
}
interface SellerBadgeItem {
    id: number;
    badge_name: string;
    badge_logo: string;
    event_type: string;
    event: SellerBadgeEvent;
    badge_type: 'published' | 'draft';
    formatted_badge_status: string;
    level_count: number;
    vendor_count: number;
}
export default function SellerBadgesList( { navigate } ) {
    const [ status, setStatus ] = useState< 'all' | 'published' | 'draft' >(
        'all'
    );
    const [ search, setSearch ] = useState( '' );
    const [ isLoading, setIsLoading ] = useState( false );
    const [ items, setItems ] = useState< SellerBadgeItem[] >( [] );
    const [ counts, setCounts ] = useState( {
        all: 0,
        published: 0,
        draft: 0,
    } );
    const [ page, setPage ] = useState( 1 );
    const [ perPage, setPerPage ] = useState( 15 );
    const [ totalPages, setTotalPages ] = useState( 1 );
    const [ totalItems, setTotalItems ] = useState( 0 );
    const [ selection, setSelection ] = useState< number[] >( [] );
    const [ previewBadgeId, setPreviewBadgeId ] = useState< number | null >(
        null
    );
    const toast = useToast();

    const query = useMemo( () => {
        const args: Record< string, string | number > = {
            badge_status: status,
            per_page: perPage,
            page,
            orderby: 'badge_id',
            order: 'asc',
        };
        if ( search ) {
            args.badge_name = search;
        }
        return args;
    }, [ status, search, page, perPage ] );

    // Fetch headers for counts and totals using a raw request
    useEffect( () => {
        setIsLoading( true );
        let isMounted = true;
        const path = addQueryArgs( '/dokan/v1/seller-badge', query as any );
        // @ts-ignore fetch to get headers
        apiFetch( { path, parse: false } )
            .then( ( res: Response ) => {
                if (
                    ! isMounted ||
                    ! res ||
                    typeof ( res as any ).headers?.get !== 'function'
                ) {
                    return;
                }

                // eslint-disable-next-line @typescript-eslint/no-shadow
                res.json().then( ( data ) => {
                    setItems( data as SellerBadgeItem[] );
                } );
                const headers = ( res as any ).headers;
                const cAll = parseInt(
                    headers.get( 'X-Status-All' ) || '0',
                    10
                );
                const cPub = parseInt(
                    headers.get( 'X-Status-Published' ) || '0',
                    10
                );
                const cDraft = parseInt(
                    headers.get( 'X-Status-Draft' ) || '0',
                    10
                );
                const tPages = parseInt(
                    headers.get( 'X-WP-TotalPages' ) || '1',
                    10
                );
                const tItems = parseInt(
                    headers.get( 'X-WP-Total' ) || '0',
                    10
                );
                setCounts( { all: cAll, published: cPub, draft: cDraft } );
                setTotalPages( tPages );
                setTotalItems( tItems );
            } )
            .catch( () => {
                if ( ! isMounted ) {
                    return;
                }
                setItems( [] );
                setCounts( { all: 0, published: 0, draft: 0 } );
                setTotalItems( 0 );
                setTotalPages( 1 );
            } )
            .finally( () => {
                if ( ! isMounted ) {
                    return;
                }
                setIsLoading( false );
            } );
        return () => {
            isMounted = false;
        };
    }, [ query ] );

    const loadingClass = twMerge(
        'bg-neutral-200! rounded! animate-pulse! text-transparent!'
    );

    const fields = [
        {
            id: 'badge_name',
            label: __( 'Badges Name', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: SellerBadgeItem } ) => (
                <div className="flex items-center gap-3">
                    <img
                        src={ item.badge_logo }
                        alt={ item.badge_name }
                        className={ twMerge(
                            'w-10 h-10 object-contain',
                            isLoading ? loadingClass : ''
                        ) }
                    />
                    <strong>
                        <a
                            href={ `?page=dokan-dashboard#/dokan-seller-badge/edit/${ item.id }` }
                            className={ twMerge(
                                'dokan-link',
                                isLoading ? loadingClass : ''
                            ) }
                        >
                            { item.badge_name }
                        </a>
                    </strong>
                </div>
            ),
        },
        {
            id: 'event_type',
            label: __( 'Badge Event', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: SellerBadgeItem } ) => (
                <div className="flex flex-col">
                    <span
                        className={ twMerge( isLoading ? loadingClass : '' ) }
                    >
                        { item.event?.title || item.event_type }
                    </span>
                    <span
                        className={ twMerge(
                            'text-xs',
                            isLoading ? loadingClass : ''
                        ) }
                    >
                        { __( 'Level:', 'dokan' ) }
                        <strong>{ item.level_count }</strong>
                    </span>
                </div>
            ),
        },
        {
            id: 'vendor_count',
            label: __( 'No. of Vendors', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: SellerBadgeItem } ) => (
                <a
                    href={ `?page=dokan-dashboard#/vendors?badge_id=${ item.id }` }
                    className={ twMerge(
                        'text-gray-700 inline-flex items-center gap-2.5',
                        isLoading ? loadingClass : ''
                    ) }
                >
                    <Users size={ 16 } className="fill-none!" />
                    { item.vendor_count }
                </a>
            ),
        },
        {
            id: 'badge_status',
            label: __( 'Status', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: SellerBadgeItem } ) => (
                <span
                    className={ twMerge(
                        'inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-medium',
                        item.badge_status === 'published'
                            ? 'bg-[#D4FBEF] text-[#00563F]'
                            : 'bg-[#F1F1F4] text-[#393939]',
                        isLoading ? loadingClass : ''
                    ) }
                >
                    { item.formatted_badge_status || item.badge_status }
                </span>
            ),
        },
    ];

    const defaultLayouts = {
        table: {},
        grid: {},
        list: {},
        density: 'comfortable',
    } as const;

    const [ view, setView ] = useState( {
        perPage,
        page,
        search: '',
        type: 'table',
        titleField: 'badge_name',
        layout: { ...defaultLayouts },
        fields,
    } );

    const actions = [
        {
            id: 'edit',
            label: () => getActionLabel( <Pencil size={ 16 } className="fill-none!" />, __( 'Edit', 'dokan' ) ),
            icon: <Pencil size={ 16 } className="fill-none!" />,
            isPrimary: false,
            supportsBulk: false,
            isEligible: () => true,
            callback: ( data: SellerBadgeItem[] ) => {
                const item = data[ 0 ];
                if ( item?.id ) {
                    window.location.href = `?page=dokan-dashboard#/dokan-seller-badge/edit/${ item.id }`;
                }
            },
        },
        {
            id: 'view',
            label: () => getActionLabel( <Eye size={ 16 } className="fill-none!" />, __( 'Preview', 'dokan' ) ),
            icon: <Eye size={ 16 } className="fill-none!" />,
            isPrimary: false,
            supportsBulk: false,
            isEligible: () => true,
            callback: ( data: SellerBadgeItem[] ) => {
                const item = data[ 0 ];
                if ( item?.id ) {
                    setPreviewBadgeId( item.id );
                }
            },
        },
        {
            id: 'show_vendors',
            label: () => getActionLabel( <Users size={ 16 } className="fill-none!" />, __( 'Show Vendors', 'dokan' ) ),
            icon: <Users size={ 16 } className="fill-none!" />,
            isPrimary: false,
            supportsBulk: false,
            isEligible: () => true,
            callback: ( data: SellerBadgeItem[] ) => {
                const item = data[ 0 ];
                if ( item?.id ) {
                    window.location.href = `?page=dokan-dashboard#/vendors?badge_id=${ item.id }`;
                }
            },
        },
        {
            id: 'publish',
            label: () => getActionLabel( <CheckCircle size={ 16 } className="fill-none!" />, __( 'Publish Badge', 'dokan' ) ),
            icon: <CheckCircle size={ 16 } className="fill-none!" />,
            isPrimary: false,
            supportsBulk: true,
            isDestructive: true,
            confirmTone: 'positive',
            confirmTitle: __( 'Publish selected badge(s)?', 'dokan' ),
            confirmMessage: __(
                'Are you sure you want to publish selected badges? Note that, badge will be assigned to the eligible vendors as soon as they get published.',
                'dokan'
            ),
            confirmButtonLabel: __( 'Yes, Publish', 'dokan' ),
            isEligible: ( item: SellerBadgeItem ) =>
                item.badge_status === 'draft',
            callback: async ( data: SellerBadgeItem[] ) => {
                const ids = ( data || [] ).map( ( item ) => item.id );
                if ( ! ids.length ) {
                    return;
                }
                await executeBadgeAction(
                    'publish',
                    ids,
                    __( 'Selected badges published successfully.', 'dokan' )
                );
            },
        },
        {
            id: 'draft',
            label: () => getActionLabel( <FileEdit size={ 16 } className="fill-none!" />, __( 'Set Badge As Draft', 'dokan' ) ),
            icon: <FileEdit size={ 16 } className="fill-none!" />,
            isPrimary: false,
            supportsBulk: true,
            isDestructive: true,
            confirmTone: 'default',
            confirmTitle: __( 'Draft selected badge(s)?', 'dokan' ),
            confirmMessage: __(
                'Are you sure you want move selected badges to draft? Note that, drafted badge will not be visible from vendor profile page.',
                'dokan'
            ),
            confirmButtonLabel: __( 'Yes, Draft', 'dokan' ),
            isEligible: ( item: SellerBadgeItem ) =>
                item.badge_status === 'published',
            callback: async ( data: SellerBadgeItem[] ) => {
                const ids = ( data || [] ).map( ( item ) => item.id );
                if ( ! ids.length ) {
                    return;
                }
                await executeBadgeAction(
                    'draft',
                    ids,
                    __( 'Selected badges moved to draft successfully.', 'dokan' )
                );
            },
        },
        {
            id: 'delete',
            label: () => getActionLabel( <Trash2 size={ 16 } className="fill-none!" />, __( 'Delete', 'dokan' ) ),
            icon: <Trash2 size={ 16 } className="fill-none!" />,
            isPrimary: false,
            supportsBulk: true,
            isDestructive: true,
            confirmTitle: __( 'Delete selected Badge?', 'dokan' ),
            confirmMessage: __(
                'Are you sure you want to delete selected badge? Note that, this action is permanent. All badge level data, seller acquired data related to the selected badges will be deleted.',
                'dokan'
            ),
            confirmButtonLabel: __( 'Yes, Delete', 'dokan' ),
            isEligible: () => true,
            callback: async ( data: SellerBadgeItem[] ) => {
                const ids = ( data || [] ).map( ( item ) => item.id );
                if ( ! ids.length ) {
                    return;
                }
                await executeBadgeAction(
                    'delete',
                    ids,
                    __( 'Selected badges deleted successfully.', 'dokan' )
                );
            },
        },
    ];

    const executeBadgeAction = async (
        action: string,
        ids: number[],
        successMessage: string
    ) => {
        try {
            await apiFetch( {
                path: '/dokan/v1/seller-badge/bulk-actions/',
                method: 'PUT',
                data: { action, ids },
            } );
            setSelection( [] );
            toast( {
                title: successMessage,
                type: 'success',
            } );

            // refresh the list
            const path = addQueryArgs( '/dokan/v1/seller-badge', query as any );
            const data = await apiFetch< SellerBadgeItem[] >( { path } );
            setItems( data );
        } catch ( e ) {
            toast( {
                title:
                    e?.message ||
                    __( 'Failed to perform bulk action.', 'dokan' ),
                type: 'error',
            } );
        }
    };

    const tabItems = useMemo(
        () => [
            {
                value: 'all',
                label: __( 'All', 'dokan' ),
                count: counts.all || 0,
            },
            {
                value: 'published',
                label: __( 'Published', 'dokan' ),
                count: counts.published || 0,
            },
            {
                value: 'draft',
                label: __( 'Draft', 'dokan' ),
                count: counts.draft || 0,
            },
        ],
        [ counts ]
    );

    const handleTabSelect = ( tabName: string ) => {
        setStatus( tabName as 'all' | 'published' | 'draft' );
        setPage( 1 );
    };

    return (
        <div className="dokan-layout dokan-admin-seller-badges-list-page">
            <div className="mb-[24px] flex items-center justify-between">
                <h2 className="text-2xl leading-3 text-gray-900 font-bold">
                    { __( 'Seller Badge', 'dokan' ) }
                </h2>
                <div className="flex items-center gap-2">
                    <DokanButton
                        type="button"
                        variant="primary"
                        onClick={ () => {
                            navigate( '/dokan-seller-badge/new' );
                        } }
                    >
                        <Plus size={ 16 } />
                        { __( 'Create Badge', 'dokan' ) }
                    </DokanButton>
                </div>
            </div>

            <div className="dokan-admin-dashboard-datatable">
                <DataViews
                    data={ items ?? [] }
                    namespace="dokan-admin-dashboard-seller-badges-dataview"
                    defaultLayouts={ { ...defaultLayouts } }
                    fields={ fields }
                    getItemId={ ( item: SellerBadgeItem ) => item.id.toString() }
                    onChangeView={ ( newView: any ) => {
                        setView( newView );
                        if ( newView?.perPage ) {
                            setPerPage( newView.perPage );
                        }
                        if ( newView?.page ) {
                            setPage( newView.page );
                        }
                    } }
                    paginationInfo={ { totalItems, totalPages } }
                    view={
                        {
                            ...view,
                            layout: { ...defaultLayouts },
                            fields: ( fields as any ).map( ( f: any ) =>
                                f.id !== ( view as any )?.titleField ? f.id : ''
                            ),
                        } as any
                    }
                    actions={ actions as any }
                    isLoading={ isLoading }
                    selection={ selection.map( String ) }
                    onChangeSelection={ ( ids ) =>
                        setSelection( ids.map( ( id ) => Number( id ) ) )
                    }
                    className="bg-white rounded border"
                    tabs={ {
                        items: tabItems,
                        onSelect: ( name ) => {
                            setSelection( [] );
                            handleTabSelect( name );
                        },
                        defaultValue: status,
                        headerContent: [
                            <SearchInput
                                key="search"
                                value={ search }
                                placeholder={ __(
                                    'Search by badge name',
                                    'dokan'
                                ) }
                                onChange={ ( val: string ) => {
                                    setPage( 1 );
                                    setSearch( val );
                                } }
                            />,
                        ],
                    } }
                />
            </div>

            { previewBadgeId !== null && (
                <SellerBadgePreviewModal
                    isOpen={ previewBadgeId !== null }
                    badgeId={ previewBadgeId as number }
                    onClose={ () => setPreviewBadgeId( null ) }
                />
            ) }

            <DokanToaster />
        </div>
    );
}
