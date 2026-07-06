import { useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
// @ts-ignore — package-style alias resolved by Pro webpack.
import { DokanButton } from '@dokan/components';
import { GripVertical, Plus, Trash2 } from 'lucide-react';
import { AddonOption, ProductAddon } from './types';
import { PRICE_TYPES, blankOption, inputBase, inputCls } from './shared';

interface OptionsEditorProps {
    options: AddonOption[];
    display: ProductAddon[ 'display' ];
    onChange: ( options: AddonOption[] ) => void;
}

const OptionsEditor = ( {
    options,
    display,
    onChange,
}: OptionsEditorProps ) => {
    const updateOption = ( i: number, patch: Partial< AddonOption > ) => {
        const next = [ ...options ];
        next[ i ] = { ...next[ i ], ...patch };
        onChange( next );
    };
    const removeOption = ( i: number ) =>
        onChange( options.filter( ( _, idx ) => idx !== i ) );
    const addOption = () => onChange( [ ...options, blankOption() ] );

    const dragIndexRef = useRef< number | null >( null );
    const [ dragOverIndex, setDragOverIndex ] = useState< number | null >(
        null
    );
    const [ draggingIndex, setDraggingIndex ] = useState< number | null >(
        null
    );

    const onRowDrop = () => {
        const from = dragIndexRef.current;
        const to = dragOverIndex;
        dragIndexRef.current = null;
        setDragOverIndex( null );
        setDraggingIndex( null );
        if ( from === null || to === null || from === to ) {
            return;
        }
        const next = [ ...options ];
        const [ moved ] = next.splice( from, 1 );
        next.splice( to, 0, moved );
        onChange( next );
    };

    return (
        <div className="border-t border-gray-200 -mx-4 px-4 pt-4">
            <div className="text-sm font-medium mb-2">
                { __( 'Options', 'dokan' ) }
            </div>
            <div className="space-y-2">
                { options.map( ( option, idx ) => {
                    const isDragging = draggingIndex === idx;
                    const isTarget = dragOverIndex === idx && ! isDragging;
                    return (
                        <div
                            key={ idx }
                            className={ `rounded transition-[border-color,opacity] ${
                                isTarget
                                    ? 'border border-dashed border-blue-500'
                                    : ''
                            } ${ isDragging ? 'opacity-60' : '' }` }
                            draggable={ draggingIndex === idx }
                            onDragStart={ () => {
                                dragIndexRef.current = idx;
                            } }
                            onDragEnter={ () => setDragOverIndex( idx ) }
                            onDragOver={ ( e ) => e.preventDefault() }
                            onDrop={ onRowDrop }
                            onDragEnd={ () => {
                                dragIndexRef.current = null;
                                setDragOverIndex( null );
                                setDraggingIndex( null );
                            } }
                        >
                            <div className="flex items-center gap-2 w-full">
                                <span
                                    className="shrink-0 cursor-grab text-gray-400 hover:text-gray-600"
                                    onMouseDown={ () =>
                                        setDraggingIndex( idx )
                                    }
                                    onMouseUp={ () => setDraggingIndex( null ) }
                                    aria-label={ __(
                                        'Drag to reorder option',
                                        'dokan'
                                    ) }
                                >
                                    <GripVertical size={ 16 } />
                                </span>
                                <input
                                    className={ `${ inputBase } flex-1 min-w-0` }
                                    type="text"
                                    value={ option.label }
                                    onChange={ ( e ) =>
                                        updateOption( idx, {
                                            label: e.target.value,
                                        } )
                                    }
                                    placeholder={ __(
                                        'Enter an option',
                                        'dokan'
                                    ) }
                                />
                                <select
                                    className={ `${ inputBase } shrink-0 w-36` }
                                    value={ option.price_type }
                                    onChange={ ( e ) =>
                                        updateOption( idx, {
                                            price_type: e.target
                                                .value as AddonOption[ 'price_type' ],
                                        } )
                                    }
                                >
                                    { PRICE_TYPES.map( ( opt ) => (
                                        <option
                                            key={ opt.value }
                                            value={ opt.value }
                                        >
                                            { opt.label }
                                        </option>
                                    ) ) }
                                </select>
                                <input
                                    className={ `${ inputBase } shrink-0 w-24` }
                                    type="number"
                                    step="0.01"
                                    value={ option.price }
                                    onChange={ ( e ) =>
                                        updateOption( idx, {
                                            price: e.target.value,
                                        } )
                                    }
                                    placeholder="0.00"
                                />
                                <button
                                    type="button"
                                    onClick={ () => removeOption( idx ) }
                                    className="shrink-0 cursor-pointer flex items-center justify-center rounded bg-red-50 text-red-500 hover:bg-red-100 hover:text-red-700 p-2 border-0"
                                    aria-label={ __(
                                        'Remove option',
                                        'dokan'
                                    ) }
                                >
                                    <Trash2 size={ 16 } />
                                </button>
                            </div>
                            { 'images' === display && (
                                <input
                                    className={ `${ inputCls } mt-2` }
                                    type="text"
                                    value={ option.image }
                                    onChange={ ( e ) =>
                                        updateOption( idx, {
                                            image: e.target.value,
                                        } )
                                    }
                                    placeholder={ __(
                                        'Image URL or attachment ID',
                                        'dokan'
                                    ) }
                                />
                            ) }
                        </div>
                    );
                } ) }
            </div>
            <DokanButton
                type="button"
                variant="primary"
                onClick={ addOption }
                className="mt-4 inline-flex items-center gap-1 text-white"
            >
                <Plus size={ 14 } className="text-white" />
                { __( 'Add Option', 'dokan' ) }
            </DokanButton>
        </div>
    );
};

export default OptionsEditor;
