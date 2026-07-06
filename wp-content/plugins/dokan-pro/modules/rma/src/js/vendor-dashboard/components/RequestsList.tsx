import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { capitalCase } from '@dokan/utilities';
import {
    DokanLink,
    DokanModal,
    DataViews,
    DateTimeHtml,
    // @ts-ignore
    // eslint-disable-next-line import/no-unresolved
} from '@dokan/components';
import { DokanToaster, useToast } from '@getdokan/dokan-ui';

import '../../../../../../src/definitions/window-types';
import { WarrantyRequest } from '../../types/warranty-request';
import { useWarrantyRequests } from '../hooks/useWarrantyRequests';
import { useStatusFilters } from '../hooks/useStatusFilters';

type RequestsListProps = {
    navigate: ( path: any, options?: any ) => void;
    location: { search: string; pathname: string };
    createSearchParams: ( params: Record< string, string > ) => string;
};

type filterState = {
    page: number;
    per_page: number;
};

// prettier-ignore
export default function RequestsList( { navigate, location, createSearchParams }: RequestsListProps ) {
    const toast = useToast();
    const queryParams = new URLSearchParams( location.search );
    const { orderUrl } = window.DokanRMAPanel;

    // State
    const [ showModal, setShowModal ] = useState< boolean >( false );
    const [ deletingRequest, setDeletingRequest ] = useState< WarrantyRequest >( null );
    const [ filterArgs, setFilterArgs ] = useState< filterState >( {
        page:  parseInt( queryParams.get( 'page' ) ) || 1,
        per_page: parseInt( queryParams.get( 'per_page' ) ) || 10,
    } );

    // Fields definition
    const fields = [
        {
            id: 'id',
            label: __( 'Details', 'dokan' ),
            render: ( { item }: { item: WarrantyRequest } ) => (
                <p className="*:inline-block">
                    { isLoading && (
                        <span className="block w-24 h-3 rounded bg-gray-200 animate-pulse" />
                    ) }

                    { ! isLoading && item.is_order_deleted && (
                        <>
                            { sprintf(
                                /* translators: 1) request id 2) customer name 3) order id 4) product id */
                                __(
                                    '#%1$d by %2$s on #%3$d',
                                    'dokan'
                                ),
                                item.id,
                                item.customer.name,
                                item.order_id
                            ) }
                        </>
                    ) }

                    { ! isLoading && ! item.is_order_deleted && (
                        <>
                            <span
                                role="button"
                                onClick={ (e) => {
                                    e.preventDefault();
                                    navigate( `/return-request/${ item.id }` );
                                }}
                                tabIndex={ 0 }
                                onKeyDown={ ( e ) => {
                                    if ( e.key === 'Enter' || e.key === ' ' ) {
                                        navigate( `/return-request/${ item.id }` );
                                    }
                                } }
                                className="font-bold hover:underline"
                            >
                                #{ item.id }
                            </span>
                            { ` ${ __('by', 'dokan') } ` }
                            { item.customer.name }
                            { ` ${ __('on', 'dokan') } ` }
                            <DokanLink
                                href={orderUrl.replace( '%7B%7BORDER_ID%7D%7D', item.order_id )}
                                className="font-bold"
                            >
                                #{ item.order_id }
                            </DokanLink>
                        </>
                    ) }
                </p>
            ),
            enableSorting: false,
            isValid: false
        },
        {
            id: 'items',
            label: __( 'Products', 'dokan' ),
            render: ( { item }: { item: WarrantyRequest } ) => (
                <div key={ `${item.id}_${item.items.length}` }>
                    { isLoading ? (
                        <span className="block w-40 h-3 rounded bg-gray-200 animate-pulse" />
                    ) : (
                        item.items.map( ( product, index ) => (
                            <div key={product.id}>
                                <DokanLink href={ product.url }>
                                    { product.title }
                                </DokanLink>
                                { ` × ` }
                                <strong>
                                    { product.quantity }
                                </strong>
                                { index < item.items.length - 1 && ', ' }
                            </div>
                        ) )
                    ) }
                </div>
            ),
            enableSorting: false,
        },
        {
            id: 'type',
            label: __( 'Type', 'dokan' ),
            render: ( { item }: { item: WarrantyRequest } ) => (
                <p>
                    { isLoading ? (
                        <span className="block w-24 h-3 rounded bg-gray-200 animate-pulse" />
                    ) : (
                        item.type_label
                    ) }
                </p>
            ),
            enableSorting: false,
        },
        {
            id: 'status',
            label: __( 'Status', 'dokan' ),
            render: ( { item }: { item: WarrantyRequest } ) => (
                <p>
                    { isLoading ? (
                        <span className="block w-24 h-3 rounded bg-gray-200 animate-pulse" />
                    ) : (
                        capitalCase( item.status )
                    ) }
                </p>
            ),
            enableSorting: false,
        },
        {
            id: 'created_at',
            label: __( 'Last Updated', 'dokan' ),
            render: ( { item }: { item: WarrantyRequest } ) =>
                isLoading ? (
                    <p>
                        <span className="block w-24 h-3 rounded bg-gray-200 animate-pulse" />
                    </p>
                ) : (
                    <DateTimeHtml.Date date={ item.created_at } />
                ),
            enableSorting: false,
        },
    ];
    const [ view, setView ] = useState( {
        perPage: parseInt( queryParams.get( 'per_page' ) ) || 10,
        page: parseInt( queryParams.get( 'page' ) ) || 1,
        search: '',
        type: 'table',
        status: queryParams.get( 'status' ) || 'all',
        selection: null,
        layout: {
            styles: {
                id: {
                    width: '20%',
                },
                items: {
                    width: '20%',
                },
                type: {
                    width: '20%',
                },
                status: {
                    width: '20%',
                },
                created_at: {
                    width: '20%',
                }
            }
        },
        fields: fields.map( ( field ) => field.id ),
    } );

    const {
        requests,
        isLoading,
        fetchRequests,
        deleteRequest,
        totalItems,
        totalPages,
    } = useWarrantyRequests( filterArgs );

    const { filters, fetchFilters } = useStatusFilters();

    // Tabs configuration
    const tabs = {
        tabs: filters.map( ( f ) => ( {
            label: f.label,
            value: f.name,
        } ) ),
        onSelect: onStatusClick,
        initialTabName: queryParams.get( 'status' ) || 'all',
    };

    // Event Handlers
    function onStatusClick( status: string ) {
        setFilterArgs( ( prevState ) => ( {
            ...prevState,
            page: 1,
        } ) );
        setView( ( prevState ) => ( { ...prevState, page: 1 } ) );
        navigate( {
            pathname: location.pathname,
            search: createSearchParams( {
                status,
                page:'1',
                per_page: String( view.perPage ),
            } ).toString(),
        } );
        void fetchRequests( status );
    }

    const onItemView = ( item: WarrantyRequest ) => {
        navigate( `/return-request/${ item.id }` );
    };

    const onViewChange = ( newView: typeof view ) => {
        setView( newView );
        setFilterArgs( ( prevState ) => ( {
            ...prevState,
            page: newView.page,
            per_page: newView.perPage,
        } ) );

        navigate( {
            pathname: location.pathname,
            search: createSearchParams( {
                status: queryParams.get( 'status' ) || 'all',
                page: String( newView.page ),
                per_page: String( newView.perPage ),
            } ).toString(),
        } );
    };

    const handleDeleteRequest = ( request: WarrantyRequest ) => {
        setDeletingRequest( request );
        setShowModal( true );
    };

    const deleteWarrantyRequest = async () => {
        const response = await deleteRequest( deletingRequest.id );
        if( response?.data?.status !== 200 ){
            toast( {
                type: 'error',
                title: __( 'Failed to delete request', 'dokan' ),
            } );
            return;
        }

        toast( {
            type: 'success',
            title: __( 'Request(s) deleted successfully', 'dokan' ),
        } );

        void fetchRequests( queryParams.get( 'status' ) || 'all' );
        void fetchFilters();
    };

    const actions = [
        {
            id: 'return-request-view',
            label: () => __( 'View', 'dokan' ),
            disabled: isLoading,
            isEligible: ( item: WarrantyRequest ) => !item.is_order_deleted,
            callback: ( [ item ]: [ item: WarrantyRequest ] ) => onItemView( item ),
        },
        {
            id: 'return-request-delete',
            label: () => __( 'Delete', 'dokan' ),
            isDestructive: true,
            disabled: isLoading,
            isEligible: ( item: WarrantyRequest ) => !item.is_order_deleted,
            callback: ( [ item ]: [ item: WarrantyRequest ] ) => {
                handleDeleteRequest( item );
            },
        },
    ];

    // Effects
    useEffect( () => {
        void fetchRequests( queryParams.get( 'status' ) || 'all' );
    }, [ filterArgs ] );

    useEffect( () => {
        void fetchFilters();
    }, [] );

    return (
        <div className="dokan-rma-wrapper">
            <DataViews
                namespace="dokan-rma-requests-data-view"
                data={ requests ?? [] }
                tabs={ tabs }
                fields={ fields }
                search={ false }
                view={ view }
                actions={ actions }
                isLoading={ isLoading }
                paginationInfo={ {
                    totalItems,
                    totalPages,
                } }
                getItemId={ ( item: WarrantyRequest ) => item.id }
                onChangeView={ onViewChange }
                onClickItem={ onItemView }
                isItemClickable={ () => true }
            />

            { deletingRequest && (
                <DokanModal
                    loading={ isLoading }
                    className="min-w-96"
                    isOpen={ showModal }
                    onConfirm={ deleteWarrantyRequest }
                    namespace={ 'dokan-rma-warranty-request-delete' }
                    onClose={ () => setShowModal( false ) }
                    dialogTitle={ sprintf(
                        /* translators: 1) request type */
                        'Delete %s Request', deletingRequest.type_label
                    ) }
                    confirmationTitle={ __( 'Are you sure you want to continue?', 'dokan' ) }
                    confirmationDescription={sprintf(
                        /* translators: 1) opening strong tag, 2) closing strong tag, 3) request type */
                        __(
                            'This action will permanently %1$sdelete%2$s this %3$s request and cannot be undone.',
                            'dokan',
                        ),
                        '<strong>',
                        '</strong>',
                        deletingRequest.type_label,
                    )}
                />
            ) }

            <DokanToaster />
        </div>
    );
}
