import TableRow from './TableRow';
import TableHeader from '../common/TableHeader';
import { useState } from '@wordpress/element';
import { DokanModal } from '@dokan/components';
import ActionButtons from '../common/ActionButtons';
import { __, _n, sprintf } from '@wordpress/i18n';
import { useTableRates } from '../hooks/useTableRates';
import TableSkeleton from '../common/TableSkeleton';

interface TableRatesProps {
    zoneId: number;
    instanceId: number;
}

const TableRates = ( { zoneId, instanceId }: TableRatesProps ) => {
    const [ showModal, setShowModal ] = useState( false );
    const {
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
    } = useTableRates( zoneId, instanceId );

    interface BaseColumn {
        key: string;
        label: string;
        tooltip: string;
        className?: string;
    }

    const getTableRateColumns = (): BaseColumn[] => [
        {
            key: 'shipping_class',
            label: __( 'Shipping Class', 'dokan' ),
            tooltip: __( 'Shipping class this rate applies to.', 'dokan' ),
        },
        {
            key: 'condition',
            label: __( 'Condition', 'dokan' ),
            tooltip: __( 'Condition vs. destination.', 'dokan' ),
        },
        {
            key: 'min_max',
            label: __( 'Min-Max', 'dokan' ),
            tooltip: __(
                'Bottom and top range for the selected condition.',
                'dokan'
            ),
        },
        {
            key: 'break',
            label: __( 'Break', 'dokan' ),
            tooltip: __(
                'Break at this point. For per-order rates, no rates other than this will be offered. For calculated rates, this will stop any further rates being matched.',
                'dokan'
            ),
        },
        {
            key: 'abort',
            label: __( 'Abort', 'dokan' ),
            tooltip: __(
                'Enable this option to disable all rates/this shipping method if this row matches any item/line/class being quoted.',
                'dokan'
            ),
        },
        {
            key: 'row_cost',
            label: __( 'Row cost', 'dokan' ),
            tooltip: __(
                'Cost for shipping the order, including tax.',
                'dokan'
            ),
        },
        {
            key: 'item_cost',
            label: __( 'Item cost', 'dokan' ),
            tooltip: __( 'Cost per item, including tax.', 'dokan' ),
        },
        {
            key: 'weight_cost',
            label: sprintf(
                /* translators: 1) Weight unit */
                __( '%1$s cost', 'dokan' ),
                // Assuming you have access to weight unit, otherwise use generic label
                dokanTableRateShippingHelper?.weight_unit ||
                    __( 'Weight', 'dokan' )
            ),
            tooltip: __( 'Cost per weight unit.', 'dokan' ),
        },
        {
            key: 'percent_cost',
            label: __(
                /* translators: % cost */
                '% cost',
                'dokan'
            ),
            tooltip: __( 'Percentage of total to charge.', 'dokan' ),
        },
        {
            key: 'label',
            label: __( 'Label', 'dokan' ),
            tooltip: __(
                'Label for the shipping method which the user will be presented.',
                'dokan'
            ),
        },
    ];

    const rateConditionOptions = [
        { value: '', label: __( 'None', 'dokan' ) },
        { value: 'price', label: __( 'Price', 'dokan' ) },
        { value: 'weight', label: __( 'Weight', 'dokan' ) },
        { value: 'items', label: __( 'Item count', 'dokan' ) },
        ...( dokanTableRateShippingHelper?.shipping_class?.length > 0
            ? [
                  {
                      value: 'items_in_class',
                      label: __( 'Item count (same class)', 'dokan' ),
                  },
              ]
            : [] ),
    ];

    const shippingClassOptions = [
        { value: '', label: __( 'Any class', 'dokan' ) },
        { value: '0', label: __( 'No class', 'dokan' ) },
        ...dokanTableRateShippingHelper?.shipping_class?.map(
            ( classItem ) => ( {
                value: String( classItem?.term_id ),
                label: classItem?.name,
            } )
        ),
    ];

    if ( isLoading ) {
        return <TableSkeleton columns={ 12 } />;
    }

    return (
        <div className="bg-white sm:gap-4">
            { /* Header section */ }
            <div className="py-6 px-1">
                <h3 className="text-base font-medium text-gray-900 mb-1">
                    { __( 'Table Rates', 'dokan' ) }
                </h3>
                <p className="text-sm text-gray-500">
                    { __(
                        'Define your table rates here in order of priority.',
                        'dokan'
                    ) }
                </p>
            </div>

            { /* Table section */ }
            <div className="w-full pr-1">
                <div className="bg-white shadow-md rounded-lg overflow-scroll max-h-[550px]">
                    <table
                        id="table-rates-shipping-table"
                        className="dataviews-view-table w-full text-sm text-left text-gray-500"
                    >
                        <TableHeader
                            onSelectAll={ handleSelectAll }
                            isAllSelected={
                                tableData.length > 0 &&
                                selectedRows.length === tableData.length
                            }
                            columns={ getTableRateColumns() }
                            dragTooltip={ __(
                                'Draggable distance rates.',
                                'dokan'
                            ) }
                        />
                        <tbody>
                            <TableRow
                                tableData={ tableData }
                                selectedRows={ selectedRows }
                                onSelectRow={ handleSelectRow }
                                onUpdate={ handleTableDataUpdate }
                                handleOrderUpdate={ handleOrderUpdate }
                                shippingClassOptions={ shippingClassOptions }
                                rateConditionOptions={ rateConditionOptions }
                            />
                        </tbody>
                    </table>
                </div>

                { /* Action buttons */ }
                <ActionButtons
                    onSave={ handleSave }
                    isSaving={ isSaving }
                    onAdd={ handleAddShippingRate }
                    onDuplicate={ handleDuplicateRows }
                    hasSelectedRows={ selectedRows.length > 0 }
                    onDelete={ () => setShowModal( true ) }
                    addButtonLabel={ __( 'Add Shipping Rate', 'dokan' ) }
                    saveButtonLabel={ __( 'Save Shipping Rates', 'dokan' ) }
                />

                { /* Delete confirmation modal */ }
                <DokanModal
                    isOpen={ showModal }
                    loading={ isLoading }
                    onConfirm={ handleDeleteRows }
                    onClose={ () => setShowModal( false ) }
                    namespace="table-rate-method-delete"
                    dialogHeader={ __(
                        'Confirm Shipping Rate Deletion',
                        'dokan'
                    ) }
                    confirmationTitle={ _n(
                        'Delete Table Rate',
                        'Delete Table Rates',
                        selectedRows?.length,
                        'dokan'
                    ) }
                    confirmationDescription={ __(
                        'Are you sure you want to delete the selected shipping rate(s)? This action cannot be undone and may affect how shipping is calculated for your customers.',
                        'dokan'
                    ) }
                />
            </div>
        </div>
    );
};

export default TableRates;
