import domReady from '@wordpress/dom-ready';
import React, { useState, useEffect, useMemo } from '@wordpress/element';
import {
    VendorAsyncSelect,
    ProductAsyncSelect,
    DataViews,
    DokanTooltip,
    getActionLabel,
} from '@dokan/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { twMerge } from 'tailwind-merge';
import { DokanToaster, useToast } from '@getdokan/dokan-ui';
import { truncate } from '@dokan/utilities';
import ProductQASingle from './ProductQASingle';
import { House, Box, BookOpenCheck, BookOpenText, Trash } from 'lucide-react';

interface Vendor {
    id: number;
    name: string;
    store_name?: string;
    avatar: string;
}

interface Product {
    id: number;
    title: string;
    name?: string;
    image: string;
}

interface Answer {
    id: number;
    answer: string;
}

interface Question {
    id: number;
    question: string;
    product: Product;
    vendor: Vendor;
    human_readable_created_at: string;
    status: string;
    read: boolean;
    answer: Answer;
}

interface QuestionCounts {
    all: number;
    read: number;
    unread: number;
    answered: number;
    unanswered: number;
}

const ProductQAList = ( {
    navigate,
}: {
    navigate?: ( path: string ) => void;
} ) => {
    const toast = useToast();
    const [ questions, setQuestions ] = useState< Question[] >( [] );
    const [ loading, setLoading ] = useState< boolean >( false );
    const [ counts, setCounts ] = useState< QuestionCounts >( {
        all: 0,
        read: 0,
        unread: 0,
        answered: 0,
        unanswered: 0,
    } );
    const [ totalPages, setTotalPages ] = useState< number >( 1 );
    const [ totalItems, setTotalItems ] = useState< number >( 0 );
    const [ selection, setSelection ] = useState< number[] >( [] );
    const [ searchValue, setSearchValue ] = useState< string >( '' );
    const [ status, setStatus ] = useState< string >( 'all' );
    const [ filterArgs, setFilterArgs ] = useState< any >( {} );
    const [ vendorFilter, setVendorFilter ] = useState< any >( null );
    const [ productFilter, setProductFilter ] = useState< any >( null );

    const defaultLayouts = {
        table: {},
        grid: {},
        list: {},
        density: 'comfortable' as const,
    };

    const [ view, setView ] = useState< any >( {
        perPage: 20,
        page: 1,
        type: 'table',
        layout: {
            ...defaultLayouts,
            styles: {
                question: { width: '30%' },
                product: { width: '22%' },
                vendor: { width: '18%' },
                date: { width: '15%' },
                status: { width: '15%' },
            },
        },
        fields: [ 'question', 'product', 'vendor', 'date', 'status' ],
    } );

    const productUrl = ( productId: number ) => {
        return `/wp-admin/post.php?post=${ productId }&action=edit`;
    };
    const fetchQuestions = async () => {
        setLoading( true );
        try {
            const data: any = {
                per_page: view.perPage,
                page: view.page,
                ...filterArgs,
            };

            if ( searchValue ) {
                data.search = searchValue;
            }

            if (
                status !== 'all' &&
                ( status === 'answered' || status === 'unanswered' )
            ) {
                data.answered = status === 'answered';
            }

            if (
                status !== 'all' &&
                ( status === 'read' || status === 'unread' )
            ) {
                data.read = status === 'read';
            }

            const path = addQueryArgs( '/dokan/v1/product-questions', data );
            const response = await apiFetch( {
                path,
                parse: false,
            } );

            const responseData: Question[] = await response.json();
            const total = parseInt(
                response.headers.get( 'X-WP-Total' ) || '0'
            );
            const pages = parseInt(
                response.headers.get( 'X-WP-TotalPages' ) || '1'
            );

            setCounts( {
                all: parseInt( response.headers.get( 'X-Status-All' ) || '0' ),
                unread: parseInt(
                    response.headers.get( 'X-Status-Unread' ) || '0'
                ),
                read: parseInt(
                    response.headers.get( 'X-Status-Read' ) || '0'
                ),
                answered: parseInt(
                    response.headers.get( 'X-Status-Answered' ) || '0'
                ),
                unanswered: parseInt(
                    response.headers.get( 'X-Status-Unanswered' ) || '0'
                ),
            } );

            setQuestions( responseData || [] );
            setTotalItems( total );
            setTotalPages( pages );
        } catch ( error: any ) {
            if ( error?.message ) {
                toast( {
                    type: 'error',
                    title: error?.message,
                } );
            }
        } finally {
            setLoading( false );
        }
    };

    const handleBulkAction = async ( action: string, itemIds: number[] ) => {
        if ( ! itemIds.length ) {
            return;
        }

        try {
            await apiFetch( {
                path: '/dokan/v1/product-questions/bulk_action',
                method: 'PUT',
                data: { action, ids: itemIds },
            } );

            toast( {
                type: 'success',
                title: __( 'Action completed successfully', 'dokan' ),
            } );

            setSelection( [] );
            await fetchQuestions();
        } catch ( error: any ) {
            if ( error?.message ) {
                toast( {
                    type: 'error',
                    title: error?.message,
                } );
            }
        }
    };

    const handleSearch = ( query: string ) => {
        setSearchValue( query );
        setView( { ...view, page: 1 } );
    };

    const handleStatusChange = ( value: string ) => {
        setStatus( value );
        setView( { ...view, page: 1 } );
        setSelection( [] );
    };

    const clearSingleFilter = ( filterId: string ) => {
        const args = { ...filterArgs };
        switch ( filterId ) {
            case 'vendor':
                setVendorFilter( null );
                delete args.vendor_id;
                break;
            case 'product':
                setProductFilter( null );
                delete args.product_id;
                break;
            default:
                break;
        }
        setFilterArgs( args );
    };

    const clearFilter = () => {
        setVendorFilter( null );
        setProductFilter( null );
        setFilterArgs( {} );
    };

    const filterFields = [
        {
            id: 'vendor',
            label: __( 'Vendor', 'dokan' ),
            field: (
                <VendorAsyncSelect
                    icon={ <House size={ 16 } /> }
                    key="vendor-select"
                    value={ vendorFilter }
                    onChange={ ( selectedVendorObj: any ) => {
                        const args = { ...filterArgs };
                        delete args.vendor_id;
                        if ( selectedVendorObj ) {
                            args.vendor_id = selectedVendorObj.value;
                        }
                        setVendorFilter( selectedVendorObj );
                        setFilterArgs( args );
                    } }
                    placeholder={ __( 'Select Vendor', 'dokan' ) }
                />
            ),
        },
        {
            id: 'product',
            label: __( 'Product', 'dokan' ),
            field: (
                <ProductAsyncSelect
                    icon={ <Box size={ 16 } /> }
                    key="product-select"
                    value={ productFilter }
                    onChange={ ( selectedProductObj: any ) => {
                        const args = { ...filterArgs };
                        delete args.product_id;
                        if ( selectedProductObj ) {
                            args.product_id = selectedProductObj.value;
                        }
                        setProductFilter( selectedProductObj );
                        setFilterArgs( args );
                    } }
                    placeholder={ __( 'Select Product', 'dokan' ) }
                />
            ),
        },
    ];

    const fields = [
        {
            id: 'question',
            label: __( 'Question', 'dokan' ),
            enableHiding: false,
            enableSorting: false,
            render: ( { item }: { item: Question } ) => {
                return (
                    <div
                        className="cursor-pointer hover:text-dokan-link"
                        onClick={ () => navigate( `/product-qa/${ item.id }` ) }
                    >
                        { item.question?.length > 40 ? (
                            <DokanTooltip content={ item.question }>
                                { item.read ? (
                                    <span className="text-sm text-[#575757]">
                                        { truncate( item.question, 40 ) }
                                    </span>
                                ) : (
                                    <strong className="text-sm">
                                        { truncate( item.question, 40 ) }
                                    </strong>
                                ) }
                            </DokanTooltip>
                        ) : item.read ? (
                            <span className="text-sm text-[#575757]">
                                { item.question }
                            </span>
                        ) : (
                            <strong className="text-sm">
                                { item.question }
                            </strong>
                        ) }
                    </div>
                );
            },
        },
        {
            id: 'product',
            label: __( 'Product', 'dokan' ),
            enableHiding: false,
            enableSorting: false,
            render: ( { item }: { item: Question } ) => {
                return (
                    <a href={ productUrl( item.product.id ) }>
                        <div className="flex items-center space-x-2">
                            <img
                                src={ item.product.image }
                                alt={ item.product.title }
                                className="w-8 h-8 rounded object-cover"
                            />
                            { item.product.title?.length > 22 ? (
                                <DokanTooltip content={ item.product.title }>
                                    <span className="text-[#575757] hover:text-[#7047EB]">
                                        { truncate( item.product.title, 22 ) }
                                    </span>
                                </DokanTooltip>
                            ) : (
                                <span className="text-[#575757] hover:text-[#7047EB]">
                                    { item.product.title ??
                                        __( '(no name)', 'dokan' ) }
                                </span>
                            ) }
                        </div>
                    </a>
                );
            },
        },
        {
            id: 'vendor',
            label: __( 'Vendor', 'dokan' ),
            enableHiding: false,
            enableSorting: false,
            render: ( { item }: { item: Question } ) => {
                return (
                    <div
                        className="flex items-center space-x-2"
                        onClick={ () =>
                            navigate( `/vendors/${ item.vendor.id }` )
                        }
                    >
                        { item.vendor.name?.length > 22 ? (
                            <DokanTooltip content={ item.vendor.name }>
                                <span className="text-[#575757] hover:text-[#7047EB]">
                                    { truncate( item.vendor.name, 22 ) }
                                </span>
                            </DokanTooltip>
                        ) : (
                            <span className="text-[#575757] hover:text-[#7047EB]">
                                { item.vendor.name }
                            </span>
                        ) }
                    </div>
                );
            },
        },
        {
            id: 'date',
            label: __( 'Date', 'dokan' ),
            enableHiding: false,
            enableSorting: false,
            render: ( { item }: { item: Question } ) => (
                <span className="text-sm text-gray-600">
                    { item.human_readable_created_at }
                </span>
            ),
        },
        {
            id: 'status',
            label: __( 'Status', 'dokan' ),
            enableHiding: false,
            enableSorting: false,
            render: ( { item }: { item: Question } ) => {
                const isAnswered = item.answer?.id;
                return (
                    <span
                        className={ twMerge(
                            'inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-medium',
                            isAnswered
                                ? 'bg-green-50 text-green-700 #D4FBEF'
                                : 'bg-red-50 text-red-700 ring-red-600/20'
                        ) }
                    >
                        { isAnswered
                            ? __( 'Answered', 'dokan' )
                            : __( 'Unanswered', 'dokan' ) }
                    </span>
                );
            },
        },
    ];

    const actions = [
        {
            id: 'product-qa-read',
            label: () => getActionLabel( <BookOpenCheck size={ 16 } className="fill-none!" />, __( 'Mark as Read', 'dokan' ) ),
            icon: <BookOpenCheck size={ 16 } className="fill-none!" />,
            isPrimary: false,
            supportsBulk: true,
            isEligible: ( item: Question ) =>
                status === 'all' && item.status !== 'read',
            callback: ( items: Question[] ) => {
                void handleBulkAction(
                    'read',
                    items.map( ( a ) => a.id )
                );
            },
        },
        {
            id: 'product-qa-unread',
            label: () => getActionLabel( <BookOpenText size={ 16 } className="fill-none!" />, __( 'Mark as Unread', 'dokan' ) ),
            icon: <BookOpenText size={ 16 } className="fill-none!" />,
            isPrimary: false,
            supportsBulk: true,
            isEligible: ( item: Question ) =>
                ( status === 'all' || status === 'read' ) &&
                item.status !== 'unread',
            callback: ( items: Question[] ) => {
                void handleBulkAction(
                    'unread',
                    items.map( ( a ) => a.id )
                );
            },
        },
        {
            id: 'product-qa-delete',
            label: () => getActionLabel( <Trash size={ 16 } className="fill-none!" />, __( 'Delete', 'dokan' ) ),
            icon: <Trash size={ 16 } className="fill-none!" />,
            isPrimary: false,
            supportsBulk: true,
            isEligible: () => true,
            isDestructive: true,
            confirmTone: 'destructive',
            confirmTitle: __( 'Delete Question', 'dokan' ),
            confirmMessage: __(
                'Are you sure you want to delete the selected question(s)? This action cannot be undone.',
                'dokan'
            ),
            confirmButtonLabel: __( 'Delete', 'dokan' ),
            cancelButtonLabel: __( 'Cancel', 'dokan' ),
            callback: ( items: Question[] ) => {
                void handleBulkAction(
                    'delete',
                    items.map( ( a ) => a.id )
                );
            },
        },
    ];

    const tabItems = useMemo(
        () => [
            { value: 'all', label: __( 'All', 'dokan' ), count: counts.all },
            {
                value: 'unread',
                label: __( 'Unread', 'dokan' ),
                count: counts.unread,
            },
            { value: 'read', label: __( 'Read', 'dokan' ), count: counts.read },
            {
                value: 'unanswered',
                label: __( 'Unanswered', 'dokan' ),
                count: counts.unanswered,
            },
            {
                value: 'answered',
                label: __( 'Answered', 'dokan' ),
                count: counts.answered,
            },
        ],
        [ counts ]
    );

    useEffect( () => {
        fetchQuestions();
    }, [ view.perPage, view.page, status, searchValue, filterArgs ] );

    return (
        <div className="product-qa-wrapper">
            <h2 className="text-2xl leading-3 text-gray-900 font-bold mb-6 pt-8">
                { __( 'Product Q&A', 'dokan' ) }
            </h2>

            <div className="dokan-admin-dashboard-datatable">
                <DataViews< Question >
                    namespace="dokan-product-qa-list-table"
                    view={ view }
                    onChangeView={ setView }
                    fields={ fields }
                    data={ questions }
                    isLoading={ loading }
                    actions={ actions }
                    selection={ selection.map( String ) }
                    onChangeSelection={ ( ids ) =>
                        setSelection( ids.map( ( id ) => Number( id ) ) )
                    }
                    paginationInfo={ { totalItems, totalPages } }
                    defaultLayouts={ defaultLayouts }
                    tabs={ {
                        items: tabItems,
                        onSelect: handleStatusChange,
                        defaultValue: status,
                    } }
                    filter={ {
                        fields: filterFields,
                        onFilterRemove: ( filterId: string ) =>
                            clearSingleFilter( filterId ),
                        onReset: () => clearFilter(),
                    } }
                    getItemId={ ( item ) => item.id.toString() }
                />
            </div>

            <DokanToaster />
        </div>
    );
};

domReady( () => {
    // @ts-ignore
    window.wp.hooks.addFilter(
        'dokan-admin-dashboard-routes',
        'dokan-admin-product-qa',
        ( routes: any[] = [] ) => {
            routes.push( {
                id: 'product-qa',
                path: '/product-qa',
                element: <ProductQAList />,
            } );
            return routes;
        }
    );

    // @ts-ignore
    window.wp.hooks.addFilter(
        'dokan-admin-dashboard-routes',
        'dokan-admin-product-qa-single',
        ( routes: any[] = [] ) => {
            routes.push( {
                id: 'product-qa-single',
                path: '/product-qa/:id',
                element: <ProductQASingle />,
            } );
            return routes;
        }
    );
} );

export default ProductQAList;
