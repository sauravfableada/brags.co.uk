import { __ } from '@wordpress/i18n';
import { DistanceRate } from '../definations';
import { SortableList } from '@dokan/components';
import {
    SearchableSelect,
    SimpleCheckbox,
    SimpleInput,
} from '@getdokan/dokan-ui';
import DragIcon from '../common/DragIcon';

interface DistanceTableRowProps {
    tableData: DistanceRate[];
    selectedRows: string[];
    handleOrderUpdate: ( updatedItems: DistanceRate[] ) => void;
    onSelectRow: ( rateOrder: string, checked: boolean ) => void;
    onUpdate: (
        rateOrder: string,
        field: keyof DistanceRate,
        value: string | number
    ) => void;
    rateConditionOptions: Array< { value: string; label: string } >;
}

export const DistanceTableRow = ( {
    tableData,
    selectedRows,
    handleOrderUpdate,
    onSelectRow,
    onUpdate,
    rateConditionOptions,
}: DistanceTableRowProps ) => {
    return tableData?.length > 0 ? (
        <SortableList
            wrapperElement=""
            items={ tableData }
            onChange={ handleOrderUpdate }
            orderProperty={ 'rate_order' }
            dragSelector="draggable-distance-rate-table"
            namespace={ 'sortable-distance-rates' }
            renderItem={ ( row: DistanceRate ) => {
                const isAborted = parseInt( row?.rate_abort ) === 1;
                const handleCheckboxChange =
                    ( field: keyof DistanceRate ) => ( event ) => {
                        onUpdate(
                            row?.rate_order,
                            field,
                            event.target.checked ? '1' : '0'
                        );
                    };

                const handleInputChange =
                    ( field: keyof DistanceRate ) => ( event ) => {
                        onUpdate( row?.rate_order, field, event.target.value );
                    };

                const handleSelectChange =
                    ( field: keyof DistanceRate ) =>
                    ( option: { value: string; label: string } ) => {
                        onUpdate( row?.rate_order, field, option?.value );
                    };

                return (
                    <tr
                        key={ row?.rate_id }
                        className={ `bg-white border-b hover:bg-gray-50` }
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
                        <td className="py-4 px-2.5 draggable-distance-rate-table align-middle">
                            <DragIcon />
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
                                className={ `min-w-28` }
                            />
                        </td>

                        { /* Min Value */ }
                        <td className="py-4 px-2.5 align-middle">
                            <SimpleInput
                                type="number"
                                step="0.01"
                                value={ row?.rate_min }
                                className={ `bg-white focus:bg-white min-w-14` }
                                placeholder={ __( 'Min', 'dokan' ) }
                                onChange={ handleInputChange( 'rate_min' ) }
                            />
                        </td>

                        { /* Max Value */ }
                        <td className="py-4 px-2.5 align-middle">
                            <SimpleInput
                                type="number"
                                step="0.01"
                                value={ row?.rate_max }
                                className={ `bg-white focus:bg-white min-w-14` }
                                placeholder={ __( 'Max', 'dokan' ) }
                                onChange={ handleInputChange( 'rate_max' ) }
                            />
                        </td>

                        { /* Base Cost */ }
                        <td className="py-4 px-2.5 align-middle">
                            <SimpleInput
                                type="number"
                                step="0.01"
                                placeholder="0.00"
                                value={ row?.rate_cost }
                                className={ `bg-white focus:bg-white min-w-14` }
                                onChange={ handleInputChange( 'rate_cost' ) }
                            />
                        </td>

                        { /* Cost Per Unit */ }
                        <td className="py-4 px-2.5 align-middle">
                            <SimpleInput
                                type="number"
                                step="0.01"
                                placeholder="0.00"
                                value={ row?.rate_cost_unit }
                                className={ `bg-white focus:bg-white min-w-14` }
                                onChange={ handleInputChange(
                                    'rate_cost_unit'
                                ) }
                            />
                        </td>

                        { /* Handling Fee */ }
                        <td className="py-4 px-2.5 align-middle">
                            <SimpleInput
                                type="number"
                                step="0.01"
                                placeholder="0.00"
                                value={ row?.rate_fee }
                                className={ `bg-white focus:bg-white min-w-14` }
                                onChange={ handleInputChange( 'rate_fee' ) }
                            />
                        </td>

                        { /* Break Checkbox */ }
                        <td className="py-4 px-2.5 align-middle">
                            <SimpleCheckbox
                                input={ {
                                    id: `distance-rate-${ row?.rate_order }-break`,
                                    name: `${ row?.rate_order }-break`,
                                    type: 'checkbox',
                                } }
                                checked={ parseInt( row?.rate_break ) === 1 }
                                onChange={ handleCheckboxChange(
                                    'rate_break'
                                ) }
                            />
                        </td>

                        { /* Abort Checkbox */ }
                        <td className="py-4 px-2.5 align-middle">
                            <SimpleCheckbox
                                input={ {
                                    id: `distance-rate-${ row?.rate_order }-abort`,
                                    name: `${ row?.rate_order }-abort`,
                                    type: 'checkbox',
                                } }
                                checked={ isAborted }
                                onChange={ handleCheckboxChange(
                                    'rate_abort'
                                ) }
                            />
                        </td>
                    </tr>
                );
            } }
        />
    ) : (
        <tr className="bg-white hover:bg-gray-50 text-center">
            <td colSpan={ 10 } className={ `text-center` }>
                { __( 'No distance rates found', 'dokan' ) }
            </td>
        </tr>
    );
};

export default DistanceTableRow;
