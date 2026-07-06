import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { useEffect, useMemo, useState } from '@wordpress/element';
import {
    DokanSubscription,
    ApiResponse,
    FetchError,
    SubscriptionOrdersQueryArgs,
    SubscriptionOrdersState,
    SubscriptionStatusHookReturn,
    SubscriptionStatuses,
    CombinedSubscriptionData,
    DownloadPermissionsResponse,
} from '../Types';
import { useNotes } from './useNotes';

export const fetchSubscriptionData = async (
    subscriptionId: string | number
): Promise< ApiResponse > => {
    const response: Record< any, any > = await apiFetch( {
        method: 'GET',
        path: addQueryArgs(
            `/dokan/v1/product-subscriptions/${ subscriptionId }`,
            {}
        ),
        parse: false,
    } );

    const body = await response.json();
    return { body };
};
export const fetchSubscriptionDownloadableProducts = async (
    subscriptionId: string | number
): Promise< ApiResponse > => {
    const response: Record< any, any > = await apiFetch( {
        method: 'GET',
        path: addQueryArgs(
            `/dokan/v3/orders/${ subscriptionId }/downloads`,
            {}
        ),
        parse: false,
    } );

    const body: DownloadPermissionsResponse = await response.json();
    return { body };
};

// Alternative: Custom Hook version
interface UseSubscriptionDataReturn {
    data: DokanSubscription | null;
    setData: ( data: DokanSubscription ) => void;
    isLoading: boolean;
    error: FetchError | null;
    refreshSub: () => Promise< void >;
}

export const useSubscriptionData = (
    subscriptionId: string
): UseSubscriptionDataReturn => {
    const [ data, setData ] = useState< DokanSubscription | null >( null );
    const [ isLoading, setIsLoading ] = useState< boolean >( false );
    const [ error, setError ] = useState< FetchError | null >( null );

    const loadData = async () => {
        setIsLoading( true );
        setError( null );

        try {
            const response = await fetchSubscriptionData( subscriptionId );
            setData( response.body );
        } catch ( err ) {
            setError( err );
            console.error( 'Error fetching data:', err );
        } finally {
            setIsLoading( false );
        }
    };

    useEffect( () => {
        let isMounted = true;

        const fetchData = async () => {
            setIsLoading( true );
            setError( null );

            try {
                const response = await fetchSubscriptionData( subscriptionId );

                if ( isMounted ) {
                    setData( response.body );
                }
            } catch ( err ) {
                if ( isMounted ) {
                    setError( err );
                    console.error( 'Error fetching data:', err );
                }
            } finally {
                if ( isMounted ) {
                    setIsLoading( false );
                }
            }
        };

        fetchData();

        return () => {
            isMounted = false;
        };
    }, [ subscriptionId ] );

    const refreshSub = async () => {
        await loadData();
    };

    return { data, setData, isLoading, error, refreshSub };
};
// Fetch function using wp.apiFetch
const fetchSubscriptionOrders = async (
    subscriptionId: number,
    args: SubscriptionOrdersQueryArgs
) => {
    try {
        const response: Record< any, any > = await apiFetch( {
            path: `/dokan/v1/product-subscriptions/${ subscriptionId }/orders`,
            method: 'GET',
            parse: false,
            // @ts-ignore
            params: {
                ...args,
            },
        } );

        return {
            data: await response.json(),
            headers: response?.headers,
            status: response?.status,
        };
    } catch ( error ) {
        throw error;
    }
};

