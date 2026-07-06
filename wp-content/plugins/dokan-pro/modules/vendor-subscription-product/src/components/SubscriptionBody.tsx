import {
    AsyncSearchableSelect,
    Card,
    Divider,
    SearchableSelect,
    useToast,
} from '@getdokan/dokan-ui';
import { useState } from '@wordpress/element';
import {
    CombinedSubscriptionData,
    DateType,
    DownloadPermission,
} from '../Types';
import { __ } from '@wordpress/i18n';
import '../../../../src/definitions/window-types';
import ShippingLineItems from './ShippingLineItems';
import {
    DateTimeHtml,
    DokanBadge,
    DokanAlert,
    DokanButton,
    DokanLink,
    PriceHtml,
    DokanTooltip as Tooltip,
} from '@dokan/components';
import SubscriptionLineItems from './SubscriptionLineItems';
import CouponLineItems from './CouponLineItems';
import Discount from './Discount';
import Shipping from './Shipping';
import { Order } from '../../../../src/Definitions/Order';
import { getStatusClass, getStatusTranslated } from '../utils';
import { twMerge } from 'tailwind-merge';
import DownloasableItem from './DownloasableItem';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { SubscriptionNote, SubscriptionNotes } from '../hooks/useNotes';
import { useDebounceCallback } from 'usehooks-ts';
import { useDownloadableProducts } from '../hooks/useDownloadableProducts';
import ScheduleItem from './ScheduleItem';
import { useSubscriptionScheduler } from '../hooks/useSubscriptionScheduler';
import useUpdateSubscriptionStatus from '../hooks/useUpdateSubscriptionStatus';
import { useSelect } from '@wordpress/data';
import { store as CountryStore } from '../../../../src/stores/country-state';
import { humanTimeDiff } from '@wordpress/date';
import NoteItem from './NoteItem';

type PropType = {
    subscription: CombinedSubscriptionData;
    navigate;
};

const StateHtml = ( {
    countryCode,
    stateCode,
}: {
    countryCode: string;
    stateCode: string;
} ) => {
    const stateItem = useSelect(
        ( select ) => {
            return select( CountryStore ).getStateItem(
                countryCode,
                stateCode
            );
        },
        [ countryCode, stateCode ]
    );

    if ( ! stateItem ) {
        return '';
    }

    return stateItem?.name;
};

