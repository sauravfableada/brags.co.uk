import { useState, useEffect, useMemo, RawHTML } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import '../../../../src/definitions/window-types';
import { useSubscriptionList } from '../hooks/SubscriptionListHooks';
import { getStatusTranslated, getStatusClass } from '../utils';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import {
    DataViews,
    DateTimeHtml,
    PriceHtml,
    CustomerFilter,
    DokanBadge,
    DokanTooltip as Tooltip,
    WpDatePicker,
} from '@dokan/components';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { snakeCase, truncate, formatPrice } from '@dokan/utilities';
import { useCustomerById } from '@dokan/hooks';
import { twMerge } from 'tailwind-merge';
import { DokanToaster, SimpleInput, useToast } from '@getdokan/dokan-ui';
import { Info, Calendar } from 'lucide-react';
import { dateI18n, getSettings } from '@wordpress/date';

type CustomerOption = {
    label: string;
    value: string;
} | null;

export default function SubscriptionList( props: any ) {
    const { navigate } = props;

    const [ selectedCustomer, setSelectedCustomer ] =
        useState< CustomerOption >( null );
    const [ selectedDate, setSelectedDate ] = useState< string >( '' );

    const [ filterArgs, setFilterArgs ] = useState( {
        page: 1,
        per_page: 10,
        selectedCustomer: '',
        selectedDate: '',
    } );

    const customerByIdHook = useCustomerById();
    const { data, totalItems, totalPages, isLoading, fetchList } =
        useSubscriptionList();
    const toast = useToast();

    const fields = [
        {
            id: 'id',
            label: __( 'Subscription', 'dokan' ),
            render: ( { item } ) => (
                <strong
                    className="dokan-link cursor-pointer"
                    onClick={ () => {
                        navigate(
                            `/user-subscription/${ item.id }`
                        );
                    } }
                >
                    #{ item.id }
                </strong>
            ),
            enableSorting: false,
        },
        {
            id: 'line_items',
            label: __( 'Item', 'dokan' ),
            render: ( { item } ) => {
                // eslint-disable-next-line camelcase
                const { line_items } = item;

                // eslint-disable-next-line camelcase,@typescript-eslint/no-shadow
                const LineItemsUI = line_items.map( ( item, index ) => {
                    const text = `${ index > 0 ? ', ' : '' }${ item.name } X ${ item.quantity }`;
                    return (
                        <Tooltip
                            key={ index }
                            content={ <RawHTML>{ text }</RawHTML> }
                            direction="top"
                            contentClass={ twMerge(
                                '',
                                'bg-gray-800 text-white p-2 rounded-md'
                            ) }
                        >
                            <p
                                className="m-0 space-x-2 flex flex-wrap max-w-80 text-wrap leading-6"
                                key={ index }
                            >
                                { truncate(
                                    text,
                                    window.wp.hooks.applyFilters(
                                        'dokan-frontend-user-subscription-list-item-title-truncate-length',
                                        20
                                    )
                                ) }
                            </p>
                        </Tooltip>
                    );
                } );
                return LineItemsUI;
            },
            enableSorting: false,
        },
        {
            id: 'status',
            label: __( 'Status', 'dokan' ),
            render: ( { item } ) => (
                <DokanBadge
                    label={ getStatusTranslated( item.status ) }
                    variant={ getStatusClass( item.status ) }
                />
            ),
            enableSorting: false,
        },
        {
            id: 'total',
            label: __( 'Total', 'dokan' ),
            render: ( { item } ) => {
                // eslint-disable-next-line camelcase,@typescript-eslint/no-shadow
                const { currency, payment_method_title, billing_period } =
                    item;
                return (
                    <div className="flex">
                        <Tooltip
                            content={
                                <RawHTML>
                                    { `${ formatPrice(
                                        item.total ?? 0,
                                        window
                                            .dokanProductSubscription
                                            .currencySymbols[
                                            currency
                                        ]
                                    ) } / ${ billing_period } ${ __(
                                        'Via',
                                        'dokan'
                                    ) } ${ payment_method_title }` }
                                </RawHTML>
                            }
                            direction="top"
                            contentClass={ twMerge(
                                '',
                                'bg-gray-800 text-white p-2 rounded-md'
                            ) }
                        >
                            <div className="flex">
                                <div>
                                    <PriceHtml
                                        price={ item.total }
                                        currencySymbol={
                                            window
                                                .dokanProductSubscription
                                                .currencySymbols[
                                                currency
                                            ]
                                        }
                                    />
                                </div>
                                &nbsp;
                                <Info
                                    size={ 16 }
                                    className="mt-[2px]"
                                />
                            </div>
                        </Tooltip>
                    </div>
                );
            },
            enableSorting: false,
        },
        {
            id: 'start_date',
            label: __( 'Start', 'dokan' ),
            render: ( { item } ) => {
                return (
                    <span>
                        { item?.display_start_date &&
                        item.start_date ? (
                            item?.display_start_date
                        ) : (
                            <DateTimeHtml.Date
                                date={ item.start_date }
                            />
                        ) }
                    </span>
                );
            },
            enableSorting: false,
        },
        {
            id: 'next_payment_date',
            label: __( 'Next Payment', 'dokan' ),
            render: ( { item } ) => {
                return (
                    <span>
                        { item?.display_next_payment_date &&
                        item.next_payment_date ? (
                            item?.display_next_payment_date
                        ) : (
                            <DateTimeHtml.Date
                                date={ item.next_payment_date }
                            />
                        ) }
                    </span>
                );
            },
            enableSorting: false,
        },
        {
            id: 'end_date',
            label: __( 'End', 'dokan' ),
            render: ( { item } ) => {
                return (
                    <span>
                        { item?.display_end_date && item.end_date ? (
                            item?.display_end_date
                        ) : (
                            <DateTimeHtml.Date
                                date={ item.end_date }
                            />
                        ) }
                    </span>
                );
            },
            enableSorting: false,
        },
    ];

    const [ view, setView ] = useState( {
        perPage: 10,
        page: 1,
        type: 'table',
        titleField: 'id',
        fields: fields.map( ( field ) =>
            field.id !== 'id' ? field.id : ''
        ),
        layout: {
            styles: {
                id: {
                    width: '12%',
                },
                line_items: {
                    width: '20%',
                },
                status: {
                    width: '11%',
                },
                total: {
                    width: '12%',
                },
                start_date: {
                    width: '15%',
                },
                next_payment_date: {
                    width: '15%',
                },
                end_date: {
                    width: '15%',
                },
            },
        },
    } );

    const actions = useMemo(
        () => [
            {
                id: 'subscription-view',
                callback: ( posts ) => {
                    const row = posts[ 0 ];
                    navigate( `/user-subscription/${ row.id }` );
                },
                label: () => __( 'View', 'dokan' ),
            },
        ],
        []
    );

    // Filter configuration
    const filter = useMemo(
        () => ( {
            fields: [
                {
                    id: 'customer',
                    label: __( 'Filter by Registered Customer', 'dokan' ),
                    field: (
                        <CustomerFilter
                            id="dokan-filter-by-customer"
                            value={ selectedCustomer }
                            errors={ [] }
                            onChange={ ( selected: {
                                label: string;
                                value: string;
                            } ) => {
                                setSelectedCustomer( selected );
                                setFilterArgs( ( prev ) => ( {
                                    ...prev,
                                    page: 1,
                                    selectedCustomer: selected.value,
                                } ) );
                            } }
                            placeholder={ __( 'Search', 'dokan' ) }
                            className="min-w-52"
                        />
                    ),
                },
                {
                    id: 'date',
                    label: __( 'Filter by Date', 'dokan' ),
                    field: (
                        <WpDatePicker
                            onChange={ ( date ) => {
                                setSelectedDate( date );
                                setFilterArgs( ( prev ) => ( {
                                    ...prev,
                                    page: 1,
                                    selectedDate: date,
                                } ) );
                            } }
                            currentDate={
                                selectedDate ? selectedDate : new Date()
                            }
                        >
                            <SimpleInput
                                addOnLeft={ <Calendar size="16" /> }
                                className="border rounded px-3 py-1.5 w-full bg-white"
                                onChange={ () => {} }
                                value={
                                    selectedDate
                                        ? dateI18n(
                                              getSettings().formats.date,
                                              selectedDate,
                                              getSettings().timezone.string
                                          )
                                        : ''
                                }
                                input={ {
                                    id: 'dokan-filter-by-date-input',
                                    name: 'dokan_filter_by_date_input',
                                    type: 'text',
                                    autoComplete: 'off',
                                    placeholder: __( 'Enter Date', 'dokan' ),
                                    readOnly: true,
                                } }
                            />
                        </WpDatePicker>
                    ),
                },
            ],
            onReset: () => {
                setSelectedCustomer( null );
                setSelectedDate( '' );
                setFilterArgs( ( prev ) => ( {
                    ...prev,
                    page: 1,
                    selectedDate: '',
                    selectedCustomer: '',
                } ) );
                setView( ( prev ) => ( { ...prev, page: 1 } ) );
            },
            onFilterRemove: ( filterId: string ) => {
                if ( filterId === 'customer' ) {
                    setSelectedCustomer( null );
                    setFilterArgs( ( prev ) => ( {
                        ...prev,
                        page: 1,
                        selectedCustomer: '',
                    } ) );
                } else if ( filterId === 'date' ) {
                    setSelectedDate( '' );
                    setFilterArgs( ( prev ) => ( {
                        ...prev,
                        page: 1,
                        selectedDate: '',
                    } ) );
                }
                setView( ( prev ) => ( { ...prev, page: 1 } ) );
            },
        } ),
        [ selectedCustomer, selectedDate ]
    );

    const onViewChange = ( newView ) => {
        setView( newView );
        setFilterArgs( ( prev ) => ( {
            ...prev,
            page: newView.page,
            per_page: newView.perPage,
        } ) );
    };

    // FIX: Replaced JSON.stringify(filterArgs) with explicit primitive dependencies.
    // JSON.stringify produces a new string reference on every render even when the
    // underlying values have not changed, causing silent unnecessary fetch executions.
    // Explicit primitive deps let React do fast equality checks and skip re-runs
    // only when a specific filter value actually changes.
    useEffect( () => {
        const fetchData = async () => {
            const requestArg: Record< string, any > = {
                page: filterArgs.page,
                per_page: filterArgs.per_page,
            };

            if ( filterArgs.selectedDate ) {
                // @ts-ignore
                requestArg.after = window
                    .moment( filterArgs.selectedDate )
                    .subtract( 1, 'days' )
                    .toISOString();
                // @ts-ignore
                requestArg.before = window
                    .moment( filterArgs.selectedDate )
                    .toISOString();
            }

            if ( filterArgs.selectedCustomer ) {
                requestArg.customer = filterArgs.selectedCustomer;
            }

            const hookName = snakeCase(
                'dokan_subscription_filter_request_param'
            );

            // @ts-ignore
            const requestPayload = wp.hooks.applyFilters(
                hookName,
                requestArg
            );

            await fetchList( requestPayload );
        };

        fetchData();
    }, [
        filterArgs.page,
        filterArgs.per_page,
        filterArgs.selectedDate,
        filterArgs.selectedCustomer,
    ] );

    // Fetch customer data on mount if customer_id in URL
    useEffect( () => {
        const queryParams = new URLSearchParams( props.location.search );
        const customerId = queryParams.get( 'customer_id' ) || '';

        if (
            customerId &&
            customerId !== '' &&
            ! isNaN( Number( customerId ) )
        ) {
            setFilterArgs( ( prev ) => ( {
                ...prev,
                selectedCustomer: customerId,
            } ) );

            customerByIdHook
                .fetchCustomerById( Number( customerId ) )
                .then( ( customer ) => {
                    setSelectedCustomer( {
                        // @ts-ignore
                        label:
                            customer.first_name + ' ' + customer.last_name,
                        value: String( customer.id ),
                    } );
                } )
                .catch( ( error ) => {
                    console.error( 'Failed to fetch customer', error );
                    toast( {
                        title: __(
                            'Failed to fetch customer',
                            'dokan'
                        ),
                        type: 'error',
                    } );
                } );
        }

        const orderDate = queryParams.get( 'order_date' ) || '';
        if ( orderDate ) {
            setSelectedDate( orderDate );
            setFilterArgs( ( prev ) => ( {
                ...prev,
                selectedDate: orderDate,
            } ) );
        }
    }, [] );

    return (
        <div className="dokan-react-user-subscription">
            <DataViews
                data={ data ?? [] }
                namespace="dokan-vendor-subscription-data-view"
                fields={ fields }
                filter={ filter }
                getItemId={ ( item ) => item.id }
                onChangeView={ onViewChange }
                search={ false }
                paginationInfo={ {
                    totalItems,
                    totalPages,
                } }
                view={ view }
                actions={ actions }
                isLoading={ isLoading }
            />

            <DokanToaster />
        </div>
    );
}