// Custom hook
export const useSubscriptionOrders = (
    subscriptionId: number,
    args: SubscriptionOrdersQueryArgs = {}
) => {
    const [ state, setState ] = useState< SubscriptionOrdersState >( {
        orders: [],
        isLoading: true,
        error: null,
        totalItems: 0,
        totalPages: 0,
        status: 200,
    } );

    // Memoize query parameters
    const queryArgs = useMemo(
        () => ( {
            context: args.context || 'view',
            page: args.page || 1,
            per_page: args.per_page || 10,
            search: args.search,
            after: args.after,
            before: args.before,
            modified_after: args.modified_after,
            modified_before: args.modified_before,
            dates_are_gmt: args.dates_are_gmt,
            exclude: args.exclude,
            include: args.include,
            offset: args.offset,
            order: args.order || 'desc',
            orderby: args.orderby || 'date',
            parent: args.parent,
            parent_exclude: args.parent_exclude,
            status: args.status || 'any',
            customer: args.customer,
            product: args.product,
            dp: args.dp || 2,
            order_item_display_meta: args.order_item_display_meta || false,
            include_meta: args.include_meta,
            exclude_meta: args.exclude_meta,
        } ),
        [
            args.context,
            args.page,
            args.per_page,
            args.search,
            args.after,
            args.before,
            args.modified_after,
            args.modified_before,
            args.dates_are_gmt,
            args.exclude?.toString(),
            args.include?.toString(),
            args.offset,
            args.order,
            args.orderby,
            args.parent?.toString(),
            args.parent_exclude?.toString(),
            args.status,
            args.customer,
            args.product,
            args.dp,
            args.order_item_display_meta,
            args.include_meta?.toString(),
            args.exclude_meta?.toString(),
        ]
    );

    // Memoize orders
    const orders = useMemo( () => state.orders, [ state.orders ] );

    useEffect( () => {
        let isMounted = true;

        const loadOrders = async () => {
            setState( ( prev ) => ( {
                ...prev,
                isLoading: true,
                error: null,
            } ) );

            try {
                const response: Record< any, any > =
                    await fetchSubscriptionOrders( subscriptionId, queryArgs );

                if ( isMounted ) {
                    setState( {
                        orders: response.data,
                        isLoading: false,
                        error: null,
                        totalItems: parseInt(
                            response.headers?.[ 'X-WP-Total' ] || '0',
                            10
                        ),
                        totalPages: parseInt(
                            response.headers?.[ 'X-WP-TotalPages' ] || '0',
                            10
                        ),
                        status: response.status || 200,
                    } );
                }
            } catch ( error ) {
                if ( isMounted ) {
                    setState( ( prev ) => ( {
                        ...prev,
                        isLoading: false,
                        error:
                            error instanceof Error
                                ? error
                                : new Error( 'Failed to fetch orders' ),
                        status: error.status || 500,
                    } ) );
                }
            }
        };

        loadOrders();

        return () => {
            isMounted = false;
        };
    }, [ subscriptionId, queryArgs ] );

    return {
        orders,
        isLoading: state.isLoading,
        error: state.error,
        totalItems: state.totalItems,
        totalPages: state.totalPages,
        status: state.status,
        refresh: () => {
            setState( ( prev ) => ( { ...prev, isLoading: true } ) );
        },
    };
};

// Hook for fetching subscription statuses
export const useSubscriptionStatus = (): SubscriptionStatusHookReturn => {
    const [ statuses, setStatuses ] = useState< SubscriptionStatuses | null >(
        null
    );

    const getPermittedStatuses = ( currectStatus: string = '' ) => {
        let deepClonedStatuses = structuredClone( statuses );

        delete deepClonedStatuses[ 'wc-switched' ];

        if ( ! currectStatus ) {
            return deepClonedStatuses;
        }

        currectStatus = `wc-${ currectStatus }`;

        switch ( currectStatus ) {
            case 'wc-active':
                delete deepClonedStatuses[ 'wc-pending' ];
                break;

            case 'wc-switched':
                // @ts-ignore
                deepClonedStatuses = {};
                break;

            case 'wc-cancelled':
                // @ts-ignore
                deepClonedStatuses = {};
                break;

            case 'wc-expired':
                // @ts-ignore
                deepClonedStatuses = {};
                break;

            case 'wc-pending-cancel':
                delete deepClonedStatuses[ 'wc-active' ];
                delete deepClonedStatuses[ 'wc-on-hold' ];
                delete deepClonedStatuses[ 'wc-pending' ];
                break;

            case 'wc-on-hold':
                delete deepClonedStatuses[ 'wc-active' ];
                delete deepClonedStatuses[ 'wc-pending' ];
                break;

            case 'wc-pending':
                delete deepClonedStatuses[ 'wc-pending-cancel' ];
                break;
        }

        // @ts-ignore
        return wp.hooks.applyFilters(
            'dokan_subscription_details_get_permitted_statuses',
            deepClonedStatuses
        );
    };

    const [ isLoading, setIsLoading ] = useState< boolean >( true );
    const [ error, setError ] = useState< Error | null >( null );

    const fetchStatuses = async () => {
        setIsLoading( true );
        setError( null );

        try {
            const response: SubscriptionStatuses = await apiFetch( {
                path: '/dokan/v1/product-subscriptions/statuses',
                method: 'GET',
            } );
            setStatuses( response );
        } catch ( err ) {
            setError(
                err instanceof Error
                    ? err
                    : new Error( 'Failed to fetch subscription statuses' )
            );
        } finally {
            setIsLoading( false );
        }
    };

    useEffect( () => {
        fetchStatuses();
    }, [] );

    const refresh = () => {
        fetchStatuses();
    };

    return {
        statuses,
        isLoading,
        error,
        refresh,
        getPermittedStatuses,
    };
};

