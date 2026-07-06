// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { DataViews, DokanBadge, ShortContent } from '@dokan/components';
import { __, _n } from '@wordpress/i18n';
import { DokanToaster, useToast } from '@getdokan/dokan-ui';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Question } from '../types';
import { useSelect } from '@wordpress/data';
import coreStore from '@dokan/stores/core';
import { addQueryArgs } from '@wordpress/url';
import ProductFilter from './ProductFilter';
import { redirectToEditProduct } from '../utils';

type ProductQAStatus = 'all' | 'answered' | 'unanswered';

interface StatusCount {
    key: string;
    label: string;
    count: number;
}

interface FilterState {
    page: number;
    per_page: number;
    status: ProductQAStatus;
    product_id: number;
}

const DEFAULT_STATUS_COUNTS: StatusCount[] = [
    {
        key: 'all',
        label: __( 'All', 'dokan' ),
        count: 0,
    },
    {
        key: 'answered',
        label: __( 'Answered', 'dokan' ),
        count: 0,
    },
    {
        key: 'unanswered',
        label: __( 'Unanswered', 'dokan' ),
        count: 0,
    },
];

type ProductQAListProps = {
    navigate?: ( path: string ) => void;
};

export default function ProductQAList( { navigate }: ProductQAListProps ) {
    const currentUser = useSelect( ( select ) => {
        return select( coreStore ).getCurrentUser();
    }, [] );
    const vendorId = useSelect( ( select ) => {
        return select( coreStore ).getVendorId();
    }, [] );

    const toast = useToast();
    const [ questions, setQuestions ] = useState< Question[] >( [] );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ totalItems, setTotalItems ] = useState( 0 );
    const [ selectedProduct, setSelectedProduct ] = useState( null );
    const [ statusCounts, setStatusCounts ] = useState< StatusCount[] >(
        DEFAULT_STATUS_COUNTS
    );

    const [ selection, setSelection ] = useState< string[] >( [] );
    const [ filterArgs, setFilterArgs ] = useState< FilterState >( {
        page: 1,
        per_page: 10,
        status: 'all',
        product_id: 0,
    } );

    const [ view, setView ] = useState( {
        page: 1,
        perPage: 10,
        type: 'table',
        status: 'all',
        fields: [ 'question', 'product_id', 'status', 'created_at' ],
        layout: {
            styles: {
                question: { width: '40%' },
                product_id: { width: '30%' },
                status: { width: '15%' },
                created_at: { width: '15%' },
            },
        },
    } );

    const navigateToQuestion = ( questionId: number ) => {
        if ( navigate ) {
            navigate( `/product-questions-answers/${ questionId }` );
        }
    };

    const fields = [
        {
            id: 'question',
            label: __( 'Question', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: Question } ) => (
                <div>
                    { /* eslint-disable-next-line */ }
                    <div
                        role="button"
                        onClick={ () => navigateToQuestion( item.id ) }
                        className="font-bold"
                    >
                        <ShortContent
                            content={ item.question }
                            maxLength={ 48 }
                            useRawHTML={ false }
                        />
                    </div>
                    <small className="text-xs">
                        { __( 'by', 'dokan' ) } { item.user_display_name }
                    </small>
                </div>
            ),
        },
        {
            id: 'product_id',
            label: __( 'Product', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: Question } ) => (
                <div className="font-bold flex gap-2 items-center">
                    <img
                        src={ item.product.image }
                        alt={ item.product.title }
                        className="w-14 h-14 rounded object-cover shrink-0"
                    />
                    <a
                        href={ redirectToEditProduct(
                            String( item.product.id )
                        ) }
                    >
                        <ShortContent
                            className="m-0 space-x-2 flex flex-wrap text-nowrap leading-6 text-sm text-gray-600"
                            content={ item.product.title }
                            maxLength={ 32 }
                            useRawHTML={ false }
                        />
                    </a>
                </div>
            ),
        },
        {
            id: 'status',
            label: __( 'Status', 'dokan' ),
            render: ( { item }: { item: Question } ) => (
                <DokanBadge
                    variant={ item.answer?.answer ? 'success' : 'info' }
                    label={
                        item.answer?.answer
                            ? __( 'Answered', 'dokan' )
                            : __( 'Unanswered', 'dokan' )
                    }
                />
            ),
        },
        {
            id: 'created_at',
            label: __( 'Date', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: Question } ) => item.created_at,
        },
    ];

    const actions = [
        {
            id: 'post-edit',
            label: () => __( 'View', 'dokan' ),
            disabled: isLoading,
            callback: ( [ item ]: Question[] ) => {
                navigateToQuestion( item.id );
            },
        },
        {
            id: 'post-delete',
            label: __( 'Delete', 'dokan' ),
            isDestructive: true,
            supportsBulk: true,
            disabled: isLoading,
            callback: ( items: Question[] ) => {
                void deleteQuestionsHandler( items );
            },
        },
    ];

    const fetchQuestions = async () => {
        if ( ! currentUser?.id ) {
            return;
        }
        setIsLoading( true );
        try {
            const queryArgs: Record< string, any > = {
                per_page: filterArgs.per_page,
                page: filterArgs.page,
                vendor_id: vendorId,
                order: 'DESC',
            };

            if ( filterArgs.status === 'answered' ) {
                queryArgs.answered = true;
            } else if ( filterArgs.status === 'unanswered' ) {
                queryArgs.answered = false;
            }

            if ( filterArgs.product_id ) {
                queryArgs.product_id = filterArgs.product_id;
            }

            const path = addQueryArgs(
                '/dokan/v1/product-questions',
                queryArgs
            );
            const response = await apiFetch< any >( {
                path,
                parse: false,
            } );
            const data: Question[] = await response.json();
            setQuestions( data );

            const headers = response.headers;
            setTotalItems( parseInt( headers.get( 'X-WP-Total' ) || '0' ) );
            setStatusCounts( [
                {
                    key: 'all',
                    label: __( 'All', 'dokan' ),
                    count: parseInt( headers.get( 'X-Status-All' ) || '0' ),
                },
                {
                    key: 'answered',
                    label: __( 'Answered', 'dokan' ),
                    count: parseInt(
                        headers.get( 'X-Status-Answered' ) || '0'
                    ),
                },
                {
                    key: 'unanswered',
                    label: __( 'Unanswered', 'dokan' ),
                    count: parseInt(
                        headers.get( 'X-Status-Unanswered' ) || '0'
                    ),
                },
            ] );
        } catch ( error ) {
            toast( {
                type: 'error',
                title: __( 'Failed to fetch questions', 'dokan' ),
            } );
        } finally {
            setIsLoading( false );
        }
    };

    useEffect( () => {
        void fetchQuestions();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [ filterArgs ] );

    const onStatusClick = ( status: string ) => {
        setFilterArgs( ( prev ) => ( {
            ...prev,
            status: status as ProductQAStatus,
            page: 1,
        } ) );
        setView( ( prev ) => ( { ...prev, page: 1 } ) );
    };

    const onViewChange = ( newView: typeof view ) => {
        setView( newView );
        setFilterArgs( ( prev ) => ( {
            ...prev,
            page: newView.page,
            per_page: newView.perPage,
        } ) );
    };

    const deleteQuestionsHandler = async ( items: Question[] ) => {
        try {
            await apiFetch( {
                path: '/dokan/v1/product-questions/bulk_action',
                method: 'PUT',
                data: {
                    action: 'delete',
                    ids: items.map( ( item ) => item.id ),
                },
            } );
            toast( {
                type: 'success',
                title: _n(
                    'Question deleted successfully',
                    'Questions deleted successfully',
                    items.length,
                    'dokan'
                ),
            } );
            setSelection( [] );
            void fetchQuestions();
        } catch ( error ) {
            toast( {
                type: 'error',
                title: _n(
                    'Failed to delete question',
                    'Failed to delete questions',
                    items.length,
                    'dokan'
                ),
            } );
        }
    };

    const tabs = {
        items: statusCounts.map( ( s ) => ( { ...s, value: s.key } ) ),
        onSelect: onStatusClick,
    };

    const filter = {
        fields: [
            {
                id: 'product',
                label: __( 'Product', 'dokan' ),
                field: (
                    <ProductFilter
                        selectProduct={ selectedProduct }
                        onChange={ ( item: any ) => {
                            setSelectedProduct( item );
                            setFilterArgs( ( prev ) => ( {
                                ...prev,
                                product_id: item?.value || 0,
                                page: 1,
                            } ) );
                            setView( ( prev ) => ( {
                                ...prev,
                                page: 1,
                            } ) );
                        } }
                    />
                ),
            },
        ],
        onReset: () => {
            setSelectedProduct( null );
            setFilterArgs( ( prev ) => ( {
                ...prev,
                product_id: 0,
                page: 1,
            } ) );
            setView( ( prev ) => ( { ...prev, page: 1 } ) );
        },
        onFilterRemove: ( filterId: string ) => {
            if ( filterId === 'product' ) {
                setSelectedProduct( null );
                setFilterArgs( ( prev ) => ( {
                    ...prev,
                    product_id: 0,
                    page: 1,
                } ) );
                setView( ( prev ) => ( { ...prev, page: 1 } ) );
            }
        },
    };

    return (
        <>
            <DataViews
                namespace="dokan-product-qa-data-view"
                data={ questions }
                tabs={ tabs }
                filter={ filter }
                fields={ fields }
                view={ view }
                actions={ actions }
                isLoading={ isLoading }
                paginationInfo={ {
                    totalItems,
                    totalPages: Math.ceil( totalItems / view.perPage ),
                } }
                getItemId={ ( item: Question ) => item.id }
                onChangeView={ onViewChange }
                selection={ selection }
                onChangeSelection={ ( ids: string[] ) =>
                    setSelection( ids )
                }
            />
            <DokanToaster />
        </>
    );
}
