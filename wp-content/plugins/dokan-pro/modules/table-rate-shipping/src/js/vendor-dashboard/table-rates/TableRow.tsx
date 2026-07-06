import DragIcon from '../common/DragIcon';
import { __ } from '@wordpress/i18n';
import { TableRate } from '../definations';
import { SortableList } from '@dokan/components';
import {
    SearchableSelect,
    SimpleCheckbox,
    SimpleInput,
} from '@getdokan/dokan-ui';

interface TableRowProps {
    tableData: TableRate[];
    selectedRows: string[];
    handleOrderUpdate: ( updatedItems: TableRate[] ) => void;
    onSelectRow: ( rateOrder: string, checked: boolean ) => void;
    onUpdate: (
        rateOrder: string,
        field: keyof TableRate,
        value: string | number
    ) => void;
    shippingClassOptions: Array< { value: string; label: string } >;
    rateConditionOptions: Array< { value: string; label: string } >;
}

const TableRow = ( {
    tableData,
    selectedRows,
    handleOrderUpdate,
    onSelectRow,
    onUpdate,
    shippingClassOptions,
    rateConditionOptions,
}: TableRowProps ) => {
    return tableData?.length > 0 ? (
        <SortableList
            wrapperElement=""
            items={ tableData }
            onChange={ handleOrderUpdate }
            orderProperty={ 'rate_order' }
            dragSelector="draggable-rate-table"
            namespace={ 'sortable-table-rates' }
            renderItem={ ( row: TableRate ) => {
                const isAborted = parseInt( row?.rate_abort ) === 1;
                const handleCheckboxChange =
                    ( field: keyof TableRate ) => ( event ) => {
                        onUpdate(
                            row?.rate_order,
                            field,
                            event.target.checked ? '1' : '0'
                        );
                    };

                const handleInputChange =
                    ( field: keyof TableRate ) => ( event ) => {
                        onUpdate( row?.rate_order, field, event.target.value );
                    };

                const handleSelectChange =
                    ( field: keyof TableRate ) =>
                    ( option: { value: string; label: string } ) => {
                        onUpdate( row?.rate_order, field, option?.value );
                    };

                return (
                    <tr
                        key={ row?.rate_id }
                        className="bg-white border-b hover:bg-gray-50"
                    >
                        { /* Selection Checkbox */ }
                        <td className="py-4 pr-0 align-middle !pl-4">
                            <SimpleCheckbox
                                input={ {
                                    id: `select-row-${ row?.rate_order }`,
                                    name: `select-row-${ row?.rate_order }`,
                                    type: 'checkbox',
                                } }
                                checked={ selectedRows.includes(
                                    row?.rate_order
                                ) }
                                onChange={ ( e ) =>
                                    onSelectRow(
                                        row?.rate_order,
                                        e.target.checked
                                    )
                                }
                            />
                        </td>

                        { /* Drag Handle */ }
                        <td className="py-4 px-2.5 draggable-rate-table align-middle">
                            <DragIcon />
                        </td>

                        { /* Shipping Class */ }
                        <td className="py-4 px-2.5 align-middle">
                            <SearchableSelect
                                menuPortalTarget={ document.querySelector(
                                    '.dokan-layout'
                                ) }
                                menuPosition='fixed'
                                options={ shippingClassOptions }
                                onChange={ handleSelectChange( 'rate_class' ) }
                                value={ shippingClassOptions.find(
                                    ( option ) =>
                                        option?.value === row?.rate_class
                                ) }
                            />
                        </td>

                        { /* Rate Condition */ }
                        <td className="py-4 px-2.5 align-middle">
                            <SearchableSelect
                                menuPortalTarget={ document.querySelector(
                                    '.dokan-layout'
                                ) }
                                menuPosition='fixed'
                                options={ rateConditionOptions }
                                onChange={ handleSelectChange(
                                    'rate_condition'
                                ) }
                                value={ rateConditionOptions?.find(
                                    ( option ) =>
                                        option?.value === row?.rate_condition
                                ) }
                            />
                        </td>

                        { /* Min-Max Range */ }
                        <td className="py-4 px-2.5 align-middle">
                            <div className="flex flex-col gap-2">
                                <SimpleInput
                                    type="text"
                                    value={ row?.rate_min }
                                    disabled={ ! row.rate_condition }
                                    className="bg-white focus:bg-white min-w-14"
                                    placeholder={ __( 'n/a', 'dokan' ) }
                                    onChange={ handleInputChange( 'rate_min' ) }
                                />
                                <SimpleInput
                                    type="text"
                                    placeholder="n/a"
                                    value={ row?.rate_max }
                                    disabled={ ! row.rate_condition }
                                    className="bg-white focus:bg-white min-w-14"
                                    onChange={ handleInputChange( 'rate_max' ) }
                                />
                            </div>
                        </td>

                        { /* Break Checkbox */ }
                        <td className="py-4 px-2.5 align-middle">
                            <SimpleCheckbox
                                input={ {
                                    id: `table-rate-${ row?.rate_order }-break`,
                                    name: `${ row?.rate_order }-break`,
                                    type: 'checkbox',
                                } }
                                checked={ parseInt( row?.rate_priority ) === 1 }
                                onChange={ handleCheckboxChange(
                                    'rate_priority'
                                ) }
                            />
                        </td>

                        { /* Abort Checkbox */ }
                        <td className="py-4 px-2.5 align-middle">
                            <SimpleCheckbox
                                input={ {
                                    id: `table-rate-${ row?.rate_order }-abort`,
                                    name: `${ row?.rate_order }-abort`,
                                    type: 'checkbox',
                                } }
                                checked={ isAborted }
                                onChange={ handleCheckboxChange(
                                    'rate_abort'
                                ) }
                            />
                        </td>

                        { /* Conditional rendering based on abort status */ }
                        { isAborted ? (
                            <td
                                colSpan={ 4 }
                                className="py-4 px-2.5 align-middle"
                            >
                                <SimpleInput
                                    type="text"
                                    value={ row?.rate_abort_reason }
                                    className="bg-white focus:bg-white min-w-14"
                                    onChange={ handleInputChange(
                                        'rate_abort_reason'
                                    ) }
                                    placeholder={ __(
                                        'Enter abort reason',
                                        'dokan'
                                    ) }
                                />
                            </td>
                        ) : (
                            <>
                                { /* Row Cost */ }
                                <td className="py-4 px-2.5 align-middle">
                                    <SimpleInput
                                        type="text"
                                        placeholder="0.00"
                                        value={ row?.rate_cost }
                                        className="bg-white focus:bg-white min-w-14"
                                        onChange={ handleInputChange(
                                            'rate_cost'
                                        ) }
                                    />
                                </td>

                                { /* Item Cost */ }
                                <td className="py-4 px-2.5 align-middle">
                                    <SimpleInput
                                        type="text"
                                        placeholder="0.00"
                                        value={ row?.rate_cost_per_item }
                                        className="bg-white focus:bg-white min-w-14"
                                        onChange={ handleInputChange(
                                            'rate_cost_per_item'
                                        ) }
                                    />
                                </td>

                                { /* Weight Unit Cost */ }
                                <td className="py-4 px-2.5 align-middle">
                                    <SimpleInput
                                        type="text"
                                        placeholder="0.00"
                                        value={ row?.rate_cost_per_weight_unit }
                                        className="bg-white focus:bg-white min-w-14"
                                        onChange={ handleInputChange(
                                            'rate_cost_per_weight_unit'
                                        ) }
                                    />
                                </td>

                                { /* Percentage Cost */ }
                                <td className="py-4 px-2.5 align-middle">
                                    <SimpleInput
                                        type="text"
                                        placeholder="0.00"
                                        value={ row?.rate_cost_percent }
                                        className="bg-white focus:bg-white min-w-14"
                                        onChange={ handleInputChange(
                                            'rate_cost_percent'
                                        ) }
                                    />
                                </td>
                            </>
                        ) }

                        { /* Shipping Label */ }
                        <td className="py-4 px-2.5 align-middle">
                            <SimpleInput
                                type="text"
                                disabled={ isAborted }
                                value={ row?.rate_label }
                                className="bg-white focus:bg-white min-w-14"
                                onChange={ handleInputChange( 'rate_label' ) }
                                placeholder={ __(
                                    'Enter shipping label',
                                    'dokan'
                                ) }
                            />
                        </td>
                    </tr>
                );
            } }
        />
    ) : (
        <tr className="bg-white hover:bg-gray-50 text-center">
            <td colSpan={ 12 } className={ `text-center` }>
                { __( 'No shipping rates found', 'dokan' ) }
            </td>
        </tr>
    );
};

export default TableRow;
