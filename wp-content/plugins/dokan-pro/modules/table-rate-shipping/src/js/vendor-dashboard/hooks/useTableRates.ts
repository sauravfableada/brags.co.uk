import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useToast } from '@getdokan/dokan-ui';
import {
    TableRate,
    UseTableRatesReturn,
    DistanceRate,
    UseDistanceRatesReturn,
} from '../definations';

export const useTableRates = (
    zoneId: string,
    instanceId: string
): UseTableRatesReturn => {
    const [ selectedRows, setSelectedRows ] = useState< string[] >( [] );
    const [ tableData, setTableData ] = useState< TableRate[] >( [] );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ isSaving, setIsSaving ] = useState( false );
    const toast = useToast();

    // Fetch table rates on mount
    const fetchTableRates = useCallback( async () => {
        try {
            const response = await apiFetch< TableRate[] >( {
                path: `/dokan/v1/shipping/table-rate/rates/zone/${ zoneId }/instance/${ instanceId }`,
            } );
            setTableData( response );
        } catch ( error: any ) {
            toast( {
                type: 'error',
                title: __( 'Error loading table rates', 'dokan' ),
                subtitle: error.message,
            } );
        } finally {
            setIsLoading( false );
        }
    }, [ zoneId, instanceId ] );

    useEffect( () => {
        fetchTableRates();
    }, [ fetchTableRates ] );

    // Selection handlers
    const handleSelectAll = useCallback(
        ( checked: boolean ) => {
            setSelectedRows(
                checked ? tableData.map( ( row ) => row.rate_order ) : []
            );
        },
        [ tableData ]
    );

    const handleSelectRow = useCallback(
        ( rateOrder: string, checked: boolean ) => {
            setSelectedRows( ( prev ) => {
                if ( checked ) {
                    return [ ...prev, rateOrder ];
                }
                return prev.filter( ( orderId ) => orderId !== rateOrder );
            } );
        },
        []
    );

    // Row operations
    const handleDuplicateRows = useCallback( () => {
        if ( selectedRows.length === 0 ) return;

        const duplicatedRows = selectedRows
            .map( ( selectedRateOrder, index ) => {
                const originalRow = tableData.find(
                    ( row ) => row.rate_order === selectedRateOrder
                );
                if ( ! originalRow ) return null;

                return {
                    ...originalRow,
                    rate_id: '0', // New row
                    rate_order: String( tableData.length + index ),
                };
            } )
            .filter( Boolean ) as TableRate[];

        setTableData( ( prev ) => [ ...prev, ...duplicatedRows ] );
        setSelectedRows( [] ); // Clear selection after duplication
    }, [ selectedRows, tableData ] );

    const handleAddShippingRate = useCallback( () => {
        const newRow: TableRate = {
            rate_id: '0',
            zone_id: zoneId,
            instance_id: instanceId,
            rate_class: '',
            rate_condition: '',
            rate_min: '',
            rate_max: '',
            rate_cost: '',
            rate_cost_per_item: '',
            rate_cost_per_weight_unit: '',
            rate_cost_percent: '',
            rate_label: '',
            rate_priority: '0',
            rate_order: String( tableData.length ),
            rate_abort: '0',
            rate_abort_reason: '',
        };

        setTableData( ( prev ) => [ ...prev, newRow ] );
    }, [ tableData.length, zoneId, instanceId ] );

    const handleDeleteRows = useCallback( async () => {
        if ( selectedRows.length === 0 ) return;

        // Filter out rows that are already stored in the database.
        const rowsToDelete = tableData.filter(
            ( row ) =>
                selectedRows.includes( row.rate_order ) &&
                parseInt( row.rate_id ) > 0
        );

        // Optimistic update
        setTableData( ( prev ) =>
            prev.filter( ( row ) => ! selectedRows.includes( row.rate_order ) )
        );
        setSelectedRows( [] );

        // API call for existing records
        if ( rowsToDelete.length > 0 ) {
            try {
                await apiFetch( {
                    path: `/dokan/v1/shipping/table-rate/rates/zone/${ zoneId }/instance/${ instanceId }`,
                    method: 'DELETE',
                    data: {
                        rate_ids: rowsToDelete.map( ( row ) => row.rate_id ),
                    },
                } );

                toast( {
                    type: 'success',
                    title: __(
                        'Selected shipping rates deleted successfully',
                        'dokan'
                    ),
                } );
            } catch ( error: any ) {
                // Revert on error
                setTableData( ( prev ) => [ ...prev, ...rowsToDelete ] );
                toast( {
                    type: 'error',
                    title: __( 'Error deleting shipping rates', 'dokan' ),
                    subtitle: error.message,
                } );
            }
        }
    }, [ selectedRows, tableData, zoneId, instanceId ] );

    // Data update handlers
    const handleTableDataUpdate = useCallback(
        ( rateOrder: string, field: keyof TableRate, value: string ) => {
            setTableData( ( prev ) =>
                prev.map( ( item ) =>
                    item.rate_order === rateOrder
                        ? { ...item, [ field ]: value }
                        : item
                )
            );
        },
        []
    );

    const handleOrderUpdate = useCallback( ( updatedItems: TableRate[] ) => {
        const reorderedItems = updatedItems.map( ( item, index ) => ( {
            ...item,
            rate_order: String( index ),
        } ) );
        setTableData( [ ...reorderedItems ] );
    }, [] );

    // Helper function to prepare table rate data.
    const prepareTableRateData = ( tableData ) => {
        if ( ! tableData?.length ) {
            return {};
        }

        const fieldMapping = {
            rate_id: 'rate_ids',
            rate_class: 'shipping_class',
            rate_condition: 'shipping_condition',
            rate_min: 'shipping_min',
            rate_max: 'shipping_max',
            rate_cost: 'shipping_cost',
            rate_cost_per_item: 'shipping_per_item',
            rate_cost_per_weight_unit: 'shipping_cost_per_weight',
            rate_cost_percent: 'cost_percent',
            rate_label: 'shipping_label',
            rate_priority: 'shipping_priority',
            rate_abort: 'shipping_abort',
            rate_abort_reason: 'shipping_abort_reason',
        };

        return Object.entries( fieldMapping ).reduce(
            ( acc, [ tableKey, apiKey ] ) => {
                acc[ apiKey ] = tableData.map( ( item ) => item[ tableKey ] );
                return acc;
            },
            {}
        );
    };

    // Save all changes
    const handleSave = useCallback( async () => {
        setIsSaving( true );
        try {
            await apiFetch( {
                path: `/dokan/v1/shipping/table-rate/rates/zone/${ zoneId }/instance/${ instanceId }`,
                method: 'POST',
                data: {
                    preparedData: prepareTableRateData( tableData ),
                    zone_id: zoneId,
                    instance_id: instanceId,
                },
            } ).then( ( response ) => {
                setTableData( [ ...response.rates.data ] );
                toast( {
                    type: 'success',
                    title: __( 'Table rates saved successfully', 'dokan' ),
                } );
            } );
        } catch ( error: any ) {
            toast( {
                type: 'error',
                title: __( 'Error saving table rates', 'dokan' ),
                subtitle: error.message,
            } );
        } finally {
            setIsSaving( false );
        }
    }, [ tableData, zoneId, instanceId ] );

    return {
        selectedRows,
        tableData,
        isLoading,
        isSaving,
        handleSelectAll,
        handleSelectRow,
        handleDuplicateRows,
        handleAddShippingRate,
        handleDeleteRows,
        handleTableDataUpdate,
        handleSave,
        handleOrderUpdate,
    };
};

