import DragIcon from './DragIcon';
import { __ } from '@wordpress/i18n';
import { SimpleCheckbox } from '@getdokan/dokan-ui';
import { DokanTooltip as Tooltip } from '@dokan/components';

// Common interfaces for header columns
interface BaseColumn {
    key: string;
    label: string;
    tooltip: string;
    className?: string;
}

interface TableHeaderProps {
    onSelectAll: ( checked: boolean ) => void;
    isAllSelected: boolean;
    columns: BaseColumn[];
    dragTooltip?: string;
}

const TableHeader = ( {
    onSelectAll,
    isAllSelected,
    columns,
    dragTooltip = __( 'Draggable rates.', 'dokan' ),
}: TableHeaderProps ) => (
    <thead className="text-xs text-gray-700 uppercase bg-gray-50">
        <tr>
            { /* Select All Checkbox */ }
            <th scope="col" className="bg-gray-50 py-4 pr-0 !pl-4">
                <SimpleCheckbox
                    input={ {
                        id: 'select-all-rows',
                        name: 'select-all-rows',
                        type: 'checkbox',
                    } }
                    onChange={ ( e ) => onSelectAll( e.target.checked ) }
                    checked={ isAllSelected }
                />
            </th>

            { /* Drag Handle Column */ }
            <th scope="col" className="bg-gray-50 py-4 px-2.5">
                <Tooltip
                    className="ml-2 text-gray-400 self-center"
                    content={ dragTooltip }
                >
                    <span>
                        <DragIcon className="fill-gray-900 cursor-default" />
                    </span>
                </Tooltip>
            </th>

            { /* Dynamic Columns */ }
            { columns.map( ( column ) => (
                <th
                    key={ column.key }
                    scope="col"
                    className={ `bg-gray-50 py-4 px-2.5 ${
                        column.className || ''
                    }` }
                >
                    <Tooltip
                        className="ml-2 text-gray-400 self-center"
                        content={ column.tooltip }
                    >
                        <span>{ column.label }</span>
                    </Tooltip>
                </th>
            ) ) }
        </tr>
    </thead>
);

export default TableHeader;
