import { useEffect, useState, useCallback, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { useToast } from '@getdokan/dokan-ui';
import {
    DataViews,
    PriceHtml,
    DateTimeHtml,
    DokanLink,
} from '@dokan/components';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { capitalCase } from '@dokan/utilities';
import { SubscriptionOrder } from '../definition/SubscriptionOrders';

const decodeURL = ( url: string ) => {
    return decodeURIComponent( url.replace( /&amp;/g, '&' ) );
};

const SubscriptionOrders = ( { vendorId } ) => {
    const toast = useToast();
    const [ isLoading, setIsLoading ] = useState( true );
    const [ ordersData, setOrdersData ] = useState< SubscriptionOrder[] >( [] );
    const [ totalOrders, setTotalOrders ] = useState( 0 );

    const [ filterArgs, setFilterArgs ] = useState( {
        page: 1,
        per_page: 10,
    } );

    const [ view, setView ] = useState( {
        perPage: 10,
        page: 1,
        type: 'table',
        layout: {
            styles: {
                id: {
                    width: '25%',
                },
                date_created: {
                    width: '25%',
                },
                status: {
                    width: '25%',
                },
                total: {
                    width: '25%',
                },
            },
        },
    } );

    const fields = [
        {
            id: 'id',
            label: __( 'Order', 'dokan' ),
            render: ( { item }: { item: SubscriptionOrder } ) => (
                <DokanLink
                    className="font-semibold cursor-pointer"
                    href={ item?.actions.view?.url }
                    target="_blank"
                >
                    { item.id }
                </DokanLink>
            ),
            enableSorting: false,
        },
        {
            id: 'date_created',
            label: __( 'Date', 'dokan' ),
            render: ( { item }: { item: SubscriptionOrder } ) => (
                <DateTimeHtml.Date date={ item.date_created } />
            ),
            enableSorting: false,
        },
        {
            id: 'status',
            label: __( 'Status', 'dokan' ),
            render: ( { item }: { item: SubscriptionOrder } ) => (
                <>{ capitalCase( item.status ) }</>
            ),
            enableSorting: false,
        },
        {
            id: 'total',
            label: __( 'Total', 'dokan' ),
            render: ( { item }: { item: SubscriptionOrder } ) => (
                <PriceHtml price={ item.total } />
            ),
            enableSorting: false,
        },
    ];

    const actions = useMemo(
        () => [
            {
                id: 'order-pay',
                isEligible: ( item: SubscriptionOrder ) =>
                    !! item.actions.pay,
                callback: ( [ order ]: SubscriptionOrder[] ) => {
                    window.open(
                        decodeURL( order?.actions?.pay?.url ),
                        '_blank'
                    );
                },
                label: () => __( 'Pay', 'dokan' ),
            },
            {
                id: 'order-view',
                isEligible: ( item: SubscriptionOrder ) =>
                    !! item.actions.view,
                callback: ( [ order ]: SubscriptionOrder[] ) => {
                    window.open(
                        decodeURL( order?.actions?.view?.url ),
                        '_blank'
                    );
                },
                label: () => __( 'View', 'dokan' ),
            },
            {
                id: 'order-cancel',
                isDestructive: true,
                isEligible: ( item: SubscriptionOrder ) =>
                    !! item.actions.cancel,
                callback: ( [ order ]: SubscriptionOrder[] ) => {
                    window.open(
                        decodeURL( order?.actions?.cancel?.url ?? '' ),
                        '_blank'
                    );
                },
                label: () => __( 'Cancel', 'dokan' ),
            },
        ],
        []
    );

    const fetchOrders = useCallback( async () => {
        if ( ! vendorId ) {
            return;
        }

        setIsLoading( true );

        try {
            const response = ( await apiFetch( {
                path: addQueryArgs(
                    `/dokan/v1/vendor-subscription/orders/${ vendorId }`,
                    {
                        per_page: filterArgs.per_page,
                        page: filterArgs.page,
                    }
                ),
                parse: false,
            } ) ) as Response;

            const orders = await response.json();
            const totalItems = parseInt(
                response.headers.get( 'X-WP-Total' ) ?? '0'
            );

            setOrdersData( orders );
            setTotalOrders( totalItems );
        } catch ( error: any ) {
            toast( {
                type: 'error',
                title:
                    error?.message ||
                    __( 'Error fetching orders', 'dokan' ),
            } );
        } finally {
            setIsLoading( false );
        }
    }, [ vendorId, filterArgs.page, filterArgs.per_page ] );

    const onViewChange = ( newView: typeof view ) => {
        setView( newView );
        setFilterArgs( ( prev ) => ( {
            ...prev,
            page: newView.page,
            per_page: newView.perPage,
        } ) );
    };

    useEffect( () => {
        void fetchOrders();
    }, [ fetchOrders ] );

    return (
        <DataViews
            data={ ordersData }
            namespace="dokan-vendor-subscription-orders-data-view"
            fields={ fields }
            getItemId={ ( item: SubscriptionOrder ) => item.id }
            onChangeView={ onViewChange }
            search={ false }
            paginationInfo={ {
                totalItems: totalOrders,
                totalPages: Math.ceil(
                    totalOrders / view.perPage
                ),
            } }
            view={ view }
            actions={ actions }
            isLoading={ isLoading }
        />
    );
};

export default SubscriptionOrders;