const SubscriptionBody = ( { subscription: subData, navigate }: PropType ) => {
    const {
        subscription,
        orders,
        ordersStatus,
        statuses,
        downloadableProducts,
        refreshDownloadsProducts,
        subscriptionNotes,
        setNotes,
        deleteSubscriptionNotes,
        createSubscriptionNote,
        setSubscription,
        refreshNotes,
        getPermittedStatuses,
    } = subData;

    const toast = useToast();
    const {
        isUpdating: isUpdatingSchedules,
        updateSchedule,
        getUnixTimestamp,
    } = useSubscriptionScheduler( subscription.id );
    const [ isEditStatus, setIsEditStatus ] = useState< boolean >( false );
    const [ statusValue, setStatusValue ] = useState< string >(
        subscription.status
    );
    const [ selectedDownloadableProducts, setSelectedDownloadableProducts ] =
        useState( [] );
    const [ searchedDownloadableProducts, setSearchedDownloadableProducts ] =
        useState( [] );
    const [ isCreatingNore, setIsCreatingNore ] = useState( false );

    const { searchProducts, grantAccess, isGranting } =
        useDownloadableProducts();

    const debounced = useDebounceCallback( async function ( {
        inputValue,
        callback,
    } ) {
        try {
            const searchResults = await searchProducts( inputValue );
            const resultData = searchResults.map( ( product ) => {
                return {
                    label: `#${ product.id } ${ product.name }`,
                    value: product.id,
                };
            } );

            setSearchedDownloadableProducts( resultData );
            callback( resultData );
        } catch ( error ) {
            console.error( __( 'Search failed:', 'dokan' ), error );
        }
    }, 500 );

    const [ noteType, setNoteType ] = useState( 'private' );
    const [ noteText, setNoteText ] = useState( '' );

    const getMeta = ( order: Order, key: string, defaultValue = '' ) => {
        const data = order.meta_data.find( ( meta ) => {
            // eslint-disable-next-line eqeqeq
            return meta.key == key;
        } );

        if ( ! data ) {
            return defaultValue;
        }

        return data.value;
    };

    const editHandle = ( e ) => {
        e.preventDefault();

        setIsEditStatus( ! isEditStatus );
    };
    const removeWCPrefix = ( status: string ): string => {
        return status.replace( /^wc-/, '' );
    };

    const revokeDownloadItem = async (
        downloadableProduct: DownloadPermission
    ) => {
        try {
            await apiFetch( {
                method: 'DELETE',
                path: addQueryArgs(
                    `/dokan/v3/orders/${ subscription.id }/downloads`,
                    {
                        download_id: downloadableProduct.download_id,
                        product_id: downloadableProduct.product_id,
                        order_id: downloadableProduct.order_id,
                        permission_id: downloadableProduct.permission_id,
                    }
                ),
                parse: false,
            } );

            toast( {
                title: __( 'Revoke successful', 'dokan' ),
                type: 'success',
            } );

            refreshDownloadsProducts();
        } catch ( err ) {
            console.log( err );
        }
    };

    const deleteNote = async ( note: SubscriptionNote ) => {
        try {
            const response = await deleteSubscriptionNotes( note );

            const deletedNoteId = response?.body?.id;

            // @ts-ignore
            setNotes( ( previousNotes: SubscriptionNotes ) => {
                return previousNotes.filter( ( noteItem ) => {
                    return Number( noteItem.id ) !== Number( deletedNoteId );
                } );
            } );

            toast( {
                title: __( 'Note deleted successfully', 'dokan' ),
                type: 'success',
            } );
        } catch ( err ) {
            console.log( err );
        }
    };

    const createNewNote = async () => {
        if ( ! noteText || noteText.length < 1 ) {
            return;
        }

        const payload = {
            customer_note: noteType === 'customer',
            note: noteText,
        };

        try {
            setIsCreatingNore( true );
            const response = await createSubscriptionNote( payload );
            const data = response?.body;

            // @ts-ignore
            setNotes( ( previousNotes: SubscriptionNotes ) => {
                return [ data, ...previousNotes ];
            } );

            setNoteText( '' );

            toast( {
                title: __( 'Note added successfully', 'dokan' ),
                type: 'success',
            } );
            setIsCreatingNore( false );
        } catch ( err ) {
            console.log( err );
            setIsCreatingNore( false );
        }
    };

    const grantAccessHandler = async () => {
        try {
            const productIds = selectedDownloadableProducts.map( ( item ) => {
                return item.value;
            } );

            if ( ! productIds.length ) {
                return;
            }

            const response = await grantAccess( subscription.id, productIds );

            if (
                response &&
                response?.granted_files &&
                Object.keys( response?.granted_files ).length
            ) {
                toast( {
                    title: __( 'Grant access successful', 'dokan' ),
                    type: 'success',
                } );
            }

            if (
                response &&
                response?.errors &&
                Array.isArray( response?.errors )
            ) {
                response?.errors.map( ( error ) => {
                    toast( {
                        title: error,
                        type: 'error',
                    } );
                } );
            }

            refreshDownloadsProducts();
        } catch ( error ) {
            // Handle any API errors
            console.error( 'Failed to grant access:', error );
        }
    };

    const setSchedulerDateType = ( {
        date,
        dateType,
    }: {
        date: string;
        dateType: DateType;
    } ) => {
        const updatedSubscription = { ...subscription };

        updatedSubscription.settings.date_types =
            updatedSubscription.settings.date_types.map( ( currentType ) => {
                if ( currentType.date_key === dateType.date_key ) {
                    currentType.date_site = date;
                    currentType[ `${ dateType.date_key }_timestamp_utc` ] = date
                        ? getUnixTimestamp( date )
                        : '';
                }
                return currentType;
            } );

        setSubscription( updatedSubscription );
    };

    const transformDates = ( dateArray ) => {
        return dateArray.reduce( ( acc, dateObj ) => {
            const { internal_date_key, can_date_be_updated, date_site } =
                dateObj;
            const timestampKey = `${ internal_date_key }_timestamp_utc`;

            // If can_date_be_updated is false, only add timestamp
            if ( ! can_date_be_updated ) {
                if ( dateObj[ timestampKey ] ) {
                    acc[ timestampKey ] = dateObj[ timestampKey ];
                }
                return acc;
            }

            // For updatable dates, process both date and time
            let dateValue;
            let hours = 0;
            let minutes = 0;

            // Parse date and time based on format
            if ( date_site.includes( 'GMT' ) ) {
                // Handle "Sat Nov 16 2024 11:50:00 GMT+0600" format
                const dateObj = new Date( date_site );
                dateValue = dateObj.toISOString().split( 'T' )[ 0 ];
                hours = dateObj.getHours();
                minutes = dateObj.getMinutes();
            } else {
                // Handle "2024-12-19T17:11:00" format
                const [ datePart, timePart ] = date_site.split( 'T' );
                dateValue = datePart;
                if ( timePart ) {
                    const [ hoursStr, minutesStr ] = timePart.split( ':' );
                    hours = parseInt( hoursStr, 10 );
                    minutes = parseInt( minutesStr, 10 );
                }
            }

            // Add date value
            acc[ internal_date_key ] = dateValue;

            // Add timestamp
            acc[ timestampKey ] = dateObj[ timestampKey ]
                ? dateObj[ timestampKey ]
                : '';

            // Add hour and minute keys
            acc[ `${ internal_date_key }_hour` ] = hours;
            acc[ `${ internal_date_key }_minute` ] = minutes;

            return acc;
        }, {} );
    };

    const updateScheduleHandler = async () => {
        const allDates = transformDates( subscription.settings.date_types );
        const params = {
            billing_interval: subscription.billing_interval,
            billing_period: subscription.billing_period,
            dates: allDates,
        };

        try {
            await updateSchedule( params );

            toast( {
                title: __( 'Schedule updated.', 'dokan' ),
                type: 'success',
            } );
        } catch ( error ) {
            if ( error.message ) {
                toast( {
                    title: error.message,
                    type: 'error',
                } );
            }
        }
    };

    const { isUpdating: isUpdatingStatus, updateStatus } =
        useUpdateSubscriptionStatus( subscription.id );
    const updateStatusHandler = () => {
        updateStatus( statusValue )
            .then( ( response ) => {
                refreshNotes();
                setSubscription( response );
            } )
            .catch( ( err ) => {
                console.log( err );
            } );
    };

    const getPaumentIntervals = () => {
        return Object.keys(
            subscription?.settings?.period_interval_strings
        ).map( ( item ) => {
            return {
                label: subscription?.settings?.period_interval_strings[ item ],
                value: item,
            };
        } );
    };

    const getPeriodStrings = () => {
        return Object.keys( subscription?.settings?.period_strings ).map(
            ( item ) => {
                return {
                    label: subscription?.settings?.period_strings[ item ],
                    value: item,
                };
            }
        );
    };

    const getFormatedPermitedStatuses = () => {
        return Object.keys( getPermittedStatuses( subscription.status ) ).map(
            ( key ) => {
                return {
                    label: statuses[ key ],
                    value: removeWCPrefix( key ),
                };
            }
        );
    };

    const getNoteTypes = () => {
        return [
            {
                label: __( 'Private note', 'dokan' ),
                value: '',
            },
            {
                label: __( 'Customer note', 'dokan' ),
                value: 'customer',
            },
        ];
    };

    const getOrderRefundTotal = ( order: Order ) => {
        return order?.refunds?.reduce( ( total, refund ) => {
            return total + Math.abs( Number( refund.total ) );
        }, 0 );
    };

    return (
        <div className="grid md:grid-cols-1 lg:grid-cols-3 gap-3">
            { /* Left Column */ }
            <div className="md:col-span-3 lg:col-span-2 space-y-3">
                <Card>
                    <Card.Header className="p-3">
                        <Card.Title className="m-0">
                            <strong>
                                <span>{ __( 'Subscription', 'dokan' ) }</span>
                                &nbsp;#{ subscription.id }&nbsp;
                                <span>→</span>
                            </strong>
                            &nbsp;
                            <span>{ __( 'Order Items', 'dokan' ) }</span>
                        </Card.Title>
                    </Card.Header>
                    <Card.Body className="p-3 text-sm">
                        <div className="overflow-x-auto">
                            <div className="w-full min-w-[400px]">
                                { /* Header */ }
                                <div className="grid grid-cols-12 bg-gray-50 py-2 px-4">
                                    { ' ' }
                                    <div className="col-span-4 text-left font-medium">
                                        { ' ' }
                                        { __( 'Item', 'dokan' ) }
                                    </div>
                                    <div className="col-span-2 text-left font-medium">
                                        { ' ' }
                                        { __( 'Cost', 'dokan' ) }
                                    </div>
                                    <div className="col-span-2 text-center font-medium">
                                        { ' ' }
                                        { __( 'Qty', 'dokan' ) }
                                    </div>
                                    <div className="col-span-2 text-right font-medium">
                                        { ' ' }
                                        { __( 'Total', 'dokan' ) }
                                    </div>
                                    <div className="col-span-2 text-right font-medium">
                                        { ' ' }
                                        { __( 'Tax', 'dokan' ) }
                                    </div>
                                </div>

                                { /* Body */ }
                                <div className="divide-y">
                                    { /* Item Row */ }
                                    <SubscriptionLineItems
                                        subscription={ subscription }
                                    />

                                    { /* Shipping Row */ }
                                    <ShippingLineItems
                                        shippingLines={
                                            subscription.shipping_lines
                                        }
                                        currency={ subscription.currency }
                                    />
                                </div>

                                { /* Footer Section */ }
                                <CouponLineItems
                                    couponLines={ subscription.coupon_lines }
                                />
                                <div className="border-t pt-4">
                                    <Discount
                                        discountTotal={ Number(
                                            subscription.discount_total
                                        ) }
                                    />
                                    <Shipping
                                        amount={ Number(
                                            subscription.shipping_total
                                        ) }
                                        shippingLines={
                                            subscription.shipping_lines
                                        }
                                    />
                                    { Number( subscription.total_tax ) > 0 && (
                                        <div className="flex justify-between px-4 pt-4">
                                            <span>
                                                { __( 'Tax:', 'dokan' ) }
                                            </span>
                                            <PriceHtml
                                                price={ subscription.total_tax }
                                            />
                                        </div>
                                    ) }
                                    <div className="flex justify-between px-4 pt-4">
                                        <span>
                                            { __( 'Order Total:', 'dokan' ) }
                                        </span>
                                        <PriceHtml
                                            price={ subscription.total }
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card.Body>
                </Card>

                <div className="grid sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-3">
                    <Card>
                        <Card.Header className="p-3">
                            <Card.Title className="m-0">
                                { __( 'Billing Address', 'dokan' ) }
                            </Card.Title>
                        </Card.Header>
                        <Card.Body className="p-3 text-sm">
                            <address className="not-italic">
                                <div>
                                    { subscription.billing.first_name }&nbsp;
                                    { subscription.billing.last_name }
                                </div>
                                <div>{ subscription.billing.company }</div>
                                <div>{ subscription.billing.address_1 }</div>
                                <div>{ subscription.billing.address_2 }</div>
                                <div>{ subscription.billing.city }</div>
                                { subscription.billing.country &&
                                    subscription.billing.state && (
                                        <div>
                                            <StateHtml
                                                countryCode={
                                                    subscription.billing.country
                                                }
                                                stateCode={
                                                    subscription.billing.state
                                                }
                                            />
                                        </div>
                                    ) }
                                <div>{ subscription.billing.postcode }</div>
                            </address>
                        </Card.Body>
                    </Card>

                    <Card>
                        <Card.Header className="p-3">
                            <Card.Title className="m-0">
                                { __( 'Shipping Address', 'dokan' ) }
                            </Card.Title>
                        </Card.Header>
                        <Card.Body className="p-3 text-sm">
                            <address className="not-italic">
                                <div>
                                    { subscription.shipping.first_name }&nbsp;
                                    { subscription.shipping.last_name }
                                </div>
                                <div>{ subscription.shipping.company }</div>
                                <div>{ subscription.shipping.address_1 }</div>
                                <div>{ subscription.shipping.address_2 }</div>
                                <div>{ subscription.shipping.city }</div>
                                { subscription.shipping.country &&
                                    subscription.shipping.state && (
                                        <div>
                                            <StateHtml
                                                countryCode={
                                                    subscription.shipping
                                                        .country
                                                }
                                                stateCode={
                                                    subscription.shipping.state
                                                }
                                            />
                                        </div>
                                    ) }
                                <div>{ subscription.shipping.postcode }</div>
                            </address>
                        </Card.Body>
                    </Card>
                </div>

                <Card>
                    <Card.Header className="p-3">
                        <Card.Title className="m-0">
                            { __( 'Downloadable Product Permission', 'dokan' ) }
                        </Card.Title>
                    </Card.Header>
                    <Card.Body className="p-3 text-sm">
                        { downloadableProducts.length > 0 && (
                            <div className="mb-3 flex flex-col gap-3">
                                { downloadableProducts.map( ( item, i ) => {
                                    return (
                                        <DownloasableItem
                                            key={ i }
                                            downloadableProduct={ item }
                                            revoke={ revokeDownloadItem }
                                        />
                                    );
                                } ) }
                            </div>
                        ) }
                        <div className="space-y-4">
                            <AsyncSearchableSelect
                                className="shadow-none"
                                defaultOptions={ searchedDownloadableProducts }
                                placeholder={ __(
                                    'Search for a downloadable product…',
                                    'dokan'
                                ) }
                                errors={ [] }
                                onChange={ (
                                    items: { label: string; value: string }[]
                                ) => {
                                    setSelectedDownloadableProducts( items );
                                } }
                                isMulti={ true }
                                loadOptions={ (
                                    inputValue: string,
                                    callback: (
                                        options: {
                                            label: string;
                                            value: string;
                                        }[]
                                    ) => void
                                ) => {
                                    debounced( {
                                        inputValue,
                                        callback,
                                    } );
                                } }
                                noOptionsMessage={ () =>
                                    __( 'No options', 'dokan' )
                                }
                            />
                            <DokanButton
                                onClick={ grantAccessHandler }
                                loading={ isGranting }
                                disabled={ isGranting }
                            >
                                { isGranting
                                    ? __( 'Granting Access…', 'dokan' )
                                    : __( 'Grant Access', 'dokan' ) }
                            </DokanButton>
                        </div>
                    </Card.Body>
                </Card>

                <Card>
                    <Card.Header className="p-3">
                        <Card.Title className="m-0">
                            { __( 'Related orders', 'dokan' ) }
                        </Card.Title>
                    </Card.Header>
                    <Card.Body className="p-3 text-sm">
                        <div className="overflow-x-auto">
                            { ordersStatus === 500 ? (
                                <div className="w-full">
                                    <DokanAlert
                                        variant="warning"
                                        label={ __(
                                            'To view related orders kindly contact with the admin',
                                            'dokan'
                                        ) }
                                    />
                                </div>
                            ) : (
                                <table className="w-full">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="text-left !py-2">
                                                { __(
                                                    'Order Number',
                                                    'dokan'
                                                ) }
                                            </th>
                                            <th className="text-left !py-2">
                                                { __(
                                                    'Relationship',
                                                    'dokan'
                                                ) }
                                            </th>
                                            <th className="text-left !py-2">
                                                { __( 'Date', 'dokan' ) }
                                            </th>
                                            <th className="text-left !py-2">
                                                { __( 'Status', 'dokan' ) }
                                            </th>
                                            <th className="text-right !py-2">
                                                { __( 'Total', 'dokan' ) }
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        { orders.map( ( order, index ) => (
                                            <tr
                                                key={ index }
                                                className={ twMerge(
                                                    '',
                                                    orders.length !== index + 1
                                                        ? 'border-b'
                                                        : ''
                                                ) }
                                            >
                                                <td className="!py-2">
                                                    <DokanLink
                                                        href={
                                                            order?.vendor_dashboard_order_link
                                                                ? order.vendor_dashboard_order_link.replace(
                                                                      /#038;/g,
                                                                      '&'
                                                                  )
                                                                : '#'
                                                        }
                                                        rel="noopener"
                                                        target="_blank"
                                                    >
                                                        #{ order.id }
                                                    </DokanLink>
                                                </td>
                                                <td className="!py-2">
                                                    { getMeta(
                                                        order,
                                                        'subscription_order_type',
                                                        ''
                                                    ) }
                                                    &nbsp;
                                                    { __( 'Order', 'dokan' ) }
                                                </td>
                                                <td className="!py-2">
                                                    { humanTimeDiff(
                                                        order.date_created,
                                                        new Date()
                                                    ) }
                                                </td>
                                                <td className="!py-2">
                                                    <DokanBadge
                                                        variant={ getStatusClass(
                                                            order.status
                                                        ) }
                                                        label={ getStatusTranslated(
                                                            order.status
                                                        ) }
                                                    />
                                                </td>
                                                <td className="!py-2 text-right">
                                                    <div className="flex flex-col">
                                                        <div
                                                            className={ twMerge(
                                                                getOrderRefundTotal(
                                                                    order
                                                                ) > 0
                                                                    ? 'line-through'
                                                                    : ''
                                                            ) }
                                                        >
                                                            <PriceHtml
                                                                price={
                                                                    order.total
                                                                }
                                                            />
                                                        </div>
                                                        { getOrderRefundTotal(
                                                            order
                                                        ) > 0 && (
                                                            <div>
                                                                <PriceHtml
                                                                    price={
                                                                        order.total -
                                                                        getOrderRefundTotal(
                                                                            order
                                                                        )
                                                                    }
                                                                />
                                                            </div>
                                                        ) }
                                                    </div>
                                                </td>
                                            </tr>
                                        ) ) }
                                    </tbody>
                                </table>
                            ) }
                        </div>
                    </Card.Body>
                </Card>
            </div>

            { /* Right Column */ }
            <div className="md:col-span-3 lg:col-span-1 space-y-3">
                <Card>
                    <Card.Header className="p-3">
                        <Card.Title className="m-0">
                            { __( 'General Details', 'dokan' ) }
                        </Card.Title>
                    </Card.Header>
                    <Card.Body className="p-3 text-sm">
                        <div className="space-y-3">
                            <div className="flex items-center">
                                <span className="text-gray-500">
                                    <strong>
                                        { __(
                                            'Subscription Status:',
                                            'dokan'
                                        ) }
                                    </strong>
                                </span>
                                &nbsp;
                                <div className="flex items-center gap-2">
                                    <DokanBadge
                                        variant={ getStatusClass(
                                            subscription.status
                                        ) }
                                        label={ getStatusTranslated(
                                            subscription.status
                                        ) }
                                    />
                                    <DokanLink
                                        href="#"
                                        onClick={ editHandle }
                                        className={ twMerge(
                                            'hover:underline',
                                            isEditStatus
                                                ? 'opacity-0'
                                                : 'opacity-100'
                                        ) }
                                    >
                                        { __( 'Edit', 'dokan' ) }
                                    </DokanLink>
                                </div>
                            </div>
                            { isEditStatus && (
                                <div className="flex flex-col">
                                    <SearchableSelect
                                        value={ getFormatedPermitedStatuses().find(
                                            ( itemData ) => {
                                                return (
                                                    itemData.value ==
                                                    statusValue
                                                );
                                            }
                                        ) }
                                        onChange={ ( e ) => {
                                            setStatusValue( e.value );
                                        } }
                                        options={ getFormatedPermitedStatuses() }
                                    />
                                    <div className="mt-3 flex flex-row gap-2">
                                        <DokanButton
                                            loading={ isUpdatingStatus }
                                            disabled={ isUpdatingStatus }
                                            onClick={ updateStatusHandler }
                                        >
                                            { __( 'Update', 'dokan' ) }
                                        </DokanButton>
                                        <DokanButton
                                            variant="secondary"
                                            onClick={ editHandle }
                                        >
                                            { __( 'Cancel', 'dokan' ) }
                                        </DokanButton>
                                    </div>
                                </div>
                            ) }
                            <div className="flex">
                                <span className="text-gray-500">
                                    <strong>
                                        { __( 'Order Date:', 'dokan' ) }
                                    </strong>
                                </span>
                                &nbsp;
                                <span className="text-gray-500">
                                    <DateTimeHtml
                                        date={ subscription.date_created }
                                    />
                                </span>
                            </div>
                            <Divider label="" />
                            <div>
                                <span className="block text-gray-500">
                                    <strong>
                                        { __( 'Customer:', 'dokan' ) }
                                    </strong>
                                    &nbsp;
                                    { subscription?.billing?.first_name }
                                    &nbsp;
                                    { subscription?.billing?.last_name }
                                </span>
                            </div>
                            <div>
                                <span className="block text-gray-500">
                                    <strong>{ __( 'Email:', 'dokan' ) }</strong>
                                    &nbsp;
                                    { subscription?.billing?.email }
                                </span>
                            </div>
                            <div>
                                <span className="block text-gray-500">
                                    <strong>{ __( 'Phone:', 'dokan' ) }</strong>
                                    &nbsp;
                                    { subscription?.billing?.phone }
                                </span>
                            </div>
                            <div>
                                <span className="block text-gray-500">
                                    <strong>
                                        { __( 'Customer IP:', 'dokan' ) }
                                    </strong>
                                    &nbsp;
                                    { subscription?.customer_ip_address }
                                </span>
                            </div>
                        </div>
                    </Card.Body>
                </Card>

                <Card>
                    <Card.Header className="p-3">
                        <Card.Title className="m-0">
                            { __( 'Subscription Schedule', 'dokan' ) }
                        </Card.Title>
                    </Card.Header>
                    <Card.Body className="p-3 text-sm">
                        <div className="space-y-4">
                            { /* Payment Schedule */ }
                            { subscription?.settings
                                ?.can_date_be_updated_next_payment ? (
                                <div className="space-y-2">
                                    { /* eslint-disable-next-line jsx-a11y/label-has-associated-control */ }
                                    <label className="text-gray-500">
                                        <strong>
                                            { __( 'Payment', 'dokan' ) }
                                        </strong>
                                        &nbsp;
                                        <Tooltip
                                            content={ __(
                                                'Choose Variable if your product has multiple attributes - like sizes, colors, quality etc',
                                                'dokan'
                                            ) }
                                            direction="top"
                                            contentClass={ twMerge(
                                                '',
                                                'bg-gray-800 text-white p-2 rounded-md'
                                            ) }
                                        >
                                            <span className="fa fa-question-circle dokan-vendor-order-page-tips"></span>
                                        </Tooltip>
                                    </label>
                                    <div className="flex gap-2">
                                        <SearchableSelect
                                            // className="border rounded px-3 py-1.5 bg-white"
                                            value={ getPaumentIntervals().find(
                                                ( interval ) => {
                                                    return (
                                                        interval.value ==
                                                        subscription?.billing_interval
                                                    );
                                                }
                                            ) }
                                            onChange={ ( e ) => {
                                                setSubscription( {
                                                    ...subscription,
                                                    billing_interval: e.value,
                                                } );
                                            } }
                                            options={ getPaumentIntervals() }
                                        />
                                        <SearchableSelect
                                            // className="border rounded px-3 py-1.5 bg-white"
                                            value={ getPeriodStrings().find(
                                                ( interval ) => {
                                                    return (
                                                        interval.value ==
                                                        subscription?.billing_period
                                                    );
                                                }
                                            ) }
                                            onChange={ ( e ) => {
                                                setSubscription( {
                                                    ...subscription,
                                                    billing_period: e.value,
                                                } );
                                            } }
                                            options={ getPeriodStrings() }
                                        />
                                    </div>
                                </div>
                            ) : (
                                <div className="space-y-1 flex">
                                    <span className="text-sm text-gray-500">
                                        <strong>
                                            { __( 'Recurring:', 'dokan' ) }
                                        </strong>
                                    </span>
                                    &nbsp;
                                    <span className="text-gray-500 text-sm !m-0">
                                        <div>
                                            { subscription?.recurring_string }
                                        </div>
                                    </span>
                                </div>
                            ) }

                            { subscription?.settings?.date_types &&
                                subscription?.settings?.date_types?.map(
                                    ( dateType, index ) => {
                                        return (
                                            <ScheduleItem
                                                key={ index }
                                                dateType={ dateType }
                                                setSchedulerDateType={
                                                    setSchedulerDateType
                                                }
                                            />
                                        );
                                    }
                                ) }

                            { subscription?.settings
                                ?.can_date_be_updated_next_payment && (
                                <div className="space-y-3">
                                    <DokanButton
                                        onClick={ updateScheduleHandler }
                                        loading={ isUpdatingSchedules }
                                        disabled={ isUpdatingSchedules }
                                    >
                                        { __( 'Update Schedule', 'dokan' ) }
                                    </DokanButton>
                                </div>
                            ) }
                        </div>
                    </Card.Body>
                </Card>

                <Card>
                    <Card.Header className="p-3">
                        <Card.Title className="m-0">
                            { __( 'Subscription Notes', 'dokan' ) }
                        </Card.Title>
                    </Card.Header>
                    <Card.Body className="p-3 text-sm">
                        <div className="space-y-4">
                            { /* Existing Notes */ }
                            { subscriptionNotes.map( ( note ) => (
                                <NoteItem
                                    key={ note.id }
                                    note={ note }
                                    deleteNote={ deleteNote }
                                />
                            ) ) }

                            { /* Add Note Section */ }
                            <div className="mt-6">
                                <div className="flex items-center gap-2 mb-2">
                                    <h4 className="font-medium">
                                        { __( 'Add note', 'dokan' ) }
                                    </h4>
                                </div>
                                <textarea
                                    value={ noteText }
                                    onChange={ ( e ) =>
                                        setNoteText( e.target.value )
                                    }
                                    className="w-full border rounded-lg p-2 min-h-[100px] mb-3"
                                    placeholder={ __(
                                        'Type your note here…',
                                        'dokan'
                                    ) }
                                />
                                <div className="flex gap-2">
                                    <SearchableSelect
                                        options={ getNoteTypes() }
                                        value={ getNoteTypes().find(
                                            ( item ) => item.value == noteType
                                        ) }
                                        onChange={ ( e ) => {
                                            setNoteType( e.value );
                                        } }
                                        placeholder={ __(
                                            'Select type',
                                            'dokan'
                                        ) }
                                        menuPortalTarget={ document.querySelector(
                                            '.dokan-layout'
                                        ) }
                                    />
                                    <DokanButton
                                        onClick={ createNewNote }
                                        loading={ isCreatingNore }
                                        disabled={ isCreatingNore }
                                    >
                                        { __( 'Add', 'dokan' ) }
                                    </DokanButton>
                                </div>
                            </div>
                        </div>
                    </Card.Body>
                </Card>
            </div>
        </div>
    );
};

export default SubscriptionBody;