export const useDistanceRates = (
    zoneId: string,
    instanceId: string
): UseDistanceRatesReturn => {
    const [ selectedRows, setSelectedRows ] = useState< string[] >( [] );
    const [ tableData, setTableData ] = useState< DistanceRate[] >( [] );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ isSaving, setIsSaving ] = useState( false );
    const toast = useToast();

    // Memoize API endpoints to prevent unnecessary re-renders
    const apiEndpoints = {
        rates: `/dokan/v1/shipping/distance-rate/rates/zone/${ zoneId }/instance/${ instanceId }`,
    };

    // Fetch distance rates on mount
    const fetchDistanceRates = useCallback( async () => {
        if ( ! zoneId || ! instanceId ) return;

        try {
            const response = await apiFetch< DistanceRate[] >( {
                path: apiEndpoints.rates,
            } );
            setTableData( response || [] );
        } catch ( error: any ) {
            toast( {
                type: 'error',
                title: __( 'Error loading distance rates', 'dokan' ),
                subtitle: error.message,
            } );
        } finally {
            setIsLoading( false );
        }
    }, [ zoneId, instanceId ] );

    useEffect( () => {
        fetchDistanceRates();
    }, [ fetchDistanceRates ] );

    // Selection handlers
    const handleSelectAll = useCallback(
        ( checked: boolean ) => {
            setSelectedRows(
                checked ? tableData.map( ( row ) => row.rate_order ) : []
            );
        },
        [ tableData ]
    );

    const handleSelectRow = useCallback(
        ( rateOrder: string, checked: boolean ) => {
            setSelectedRows( ( prev ) => {
                const newSelection = new Set( prev );
                if ( checked ) {
                    newSelection.add( rateOrder );
                } else {
                    newSelection.delete( rateOrder );
                }
                return Array.from( newSelection );
            } );
        },
        []
    );

    // Row operations
    const handleDuplicateRows = useCallback( () => {
        if ( selectedRows.length === 0 ) return;

        const duplicatedRows = selectedRows.reduce< DistanceRate[] >(
            ( acc, selectedRateOrder, index ) => {
                const originalRow = tableData.find(
                    ( row ) => row.rate_order === selectedRateOrder
                );
                if ( originalRow ) {
                    acc.push( {
                        ...originalRow,
                        rate_id: '0', // New row
                        rate_order: String( tableData.length + index ),
                    } );
                }
                return acc;
            },
            []
        );

        if ( duplicatedRows.length > 0 ) {
            setTableData( ( prev ) => [ ...prev, ...duplicatedRows ] );
            setSelectedRows( [] ); // Clear selection after duplication
        }
    }, [ selectedRows, tableData ] );

    const handleAddDistanceRate = useCallback( () => {
        const newRow: DistanceRate = {
            rate_id: '0',
            zone_id: zoneId,
            instance_id: instanceId,
            vendor_id: '',
            rate_condition: 'distance',
            rate_min: '',
            rate_max: '',
            rate_cost: '',
            rate_cost_unit: '',
            rate_fee: '',
            rate_break: '0',
            rate_abort: '0',
            rate_order: String( tableData.length ),
        };

        setTableData( ( prev ) => [ ...prev, newRow ] );
    }, [ tableData.length, zoneId, instanceId ] );

    const handleDeleteRows = useCallback( async () => {
        if ( selectedRows.length === 0 ) return;

        // Filter out rows that are already stored in the database.
        const rowsToDelete = tableData.filter(
            ( row ) =>
                selectedRows.includes( row.rate_order ) &&
                parseInt( row.rate_id ) > 0
        );

        const deletedRateIds = new Set( selectedRows );

        // Optimistic update
        setTableData( ( prev ) =>
            prev.filter( ( row ) => ! deletedRateIds.has( row.rate_order ) )
        );
        setSelectedRows( [] );

        // API call for existing records
        if ( rowsToDelete.length > 0 ) {
            try {
                await apiFetch( {
                    path: apiEndpoints.rates,
                    method: 'DELETE',
                    data: {
                        rate_ids: rowsToDelete.map( ( row ) => row.rate_id ),
                    },
                } );

                toast( {
                    type: 'success',
                    title: __(
                        'Selected distance rates deleted successfully',
                        'dokan'
                    ),
                } );
            } catch ( error: any ) {
                // Revert on error
                setTableData( ( prev ) => [ ...prev, ...rowsToDelete ] );
                toast( {
                    type: 'error',
                    title: __( 'Error deleting distance rates', 'dokan' ),
                    subtitle: error.message,
                } );
            }
        }
    }, [ selectedRows, tableData, apiEndpoints.rates, toast ] );

    // Data update handlers
    const handleTableDataUpdate = useCallback(
        ( rateOrder: string, field: keyof DistanceRate, value: string ) => {
            setTableData( ( prev ) =>
                prev.map( ( item ) =>
                    item.rate_order === rateOrder
                        ? { ...item, [ field ]: value }
                        : item
                )
            );
        },
        []
    );

    const handleOrderUpdate = useCallback( ( updatedItems: DistanceRate[] ) => {
        const reorderedItems = updatedItems.map( ( item, index ) => ( {
            ...item,
            rate_order: String( index ),
        } ) );
        setTableData( [ ...reorderedItems ] );
    }, [] );

    // Helper function to prepare distance rate data.
    const prepareDistanceRateData = useCallback(
        ( tableData: DistanceRate[] ) => {
            if ( ! tableData?.length ) {
                return {};
            }

            const fieldMapping = {
                rate_id: 'rate_ids',
                rate_condition: 'rate_condition',
                rate_min: 'rate_min',
                rate_max: 'rate_max',
                rate_cost: 'rate_cost',
                rate_cost_unit: 'rate_cost_unit',
                rate_fee: 'rate_fee',
                rate_break: 'rate_break',
                rate_abort: 'rate_abort',
            } as const;

            return Object.entries( fieldMapping ).reduce(
                ( acc, [ tableKey, apiKey ] ) => {
                    acc[ apiKey ] = tableData.map(
                        ( item ) => item[ tableKey as keyof DistanceRate ]
                    );
                    return acc;
                },
                {} as Record< string, any >
            );
        },
        []
    );

    // Save all changes
    const handleSave = useCallback( async () => {
        if ( isSaving ) return; // Prevent double submission

        setIsSaving( true );
        try {
            const response = await apiFetch( {
                path: apiEndpoints.rates,
                method: 'POST',
                data: {
                    preparedData: prepareDistanceRateData( tableData ),
                    zone_id: zoneId,
                    instance_id: instanceId,
                },
            } );

            if ( response?.rates?.data ) {
                setTableData( [ ...response.rates.data ] );
            }

            toast( {
                type: 'success',
                title: __( 'Distance rates saved successfully', 'dokan' ),
            } );
        } catch ( error: any ) {
            toast( {
                type: 'error',
                title: __( 'Error saving distance rates', 'dokan' ),
                subtitle: error.message,
            } );
        } finally {
            setIsSaving( false );
        }
    }, [
        tableData,
        zoneId,
        instanceId,
        isSaving,
        apiEndpoints.rates,
        prepareDistanceRateData,
        toast,
    ] );

    return {
        selectedRows,
        tableData,
        isLoading,
        isSaving,
        handleSelectAll,
        handleSelectRow,
        handleDuplicateRows,
        handleAddDistanceRate,
        handleDeleteRows,
        handleTableDataUpdate,
        handleSave,
        handleOrderUpdate,
    };
};
