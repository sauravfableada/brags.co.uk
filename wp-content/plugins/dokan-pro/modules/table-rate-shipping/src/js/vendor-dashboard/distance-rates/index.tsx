import { DistanceTableRow } from './DistanceTableRow';
import { useState } from '@wordpress/element';
import { DokanModal } from '@dokan/components';
import { __, _n } from '@wordpress/i18n';
import { useDistanceRates } from '../hooks/useTableRates';
import ActionButtons from '../common/ActionButtons';
import TableHeader from '../common/TableHeader';
import TableSkeleton from '../common/TableSkeleton';

interface DistanceRatesProps {
    zoneId: number;
    instanceId: number;
}

const DistanceRates = ( { zoneId, instanceId }: DistanceRatesProps ) => {
    const [ showModal, setShowModal ] = useState( false );
    const {
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
    } = useDistanceRates( zoneId, instanceId );

    interface BaseColumn {
        key: string;
        label: string;
        tooltip: string;
        className?: string;
    }

    const getDistanceRateColumns = (): BaseColumn[] => [
        {
            key: 'condition',
            label: __( 'Condition', 'dokan' ),
            tooltip: __(
                'On what condition must the rule be applied.',
                'dokan'
            ),
        },
        {
            key: 'min',
            label: __( 'Min', 'dokan' ),
            tooltip: __(
                'Minimum condition value, leave blank for no limit. Travel time based in minutes.',
                'dokan'
            ),
        },
        {
            key: 'max',
            label: __( 'Max', 'dokan' ),
            tooltip: __(
                'Maximum condition value, leave blank for no limit. Travel time based in minutes.',
                'dokan'
            ),
        },
        {
            key: 'base_cost',
            label: __( 'Base Cost', 'dokan' ),
            tooltip: __(
                'Base cost for rule, excluding tax. Other calculations will be added on top of this cost.',
                'dokan'
            ),
        },
        {
            key: 'cost_per_distance',
            label: __( 'Cost Per Distance/Minute', 'dokan' ),
            tooltip: __(
                'Cost per distance unit, or cost per minute for total travel time, excluding tax. Will be added to Base cost.',
                'dokan'
            ),
        },
        {
            key: 'handling_fee',
            label: __( 'Handling Fee', 'dokan' ),
            tooltip: __(
                'Fee excluding tax. Enter an amount, e.g. 2.50, or a percentage, e.g. 5%. Will be added to Base cost.',
                'dokan'
            ),
        },
        {
            key: 'break',
            label: __( 'Break', 'dokan' ),
            tooltip: __(
                'Check to not continue processing rules below the selected rule.',
                'dokan'
            ),
        },
        {
            key: 'abort',
            label: __( 'Abort', 'dokan' ),
            tooltip: __(
                'Check to disable the shipping method if the rule matches.',
                'dokan'
            ),
        },
    ];

    const rateConditionOptions = [
        { value: 'distance', label: __( 'Distance', 'dokan' ) },
        { value: 'time', label: __( 'Total Travel Time', 'dokan' ) },
        { value: 'weight', label: __( 'Weight', 'dokan' ) },
        { value: 'total', label: __( 'Order Total', 'dokan' ) },
        { value: 'quantity', label: __( 'Quantity', 'dokan' ) },
    ];

    if ( isLoading ) {
        return <TableSkeleton />;
    }

    return (
        <div className="bg-white sm:gap-4">
            { /* Header section */ }
            <div className="py-6 px-1">
                <h3 className="text-base font-medium text-gray-900 mb-1">
                    { __( 'Distance Rates', 'dokan' ) }
                </h3>
                <p className="text-sm text-gray-500">
                    { __(
                        'Define your distance rates here in order of priority.',
                        'dokan'
                    ) }
                </p>
            </div>

            { /* Table section */ }
            <div className="w-full pr-1">
                <div className="bg-white shadow-md rounded-lg overflow-scroll max-h-[550px]">
                    <table
                        id="distance-rates-shipping-table"
                        className="dataviews-view-table w-full text-sm text-left text-gray-500"
                    >
                        <TableHeader
                            onSelectAll={ handleSelectAll }
                            isAllSelected={
                                tableData.length > 0 &&
                                selectedRows.length === tableData.length
                            }
                            columns={ getDistanceRateColumns() }
                            dragTooltip={ __(
                                'Draggable distance rates.',
                                'dokan'
                            ) }
                        />

                        <tbody>
                            <DistanceTableRow
                                tableData={ tableData }
                                selectedRows={ selectedRows }
                                onSelectRow={ handleSelectRow }
                                onUpdate={ handleTableDataUpdate }
                                handleOrderUpdate={ handleOrderUpdate }
                                rateConditionOptions={ rateConditionOptions }
                            />
                        </tbody>
                    </table>
                </div>

                { /* Action buttons */ }
                <ActionButtons
                    onSave={ handleSave }
                    isSaving={ isSaving }
                    onAdd={ handleAddDistanceRate }
                    onDuplicate={ handleDuplicateRows }
                    hasSelectedRows={ selectedRows.length > 0 }
                    onDelete={ () => setShowModal( true ) }
                    addButtonLabel={ __( 'Add Distance Rate', 'dokan' ) }
                    saveButtonLabel={ __( 'Save Distance Rates', 'dokan' ) }
                />

                { /* Delete confirmation modal */ }
                <DokanModal
                    isOpen={ showModal }
                    loading={ isLoading }
                    onConfirm={ handleDeleteRows }
                    onClose={ () => setShowModal( false ) }
                    namespace="distance-rate-method-delete"
                    confirmationTitle={ _n(
                        'Delete Distance Rate',
                        'Delete Distance Rates',
                        selectedRows?.length,
                        'dokan'
                    ) }
                    confirmationDescription={ __(
                        'Are you sure you want to delete the selected distance rate(s)? This action cannot be undone and may affect how shipping is calculated for your customers.',
                        'dokan'
                    ) }
                />
            </div>
        </div>
    );
};

export default DistanceRates;
