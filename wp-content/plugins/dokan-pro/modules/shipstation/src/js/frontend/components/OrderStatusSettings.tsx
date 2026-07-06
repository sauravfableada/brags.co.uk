import { useState, useEffect, useMemo } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { DokanButton, DokanTooltip as Tooltip } from '@dokan/components';
import { Card, SearchableSelect, useToast } from '@getdokan/dokan-ui';
import { OrderStatuses } from '../definition/OrderStatusTypes';
import OrderStatusSettingsSkeleton from './skeleton/OrderStatusSettingsSkeleton';

interface OrderStatusSettingsProps {
    vendorId?: number;
}

const OrderStatusSettings = ( { vendorId }: OrderStatusSettingsProps ) => {
    const toast = useToast();
    const [ loading, setLoading ] = useState( true );
    const [ exportOrderStatuses, setExportOrderStatuses ] = useState( [] );
    const [ shippedOrderStatus, setShippedOrderStatus ] = useState( {} );
    const [ exportStatusesInputErrors, setExportStatusesInputErrors ] =
        useState( [] );
    const [ submitButtonDisabled, setSubmitButtonDisabled ] = useState( false );

    const availableOrderStatuses = useMemo( () => {
        const statuses: { label: string; value: string }[] = [];

        for ( const [ key, value ] of Object.entries(
            // @ts-ignore
            window.dokanShipStation?.orderStatuses || {}
        ) ) {
            statuses.push( { label: value as string, value: key } );
        }

        return statuses;
    }, [] );

    useEffect( () => {
        const fetchOrderStatusSettings = async () => {
            try {
                const response: OrderStatuses = await apiFetch( {
                    path: `dokan/v1/shipstation/order-statuses/${ vendorId }`,
                    method: 'GET',
                } );

                if ( response.vendor_id ) {
                    const exportStatuses = response?.export_statuses.map(
                        ( statusKey ) => {
                            return {
                                label: getStatusLabelByKey( statusKey ),
                                value: statusKey,
                            };
                        }
                    );

                    const shippedStatus = {
                        label: getStatusLabelByKey( response?.shipped_status ),
                        value: response?.shipped_status,
                    };

                    setExportOrderStatuses( exportStatuses );
                    setShippedOrderStatus( shippedStatus );
                }
            } catch ( error ) {
                toast( {
                    type: 'error',
                    title:
                        __( 'Error getting order status settings: ', 'dokan' ) +
                        error?.message,
                } );
            } finally {
                setLoading( false );
            }
        };

        if ( vendorId ) {
            fetchOrderStatusSettings();
        }
    }, [ vendorId ] );

    useEffect( () => {
        if ( exportOrderStatuses.length < 1 ) {
            setSubmitButtonDisabled( true );
            setExportStatusesInputErrors( [
                __( 'Export order status is required', 'dokan' ),
            ] );
        } else {
            setSubmitButtonDisabled( false );
            setExportStatusesInputErrors( [] );
        }
    }, [ exportOrderStatuses ] );

    const getStatusLabelByKey = ( key ) => {
        const item = availableOrderStatuses.find(
            ( status ) => status.value === key
        );
        return item ? item.label : '';
    };

    const handleExportOrderStatusChange = ( selectedOptions ) => {
        setExportOrderStatuses( selectedOptions || [] );
    };

    const handleShippedOrderStatusChange = ( selectedOptions ) => {
        setShippedOrderStatus( selectedOptions || {} );
    };

    const handleFormSubmission = async () => {
        setLoading( true );

        const data = {
            vendor_id: vendorId,
            export_statuses: exportOrderStatuses.map(
                ( status ) => status.value
            ),
            // @ts-ignore
            shipped_status: shippedOrderStatus?.value,
        };

        try {
            const response: OrderStatuses = await apiFetch( {
                path: 'dokan/v1/shipstation/order-statuses',
                method: 'PUT',
                data,
            } );

            if ( response.vendor_id ) {
                toast( {
                    type: 'success',
                    title: __( 'Settings saved successfully.', 'dokan' ),
                } );
            }
        } catch ( error ) {
            toast( {
                type: 'error',
                title:
                    __( 'Error saving order status settings: ', 'dokan' ) +
                    error.message,
            } );
        } finally {
            setLoading( false );
        }
    };

    return (
        <>
            <Card>
                <Card.Header>
                    <Card.Title>{ __( 'Order Statuses', 'dokan' ) }</Card.Title>
                </Card.Header>
                <Card.Body>
                    { loading ? (
                        <OrderStatusSettingsSkeleton />
                    ) : (
                        <div className="flex flex-nowrap flex-col -mb-5">
                            <div className="w-full flex justify-start items-baseline mb-5 flex-col lg:flex-row">
                                <label
                                    className="w-full lg:w-3/12 mb-2 lg:mb-0"
                                    htmlFor="dokan-shipstation-export-order-statuses"
                                >
                                    { __( 'Export Order Statuses', 'dokan' ) }
                                    <Tooltip
                                        content={ __(
                                            'Define the order statuses you wish to export to ShipStation.',
                                            'dokan'
                                        ) }
                                        direction="bottom"
                                    >
                                        <span className="ml-2">
                                            <i className="fas fa-question-circle text-gray-400"></i>
                                        </span>
                                    </Tooltip>
                                </label>
                                <div className="w-full lg:w-7/12 shipstation-order-status-single-input">
                                    <SearchableSelect
                                        isMulti={ true }
                                        options={ availableOrderStatuses }
                                        value={ exportOrderStatuses }
                                        onChange={
                                            handleExportOrderStatusChange
                                        }
                                        isClearable={ false }
                                        errors={ exportStatusesInputErrors }
                                    />
                                </div>
                            </div>

                            <div className="w-full flex justify-start items-baseline mb-5 flex-col lg:flex-row">
                                <label
                                    className="w-full lg:w-3/12 mb-2 lg:mb-0"
                                    htmlFor="dokan-shipstation-export-order-statuses"
                                >
                                    { __( 'Shipped Order Status', 'dokan' ) }
                                    <Tooltip
                                        content={ __(
                                            'Define the order status you wish to update to once an order has been shipped via ShipStation. By default this is Completed.',
                                            'dokan'
                                        ) }
                                        direction="bottom"
                                    >
                                        <span className="ml-2">
                                            <i className="fas fa-question-circle text-gray-400"></i>
                                        </span>
                                    </Tooltip>
                                </label>
                                <div className="w-full lg:w-7/12 shipstation-order-status-single-input">
                                    <SearchableSelect
                                        options={ availableOrderStatuses }
                                        value={ shippedOrderStatus }
                                        onChange={
                                            handleShippedOrderStatusChange
                                        }
                                        required={ true }
                                    />
                                </div>
                            </div>

                            <div className="basis-full flex justify-start items-baseline mb-5">
                                <div className="basis-0 lg:basis-3/12"></div>
                                <div className="basis-7/12 shipstation-order-status-submit-input">
                                    <DokanButton
                                        variant="primary"
                                        label={ __( 'Save Changes', 'dokan' ) }
                                        onClick={ handleFormSubmission }
                                        disabled={ submitButtonDisabled }
                                    />
                                </div>
                            </div>
                        </div>
                    ) }
                </Card.Body>
            </Card>
        </>
    );
};

export default OrderStatusSettings;