export const useSubscriptionWithOrders = (
    subscriptionId: string | number,
    orderArgs: SubscriptionOrdersQueryArgs = {}
): CombinedSubscriptionData => {
    // Use existing subscription data hook
    const {
        data: subscription,
        isLoading: subscriptionLoading,
        error: subscriptionError,
    } = useSubscriptionData( String( subscriptionId ) );

    // Use existing orders hook
    const {
        orders,
        isLoading: ordersLoading,
        error: ordersError,
        totalItems,
        totalPages,
        refresh: refreshOrders,
    } = useSubscriptionOrders( Number( subscriptionId ), orderArgs );

    // Combined loading state
    const isLoading = subscriptionLoading || ordersLoading;

    // Combined error state (prioritize subscription error)
    const error = subscriptionError || ordersError;

    // Memoized refresh function that triggers both hooks' refresh mechanisms
    const refresh = useMemo( () => {
        return () => {
            // Currently useSubscriptionData doesn't expose a refresh method
            // If it did, we would call it here along with refreshOrders
            refreshOrders();
        };
    }, [ refreshOrders ] );

    return {
        subscription,
        orders,
        isLoading,
        error,
        totalOrders: totalItems,
        totalPages,
        refresh,
    };
};

// Combined hook for subscription data, orders, and statuses
export const useSubscriptionDetails = (
    subscriptionId: string | number,
    orderArgs: SubscriptionOrdersQueryArgs = {}
): CombinedSubscriptionData => {
    // Use existing subscription data hook
    const {
        data: subscription,
        setData: setSubscription,
        isLoading: subscriptionLoading,
        error: subscriptionError,
        refreshSub,
    } = useSubscriptionData( String( subscriptionId ) );

    // Use existing orders hook
    const {
        orders,
        isLoading: ordersLoading,
        error: ordersError,
        totalItems,
        totalPages,
        refresh: refreshOrders,
        status,
    } = useSubscriptionOrders( Number( subscriptionId ), orderArgs );

    const {
        data: downloadableProducts,
        isLoading: downloadableProductsLoading,
        error: downloadableProductsError,
        refresh: refreshDownloadsProducts,
    } = useSubscriptionDownloadsProducts( String( subscriptionId ) );

    // Use new status hook
    const {
        statuses,
        isLoading: statusesLoading,
        error: statusesError,
        refresh: refreshStatuses,
        getPermittedStatuses,
    } = useSubscriptionStatus();

    const {
        data: subscriptionNotes,
        setData: setNotes,
        isLoading: subscriptionNotesLoading,
        error: subscriptionNotesError,
        refresh: refreshNotes,
        deleteSubscriptionNotes,
        createSubscriptionNote,
    } = useNotes( subscriptionId );

    // Combined error state (prioritize in order: subscription > orders > statuses)
    const error = subscriptionError || ordersError || statusesError;

    // Combined loading state
    const isLoading =
        subscriptionLoading ||
        ordersLoading ||
        statusesLoading ||
        downloadableProductsLoading;

    // Memoized refresh function that triggers all refresh mechanisms
    const refresh = useMemo( () => {
        return () => {
            refreshOrders();
            refreshStatuses();
            // Add subscription refresh when available
        };
    }, [ refreshOrders, refreshStatuses ] );

    return {
        subscription,
        orders,
        ordersStatus: status,
        statuses,
        isLoading,
        error,
        totalOrders: totalItems,
        totalPages,
        refresh,
        downloadableProducts,
        downloadableProductsError,
        refreshDownloadsProducts,
        subscriptionNotes,
        subscriptionNotesLoading,
        subscriptionNotesError,
        refreshNotes,
        deleteSubscriptionNotes,
        setNotes,
        createSubscriptionNote,
        refreshSub,
        setSubscription,
        getPermittedStatuses,
    };
};

export const useSubscriptionDownloadsProducts = ( subscriptionId: string ) => {
    const [ data, setData ] = useState< DownloadPermissionsResponse >( null ); // Replace 'any' with your data type
    const [ isLoading, setIsLoading ] = useState< boolean >( false );
    const [ error, setError ] = useState< FetchError | null >( null );

    const loadData = async () => {
        setIsLoading( true );
        setError( null );

        try {
            const response =
                await fetchSubscriptionDownloadableProducts( subscriptionId );

            setData( response.body );
        } catch ( err ) {
            setError( {
                message:
                    err instanceof Error
                        ? err.message
                        : 'An error occurred while fetching data',
            } );

            // @ts-ignore
            console.error( 'Error fetching data:', err );
        } finally {
            setIsLoading( false );
        }
    };

    useEffect( () => {
        loadData();
    }, [ subscriptionId ] );

    const refresh = () => {
        loadData();
    };

    return { data, isLoading, error, refresh };
};
