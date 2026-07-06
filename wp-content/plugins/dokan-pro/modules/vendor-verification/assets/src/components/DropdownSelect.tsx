import { EllipsisVertical } from 'lucide-react';
import { useState } from '@wordpress/element';
import { Icon, MenuItem, Popover } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

function DropdownSelect( {
    onChange,
}: {
    onChange: ( value: string ) => void;
} ) {
    const [ popoverAnchor, setPopoverAnchor ] = useState();
    const [ isVisible, setIsVisible ] = useState( false );
    return (
        <div>
            <button
                className="p-1 text-neutral-600 hover:bg-neutral-50 transition-colors border rounded"
                onClick={ () => {
                    setIsVisible( ! isVisible );
                } }
                ref={ setPopoverAnchor }
            >
                <EllipsisVertical className="w-5 h-5" />
            </button>

            { isVisible && (
                <Popover
                    animate
                    anchor={ popoverAnchor }
                    focusOnMount={ true }
                    onClose={ () => {
                        setIsVisible( ! isVisible );
                    } }
                    onFocusOutside={ () => {
                        setIsVisible( ! isVisible );
                    } }
                >
                    <MenuItem
                        onClick={ () => {
                            setIsVisible( ! isVisible );
                            onChange( 'approved' );
                        } }
                        icon={ <Icon icon="yes" /> }
                        iconPosition="left"
                    >
                        <span className="flex flex-row gap-3">
                            { __( 'Approve', 'dokan' ) }
                        </span>
                    </MenuItem>
                    <MenuItem
                        onClick={ () => {
                            setIsVisible( ! isVisible );
                            onChange( 'rejected' );
                        } }
                        icon={ <Icon icon="no" /> }
                        iconPosition="left"
                    >
                        <span className="flex flex-row gap-3">
                            { __( 'Reject', 'dokan' ) }
                        </span>
                    </MenuItem>
                </Popover>
            ) }
        </div>
    );
}

export default DropdownSelect;
