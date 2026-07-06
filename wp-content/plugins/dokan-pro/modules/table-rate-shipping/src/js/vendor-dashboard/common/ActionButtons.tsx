import { Button } from '@getdokan/dokan-ui';
import { __ } from '@wordpress/i18n';

interface ActionButtonsProps {
    onAdd: () => void;
    onDuplicate: () => void;
    onSave: () => void;
    onDelete: () => void;
    isSaving: boolean;
    hasSelectedRows: boolean;
    addButtonLabel?: string;
    duplicateButtonLabel?: string;
    saveButtonLabel?: string;
    deleteButtonLabel?: string;
}

/**
 * Common ActionButtons component for shipping rate tables
 *
 * @param {ActionButtonsProps} props Component props
 * @return {JSX.Element} ActionButtons component
 */
const ActionButtons = ( {
    onAdd,
    onDuplicate,
    onSave,
    onDelete,
    isSaving,
    hasSelectedRows,
    addButtonLabel = __( 'Add Rate', 'dokan' ),
    duplicateButtonLabel = __( 'Duplicate Selected Rows', 'dokan' ),
    saveButtonLabel = __( 'Save Rates', 'dokan' ),
    deleteButtonLabel = __( 'Delete Selected Rows', 'dokan' ),
}: ActionButtonsProps ) => {
    return (
        <div className="flex justify-end gap-2 mt-6">
            <Button
                onClick={ onAdd }
                color={ 'primary' }
                className={ 'dokan-btn' }
                label={ addButtonLabel }
            />
            <Button
                color={ 'primary' }
                onClick={ onDuplicate }
                className={ 'dokan-btn' }
                disabled={ ! hasSelectedRows }
                label={ duplicateButtonLabel }
            />
            <Button
                onClick={ onSave }
                color={ 'primary' }
                loading={ isSaving }
                className={ 'dokan-btn' }
                label={ saveButtonLabel }
            />
            <Button
                color={ 'primary' }
                onClick={ onDelete }
                className={ 'dokan-btn' }
                disabled={ ! hasSelectedRows }
                label={ deleteButtonLabel }
            />
        </div>
    );
};

export default ActionButtons;
