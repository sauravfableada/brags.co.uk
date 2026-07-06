// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { truncate } from '@dokan/utilities';
import apiFetch from '@wordpress/api-fetch';
import { RawHTML, useCallback, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

// Import Dokan components
import {
    DataViews,
    DateTimeHtml,
    DokanModal,
    VendorAsyncSelect,
    DokanTooltip as Tooltip,
    getActionLabel,
    // @ts-ignore
    // eslint-disable-next-line import/no-unresolved
} from '@dokan/components';

import { Edit, Home, RotateCw, Star, Trash } from 'lucide-react';
import ItemEdit from './ItemEdit';
import { DokanToaster, useToast } from '@getdokan/dokan-ui';

type Customer = {
    id: number;
    first_name: string | null;
    last_name: string | null;
    email: string;
    display_name: string;
};

type Vendor = {
    id: number;
    first_name: string | null;
    last_name: string | null;
    shop_name: string;
    shop_url: string;
    avatar: string | false;
    banner: string;
};

export type StoreReview = {
    id: number;
    title: string;
    content: string;
    status: string;
    created_at: string; // RFC3339
    customer: Customer;
    vendor: Vendor;
    rating: number;
};

// Define review statuses for tab filtering
const REVIEW_STATUSES = [
    { value: 'all', label: __( 'All', 'dokan' ) },
    { value: 'trash', label: __( 'Trash', 'dokan' ) },
];

const PER_PAGE = 10;

const ReviewList = () => {
    const toast = useToast();
    const [ data, setData ] = useState< StoreReview[] >( [] );
    const [ vendorsData, setVendorsData ] = useState< any >( null );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ totalItems, setTotalItems ] = useState( 0 );
    const [ statusCounts, setStatusCounts ] = useState( {
        all: 0,
        trash: 0,
    } );

    const renderContent = ( content: string, length: number = 72 ) => {
        if ( content.length > length ) {
            return (
                <Tooltip content={ content }>
                    <div className="line-clamp-2 text-wrap text-sm text-gray-600">
                        <RawHTML>{ truncate( content, length ) }</RawHTML>
                    </div>
                </Tooltip>
            );
        }
        return (
            <div className="text-sm text-gray-600 text-wrap">
                <RawHTML>{ content }</RawHTML>
            </div>
        );
    };

    // Define fields for the table columns
    const fields = [
        {
            id: 'title',
            label: __( 'Title', 'dokan' ),
            enableGlobalSearch: true,
            enableSorting: false,
            render: ( { item }: { item: StoreReview } ) => {
                return renderContent( item.title || '-' );
            },
        },
        {
            id: 'content',
            label: __( 'Comment', 'dokan' ),
            enableGlobalSearch: true,
            render: ( { item }: { item: StoreReview } ) => {
                return renderContent( item.content || '-' );
            },
        },
        {
            id: 'customer',
            label: __( 'Reviewer', 'dokan' ),
            enableGlobalSearch: true,
            render: ( { item }: { item: StoreReview } ) => {
                return (
                    <div className="text-gray-600">
                        { item.customer?.display_name || __( 'N/A', 'dokan' ) }
                    </div>
                );
            },
        },
        {
            id: 'vendor',
            label: __( 'Store', 'dokan' ),
            enableGlobalSearch: true,
            enableSorting: false,
            render: ( { item }: { item: StoreReview } ) => {
                return renderContent( item.vendor?.shop_name, 32 );
            },
        },
        {
            id: 'rating',
            label: __( 'Rating', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: StoreReview } ) => {
                return (
                    <div className="flex items-center">
                        <span className="text-yellow-600 mr-1">
                            <Star fill="#eab308" size={ 20 } />
                        </span>
                        <span className="text-sm text-gray-600">
                            { item.rating }
                        </span>
                    </div>
                );
            },
        },
        {
            id: 'date',
            label: __( 'Submitted On', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: StoreReview } ) => {
                return (
                    <div className="text-gray-900">
                        <DateTimeHtml.Date date={ item.created_at } />
                    </div>
                );
            },
        },
    ];

    const actions = [
        {
            id: 'edit',
            label: () => getActionLabel( <Edit size={ 16 } className="fill-none!" />, __( 'Edit', 'dokan' ) ),
            icon: <Edit size={ 16 } className="fill-none!" />,
            isPrimary: false,
            callback: ( items: StoreReview[] ) => {
                openModal( 'edit', items );
            },
        },
        {
            id: 'trash',
            label: () => getActionLabel( <Trash size={ 16 } className="fill-none!" />, __( 'Move to Trash', 'dokan' ) ),
            icon: <Trash size={ 16 } className="fill-none!" />,
            supportsBulk: true,
            isDestructive: true,
            confirmTitle: __( 'Move to Trash', 'dokan' ),
            confirmMessage: __(
                'Are you sure you want to move the selected review(s) to trash?',
                'dokan'
            ),
            confirmButtonLabel: __( 'Move to Trash', 'dokan' ),
            isEligible: ( item: StoreReview ) => item?.status !== 'trash',
            callback: async ( items: StoreReview[] ) => {
                await handleBulkAction(
                    'trash',
                    items.map( ( item ) => item.id )
                );
            },
        },
        {
            id: 'restore',
            label: () => getActionLabel( <RotateCw size={ 16 } className="fill-none!" />, __( 'Restore', 'dokan' ) ),
            icon: <RotateCw size={ 16 } className="fill-none!" />,
            supportsBulk: true,
            isDestructive: true,
            confirmTone: 'positive',
            confirmTitle: __( 'Restore Review', 'dokan' ),
            confirmMessage: __(
                'Are you sure you want to restore the selected review(s)?',
                'dokan'
            ),
            confirmButtonLabel: __( 'Restore', 'dokan' ),
            isEligible: ( item: StoreReview ) => item?.status === 'trash',
            callback: async ( items: StoreReview[] ) => {
                await handleBulkAction(
                    'restore',
                    items.map( ( item ) => item.id )
                );
            },
        },
        {
            id: 'delete',
            label: () => getActionLabel( <Trash size={ 16 } className="fill-none! text-red-600" />, __( 'Delete Permanently', 'dokan' ) ),
            icon: <Trash size={ 16 } className="fill-none! text-red-600" />,
            supportsBulk: true,
            isDestructive: true,
            confirmTitle: __( 'Delete Review', 'dokan' ),
            confirmMessage: __(
                'Are you sure you want to permanently delete the selected review(s)? This action cannot be undone.',
                'dokan'
            ),
            confirmButtonLabel: __( 'Delete', 'dokan' ),
            isEligible: ( item: StoreReview ) => item?.status === 'trash',
            callback: async ( items: StoreReview[] ) => {
                await handleBulkAction(
                    'delete',
                    items.map( ( item ) => item.id )
                );
            },
        },
    ];

    // Set for handling bulk selection
    const [ selection, setSelection ] = useState( [] );

    // Modal state management
    const [ modalState, setModalState ] = useState< {
        isOpen: boolean;
        type: string;
        items: StoreReview[];
    } >( {
        isOpen: false,
        type: '',
        items: [],
    } );

    // Modal helper functions
    const openModal = ( type: string, items: StoreReview[] ) => {
        setModalState( {
            isOpen: true,
            type,
            items,
        } );
    };

    const closeModal = () => {
        setModalState( {
            isOpen: false,
            type: '',
            items: [],
        } );
    };

    // Set data view default layout
    const defaultLayouts = {
        table: { density: 'comfortable' },
        grid: {},
        list: {},
    };

    // Set view state for handling the table view
    const [ view, setView ] = useState( {
        perPage: PER_PAGE,
        page: 1,
        search: '',
        type: 'table',
        titleField: 'title',
        status: 'all',
        layout: defaultLayouts,
        fields: fields.map( ( field ) =>
            field.id !== 'title' ? field.id : ''
        ),
    } );

    // Handle tab selection for status filtering
    const handleTabSelect = ( tabName: string ) => {
        setView( ( prevView ) => ( {
            ...prevView,
            status: tabName,
            page: 1, // Reset to first page when changing status
        } ) );
    };

    const tabItems = REVIEW_STATUSES.map( ( status ) => ( {
        value: status.value,
        label: status.label,
        count: statusCounts[ status.value as keyof typeof statusCounts ],
    } ) );

    const filterFields = [
        {
            id: 'vendor',
            label: __( 'Vendor', 'dokan' ),
            field: (
                <VendorAsyncSelect
                    icon={ <Home size={ 16 } /> }
                    key="vendor-select"
                    value={ vendorsData }
                    onChange={ setVendorsData }
                    placeholder={ __( 'Select Vendor', 'dokan' ) }
                    prefetch
                    defaultOptions
                    cacheOptions
                />
            ),
        },
    ];

    // Handle data fetching from the server
    const fetchReviews = useCallback( async () => {
        setIsLoading( true );
        try {
            const queryArgs = {
                per_page: view?.perPage ?? PER_PAGE,
                page: view?.page ?? 1,
                status: view.status || 'all',
                vendor_id: vendorsData?.value || undefined,
            };

            // Fetch data from the REST API
            const response = await apiFetch< any >( {
                path: addQueryArgs( 'dokan/v1/store-reviews', queryArgs ),
                // @ts-ignore
                parse: false, // Get raw response to access headers
            } );

            const responseData = await response.json();
            setTotalItems(
                parseInt( response.headers.get( 'X-WP-Total' ) || 0 )
            );

            setData( Array.isArray( responseData ) ? responseData : [] );

            // Extract status counts from response headers
            const counts = {
                all: parseInt( response.headers.get( 'X-Status-All' ) || 0 ),
                trash: parseInt(
                    response.headers.get( 'X-Status-Trash' ) || 0
                ),
            };
            setStatusCounts( counts );
        } catch ( error ) {
            setData( [] );
        } finally {
            setIsLoading( false );
        }
    }, [ vendorsData, view?.page, view?.perPage, view.status ] );

    // Handle bulk actions
    const handleBulkAction = async ( action: string, ids: number[] ) => {
        try {
            const deletedData = { [ action ]: ids };

            await apiFetch( {
                path: `/dokan/v1/store-reviews/batch`,
                method: 'POST',
                data: deletedData,
            } );

            void fetchReviews(); // Refresh data
            setSelection( [] ); // Clear selection
            const messages = {
                trash: __( 'Review(s) moved to trash.', 'dokan' ),
                restore: __( 'Review(s) restored successfully.', 'dokan' ),
                delete: __( 'Review(s) deleted permanently.', 'dokan' ),
            };
            toast( {
                type: 'success',
                title: messages[ action as keyof typeof messages ],
            } );
        } catch ( error ) {
            toast( {
                type: 'error',
                title: __( 'Failed to perform the action.', 'dokan' ),
            } );
        }
    };

    // Clear filters
    const clearFilter = () => {
        setVendorsData( null );
        void fetchReviews();
    };

    const saveChanges = async () => {
        if ( ! modalState.items[ 0 ] ) {
            toast( {
                type: 'error',
                title: __( 'No review selected for editing.', 'dokan' ),
            } );
            return;
        }

        try {
            const reviewToUpdate = modalState.items[ 0 ];
            await apiFetch( {
                path: `/dokan/v1/store-reviews/${ reviewToUpdate.id }`,
                method: 'PUT',
                data: {
                    title: reviewToUpdate.title,
                    content: reviewToUpdate.content,
                    rating: reviewToUpdate.rating,
                },
            } );

            // Refresh the reviews list
            void fetchReviews();
            closeModal();
            toast( {
                type: 'success',
                title: __( 'Review updated successfully.', 'dokan' ),
            } );
        } catch ( error ) {
            toast( {
                type: 'error',
                title: __( 'Failed to update the review.', 'dokan' ),
            } );
        }
    };

    const reviewEdit = ( e: any ) => {
        const { name, value } = e.target;
        setModalState( ( prev ) => {
            if ( ! prev.items[ 0 ] ) {
                return prev;
            }
            return {
                ...prev,
                items: [
                    {
                        ...prev.items[ 0 ],
                        [ name ]:
                            name === 'rating'
                                ? parseInt( String( value ), 10 )
                                : value,
                    },
                ],
            };
        } );
    };

    // Fetch reviews when view changes
    useEffect( () => {
        void fetchReviews();
    }, [ fetchReviews ] );

    return (
        <div className="store-reviews-admin-page">
            <div className="flex items-center justify-between mb-4">
                <h2 className="text-xl font-bold text-gray-900 leading-8">
                    { __( 'Store Reviews', 'dokan' ) }
                </h2>
            </div>

            { /* Data Table */ }
            <div className="dokan-admin-dashboard-datatable">
                <DataViews
                    data={ data }
                    namespace="store-reviews-data-view"
                    defaultLayouts={ defaultLayouts }
                    fields={ fields }
                    getItemId={ ( item ) => item.id }
                    // @ts-ignore
                    onChangeView={ setView }
                    paginationInfo={ {
                        totalItems,
                        totalPages: Math.ceil( totalItems / view.perPage ),
                    } }
                    // @ts-ignore
                    view={ view }
                    selection={ selection }
                    // @ts-ignore
                    onChangeSelection={ setSelection }
                    // @ts-ignore
                    actions={ actions }
                    isLoading={ isLoading }
                    tabs={ {
                        items: tabItems,
                        onSelect: ( status ) => {
                            setSelection( [] );
                            handleTabSelect( status );
                        },
                        defaultValue: 'all',
                    } }
                    filter={ {
                        fields: filterFields,
                        onFilterRemove: clearFilter,
                        onReset: () => clearFilter(),
                    } }
                />
            </div>

            { modalState.isOpen &&
                modalState.type === 'edit' &&
                modalState.items.length > 0 && (
                    <DokanModal
                        className={ `w-[800px]` }
                        isOpen={ modalState.isOpen }
                        namespace={ `edit-review-${ modalState.items[ 0 ]?.id }` }
                        onClose={ closeModal }
                        onConfirm={ saveChanges }
                        dialogTitle={ __( 'Edit Review', 'dokan' ) }
                        confirmButtonText={ __( 'Save Changes', 'dokan' ) }
                        cancelButtonText={ __( 'Cancel', 'dokan' ) }
                        dialogContent={
                            <ItemEdit
                                item={ modalState.items[ 0 ] }
                                onChange={ reviewEdit }
                            />
                        }
                    />
                ) }

            <DokanToaster />
        </div>
    );
};

export default ReviewList;
