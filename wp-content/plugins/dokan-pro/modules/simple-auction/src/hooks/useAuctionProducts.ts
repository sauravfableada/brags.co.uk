import { useState, useCallback, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import type {
    AuctionProduct,
    AuctionFilterState,
    AuctionStatusCount,
    AuctionSummary,
    SubscriptionRemaining,
} from '../types';

interface UseAuctionProductsReturn {
    data: AuctionProduct[];
    isLoading: boolean;
    totalItems: number;
    totalPages: number;
    statusCounts: AuctionStatusCount[];
    subscriptionRemaining: SubscriptionRemaining | null;
    fetchProducts: () => void;
    fetchStatusCounts: () => void;
    deleteProduct: ( id: number ) => Promise< void >;
}

export const useAuctionProducts = (
    filterArgs: AuctionFilterState
): UseAuctionProductsReturn => {
    const [ data, setData ] = useState< AuctionProduct[] >( [] );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ totalItems, setTotalItems ] = useState( 0 );
    const [ totalPages, setTotalPages ] = useState( 0 );
    const [ subscriptionRemaining, setSubscriptionRemaining ] =
        useState< SubscriptionRemaining | null >( null );

    const [ statusCounts, setStatusCounts ] = useState< AuctionStatusCount[] >(
        [
            { value: 'all', label: __( 'All', 'dokan' ), count: 0 },
            { value: 'publish', label: __( 'Published', 'dokan' ), count: 0 },
            { value: 'draft', label: __( 'Draft', 'dokan' ), count: 0 },
            {
                value: 'pending',
                label: __( 'Pending Review', 'dokan' ),
                count: 0,
            },
        ]
    );

    const filterArgsRef = useRef( filterArgs );
    filterArgsRef.current = filterArgs;

    const fetchProducts = useCallback( async () => {
        setIsLoading( true );
        try {
            const args = filterArgsRef.current;
            const queryArgs: Record< string, unknown > = {
                per_page: args.per_page,
                page: args.page,
            };

            if ( args.status !== 'all' ) {
                queryArgs.status = args.status;
            }
            if ( args.search ) {
                queryArgs.search = args.search;
            }
            if ( args.start_date ) {
                queryArgs.start_date = args.start_date;
            }
            if ( args.end_date ) {
                queryArgs.end_date = args.end_date;
            }

            const response = ( await apiFetch( {
                path: addQueryArgs( '/dokan/v1/auction/products', queryArgs ),
                parse: false,
            } ) ) as Response;

            const responseData: AuctionProduct[] = await response.json();
            const total = response.headers.get( 'X-WP-Total' );
            const totalPagesHeader = response.headers.get( 'X-WP-TotalPages' );

            setData( responseData );
            setTotalItems( parseInt( total ?? '0', 10 ) );
            setTotalPages( parseInt( totalPagesHeader ?? '0', 10 ) );
        } catch ( error ) {
            // eslint-disable-next-line no-console
            console.error( 'Error fetching auction products:', error );
            setData( [] );
        } finally {
            setIsLoading( false );
        }
    }, [] );

    const fetchStatusCounts = useCallback( async () => {
        try {
            const response = ( await apiFetch( {
                path: '/dokan/v1/auction/products/summary',
            } ) ) as AuctionSummary;

            const counts = response.post_counts ?? {};
            const allCount =
                ( counts.publish ?? 0 ) +
                ( counts.draft ?? 0 ) +
                ( counts.pending ?? 0 ) +
                ( counts.reject ?? 0 );

            setStatusCounts( [
                { value: 'all', label: __( 'All', 'dokan' ), count: allCount },
                {
                    value: 'publish',
                    label: __( 'Published', 'dokan' ),
                    count: counts.publish ?? 0,
                },
                {
                    value: 'draft',
                    label: __( 'Draft', 'dokan' ),
                    count: counts.draft ?? 0,
                },
                {
                    value: 'pending',
                    label: __( 'Pending Review', 'dokan' ),
                    count: counts.pending ?? 0,
                },
                {
                    value: 'reject',
                    label: __( 'Rejected', 'dokan' ),
                    count: counts.reject ?? 0,
                },
            ] );

            setSubscriptionRemaining( response.subscription_remaining ?? null );
        } catch ( error ) {
            // eslint-disable-next-line no-console
            console.error( 'Error fetching auction summary:', error );
        }
    }, [] );

    const deleteProduct = useCallback( async ( id: number ) => {
        await apiFetch( {
            path: `/dokan/v1/auction/products/${ id }`,
            method: 'DELETE',
        } );
    }, [] );

    useEffect( () => {
        void fetchProducts();
    }, [
        fetchProducts,
        filterArgs.page,
        filterArgs.per_page,
        filterArgs.status,
        filterArgs.search,
        filterArgs.start_date,
        filterArgs.end_date,
    ] );

    useEffect( () => {
        void fetchStatusCounts();
    }, [ fetchStatusCounts ] );

    return {
        data,
        isLoading,
        totalItems,
        totalPages,
        statusCounts,
        subscriptionRemaining,
        fetchProducts,
        fetchStatusCounts,
        deleteProduct,
    };
};
