import { __ } from '@wordpress/i18n';
// @ts-ignore — package-style alias resolved by Pro webpack.
import { DokanButton } from '@dokan/components';
import { ChevronDown, ChevronUp, GripVertical, Trash2 } from 'lucide-react';
import { AddonOption, ProductAddon } from './types';
import { ADDON_TYPES, AddonIssue, SubFieldFlags } from './shared';
import AddonBody from './AddonBody';

interface AddonCardProps {
    addon: ProductAddon;
    isOpen: boolean;
    isDragging: boolean;
    isDropTarget: boolean;
    subFields?: SubFieldFlags;
    issues: AddonIssue[];
    titleTouched: boolean;
    onToggle: () => void;
    onRequestRemove: () => void;
    onDragStart: () => void;
    onDragEnter: () => void;
    onDrop: () => void;
    onDragEnd: () => void;
    onGripDown: () => void;
    onGripUp: () => void;
    onTitleBlur: () => void;
    onPatch: ( patch: Partial< ProductAddon > ) => void;
    onUpdateOptions: ( opts: AddonOption[] ) => void;
}

const AddonCard = ( {
    addon,
    isOpen,
    isDragging,
    isDropTarget,
    subFields,
    issues,
    titleTouched,
    onToggle,
    onRequestRemove,
    onDragStart,
    onDragEnter,
    onDrop,
    onDragEnd,
    onGripDown,
    onGripUp,
    onTitleBlur,
    onPatch,
    onUpdateOptions,
}: AddonCardProps ) => {
    const typeLabel =
        ADDON_TYPES.find( ( t ) => t.value === addon.type )?.label ??
        addon.type;

    return (
        // border-solid + shadow-sm keep all four edges visible against the section card background.
        <div
            className={ `rounded bg-white shadow-sm transition-[border-color,opacity] ${
                isDragging ? 'opacity-60' : ''
            }` }
            draggable={ isDragging }
            onDragStart={ onDragStart }
            onDragEnter={ onDragEnter }
            onDragOver={ ( e ) => e.preventDefault() }
            onDrop={ onDrop }
            onDragEnd={ onDragEnd }
        >
            <div
                className={ `rounded border border-solid  flex items-center justify-between px-3 py-2 bg-white ${
                    isDropTarget
                        ? 'border-blue-500 border-dashed'
                        : 'border-gray-200'
                } ${ isOpen && 'rounded-b-none' }` }
            >
                <span
                    className="cursor-grab text-gray-400 hover:text-gray-600 pr-1"
                    onMouseDown={ ( e ) => {
                        e.stopPropagation();
                        onGripDown();
                    } }
                    onMouseUp={ onGripUp }
                    aria-label={ __( 'Drag to reorder', 'dokan' ) }
                >
                    <GripVertical size={ 16 } />
                </span>
                <button
                    type="button"
                    onClick={ onToggle }
                    className="flex items-center gap-2 text-left flex-1 cursor-pointer bg-transparent p-0 outline-none"
                >
                    <span className="font-medium text-sm">
                        { addon.name || __( '(Untitled add-on)', 'dokan' ) }
                    </span>
                    <span className="text-xs text-gray-500">{ typeLabel }</span>
                    { ! isOpen && issues.length > 0 && (
                        <span className="text-xs font-medium text-red-600">
                            { __( 'Needs attention', 'dokan' ) }
                        </span>
                    ) }
                </button>
                <div className="flex items-center gap-2">
                    <DokanButton
                        type="button"
                        variant="primary"
                        onClick={ onRequestRemove }
                        className="flex items-center gap-1"
                    >
                        <Trash2 size={ 14 } />
                        { __( 'Remove', 'dokan' ) }
                    </DokanButton>
                    <button
                        type="button"
                        onClick={ onToggle }
                        className="text-gray-500 hover:text-gray-700 cursor-pointer bg-transparent border-0 p-0"
                        aria-label={
                            isOpen
                                ? __( 'Collapse', 'dokan' )
                                : __( 'Expand', 'dokan' )
                        }
                    >
                        { isOpen ? (
                            <ChevronUp size={ 16 } />
                        ) : (
                            <ChevronDown size={ 16 } />
                        ) }
                    </button>
                </div>
            </div>

            { isOpen && (
                <AddonBody
                    addon={ addon }
                    subFields={ subFields }
                    onPatch={ onPatch }
                    onUpdateOptions={ onUpdateOptions }
                    titleTouched={ titleTouched }
                    onTitleBlur={ onTitleBlur }
                />
            ) }
        </div>
    );
};

export default AddonCard;
