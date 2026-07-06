import { useCallback, useMemo, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
// @ts-ignore — package-style alias resolved by Pro webpack.
import { DokanButton, DokanLink, DokanModal } from '@dokan/components';
import { Download, Plus, Upload } from 'lucide-react';
import { AddonOption, ProductAddon } from './types';
import AddonCard from './AddonCard';
import {
    SERIALIZE_PATH,
    SubFieldFlags,
    UNSERIALIZE_PATH,
    blankAddon,
    getAddonIssues,
    getValidationLabels,
    inputCls,
    isSubVisible,
    randomId,
} from './shared';

// Re-export so the variant registration in index.tsx and any external consumers keep their import paths.
export type { SubFieldFlags, SubFieldConfig, SubKey } from './shared';
export { isSubVisible, isSubRequired } from './shared';

interface EditProps {
    data: Record< string, any >;
    field: {
        id: string;
        label?: string;
        options?: { subFields?: SubFieldFlags };
    };
    onChange: ( changes: Record< string, any > ) => void;
}

declare const dokanProductEditorAddon: { globalAddonsUrl?: string } | undefined;

const ProductAddonsEdit = ( { data, field, onChange }: EditProps ) => {
    const addons: ProductAddon[] = useMemo(
        () => ( Array.isArray( data[ field.id ] ) ? data[ field.id ] : [] ),
        [ data, field.id ]
    );
    const subFields = field.options?.subFields;

    // Compute each add-on's issues once per change so we can flag each problematic
    // card with its own "Needs attention" tag (the editor's own badge counts this
    // whole repeater as a single field).
    const issuesByAddon = useMemo( () => {
        const labels = getValidationLabels();
        return addons.map( ( addon ) =>
            getAddonIssues( addon, subFields, labels )
        );
    }, [ addons, subFields ] );

    const [ expanded, setExpanded ] = useState< Record< string, boolean > >(
        {}
    );
    const [ titleTouched, setTitleTouched ] = useState<
        Record< string, boolean >
    >( {} );

    const update = useCallback(
        ( next: ProductAddon[] ) => {
            onChange( {
                [ field.id ]: next.map( ( addon, idx ) => ( {
                    ...addon,
                    position: idx,
                } ) ),
            } );
        },
        [ field.id, onChange ]
    );

    const updateAddon = ( index: number, patch: Partial< ProductAddon > ) => {
        const next = [ ...addons ];
        next[ index ] = { ...next[ index ], ...patch };
        update( next );
    };

    // Confirm-before-delete keeps a stray click from dropping a fully configured add-on.
    const [ pendingDelete, setPendingDelete ] = useState< {
        index: number;
        name: string;
    } | null >( null );

    const requestRemove = ( index: number ) => {
        setPendingDelete( {
            index,
            name: addons[ index ]?.name?.trim() || __( 'this add-on', 'dokan' ),
        } );
    };

    const cancelRemove = () => setPendingDelete( null );

    const confirmRemove = () => {
        if ( null === pendingDelete ) {
            return;
        }
        update( addons.filter( ( _, i ) => i !== pendingDelete.index ) );
        setPendingDelete( null );
    };

    const addAddon = () => {
        const fresh = blankAddon( addons.length );
        update( [ ...addons, fresh ] );
        setExpanded( ( prev ) => ( { ...prev, [ fresh.id ]: true } ) );
    };

    const toggle = ( id: string ) =>
        setExpanded( ( prev ) => ( { ...prev, [ id ]: ! prev[ id ] } ) );

    const updateOptions = ( index: number, options: AddonOption[] ) => {
        updateAddon( index, { options } );
    };

    // Card-level drag-and-drop reorder state.
    const dragIndexRef = useRef< number | null >( null );
    const [ dragOverIndex, setDragOverIndex ] = useState< number | null >(
        null
    );
    const [ draggingId, setDraggingId ] = useState< string | null >( null );

    const onCardDrop = () => {
        const from = dragIndexRef.current;
        const to = dragOverIndex;
        dragIndexRef.current = null;
        setDragOverIndex( null );
        setDraggingId( null );
        if ( from === null || to === null || from === to ) {
            return;
        }
        const next = [ ...addons ];
        const [ moved ] = next.splice( from, 1 );
        next.splice( to, 0, moved );
        update( next );
    };

    // Import / Export round-trip the legacy PHP serialize format via REST.
    const [ exportText, setExportText ] = useState< string | null >( null );
    const [ importText, setImportText ] = useState< string | null >( null );
    const [ importError, setImportError ] = useState< string | null >( null );
    const [ isExporting, setIsExporting ] = useState( false );
    const [ isImporting, setIsImporting ] = useState( false );

    const handleExport = async () => {
        setImportText( null );
        setImportError( null );
        setIsExporting( true );
        try {
            const res = ( await apiFetch( {
                path: SERIALIZE_PATH,
                method: 'POST',
                data: { addons },
            } ) ) as { data?: string };
            setExportText( res?.data ?? '' );
        } catch ( e: any ) {
            setExportText( '' );
            setImportError(
                e?.message || __( 'Failed to export add-ons.', 'dokan' )
            );
        } finally {
            setIsExporting( false );
        }
    };

    const handleOpenImport = () => {
        setExportText( null );
        setImportError( null );
        setImportText( '' );
    };

    const handleApplyImport = async () => {
        const raw = ( importText || '' ).trim();
        if ( ! raw ) {
            setImportError(
                __( 'Paste exported add-on data first.', 'dokan' )
            );
            return;
        }

        setIsImporting( true );
        try {
            const res = ( await apiFetch( {
                path: UNSERIALIZE_PATH,
                method: 'POST',
                data: { data: raw },
            } ) ) as { addons?: ProductAddon[] };

            const parsed = Array.isArray( res?.addons ) ? res.addons : null;
            if ( ! parsed ) {
                setImportError(
                    __( 'Expected an array of add-ons.', 'dokan' )
                );
                return;
            }
            update( [
                ...addons,
                ...parsed.map( ( a ) => ( { ...a, id: randomId() } ) ),
            ] );
            setImportText( null );
        } catch ( e: any ) {
            setImportError(
                e?.message ||
                    __(
                        'Could not parse the input as legacy serialize or JSON.',
                        'dokan'
                    )
            );
        } finally {
            setIsImporting( false );
        }
    };

    const globalAddonsUrl =
        ( typeof dokanProductEditorAddon !== 'undefined' &&
            dokanProductEditorAddon?.globalAddonsUrl ) ||
        '#';

    return (
        <div className="dokan-product-addons-editor space-y-3">
            { addons.length === 0 && (
                <p className="text-sm text-gray-500 italic">
                    { __(
                        'No add-on fields yet. Click “Add Field” to create one.',
                        'dokan'
                    ) }
                </p>
            ) }

            { addons.map( ( addon, index ) => (
                <AddonCard
                    key={ addon.id }
                    addon={ addon }
                    isOpen={ !! expanded[ addon.id ] }
                    isDragging={ draggingId === addon.id }
                    isDropTarget={
                        dragOverIndex === index && draggingId !== addon.id
                    }
                    subFields={ subFields }
                    issues={ issuesByAddon[ index ] }
                    titleTouched={ !! titleTouched[ addon.id ] }
                    onToggle={ () => toggle( addon.id ) }
                    onRequestRemove={ () => requestRemove( index ) }
                    onDragStart={ () => {
                        dragIndexRef.current = index;
                    } }
                    onDragEnter={ () => setDragOverIndex( index ) }
                    onDrop={ onCardDrop }
                    onDragEnd={ () => {
                        dragIndexRef.current = null;
                        setDragOverIndex( null );
                        setDraggingId( null );
                    } }
                    onGripDown={ () => setDraggingId( addon.id ) }
                    onGripUp={ () => setDraggingId( null ) }
                    onTitleBlur={ () =>
                        setTitleTouched( ( prev ) => ( {
                            ...prev,
                            [ addon.id ]: true,
                        } ) )
                    }
                    onPatch={ ( patch ) => updateAddon( index, patch ) }
                    onUpdateOptions={ ( opts ) => updateOptions( index, opts ) }
                />
            ) ) }

            <div className="flex items-center justify-between flex-wrap gap-2 pt-2">
                <DokanButton
                    type="button"
                    variant="secondary"
                    onClick={ addAddon }
                    className="flex items-center gap-1"
                >
                    <Plus size={ 14 } />
                    { __( 'Add Field', 'dokan' ) }
                </DokanButton>
                { isSubVisible( subFields, 'import_export' ) && (
                    <div className="flex items-center gap-2">
                        <DokanButton
                            type="button"
                            variant="secondary"
                            onClick={ handleOpenImport }
                            className="flex items-center gap-1"
                            disabled={ isExporting || isImporting }
                        >
                            <Upload size={ 14 } />
                            { __( 'Import', 'dokan' ) }
                        </DokanButton>
                        <DokanButton
                            type="button"
                            variant="secondary"
                            onClick={ handleExport }
                            className="flex items-center gap-1"
                            loading={ isExporting }
                            disabled={ isExporting || isImporting }
                        >
                            <Download size={ 14 } />
                            { __( 'Export', 'dokan' ) }
                        </DokanButton>
                    </div>
                ) }
            </div>

            { exportText !== null && (
                <div className="border border-solid border-gray-200 rounded p-3 space-y-2">
                    <div className="text-sm font-medium">
                        { __(
                            'Export — copy this to import elsewhere:',
                            'dokan'
                        ) }
                    </div>
                    <textarea
                        readOnly
                        className={ `${ inputCls } font-mono text-xs` }
                        rows={ 6 }
                        value={ exportText }
                        onFocus={ ( e ) => e.currentTarget.select() }
                    />
                    <DokanButton
                        type="button"
                        variant="secondary"
                        onClick={ () => setExportText( null ) }
                    >
                        { __( 'Close', 'dokan' ) }
                    </DokanButton>
                </div>
            ) }

            { importText !== null && (
                <div className="border border-solid border-gray-200 rounded p-3 space-y-2">
                    <div className="text-sm font-medium">
                        { __(
                            'Import — paste exported add-on data here:',
                            'dokan'
                        ) }
                    </div>
                    <textarea
                        className={ `${ inputCls } font-mono text-xs` }
                        rows={ 6 }
                        value={ importText }
                        onChange={ ( e ) => {
                            setImportText( e.target.value );
                            setImportError( null );
                        } }
                        placeholder={ __(
                            'Paste exported form data here and then save to import fields. The imported fields will be appended.',
                            'dokan'
                        ) }
                    />
                    { importError && (
                        <p className="text-xs text-red-600">{ importError }</p>
                    ) }
                    <div className="flex gap-2">
                        <DokanButton
                            type="button"
                            variant="primary"
                            onClick={ handleApplyImport }
                            loading={ isImporting }
                            disabled={ isImporting }
                        >
                            { __( 'Import', 'dokan' ) }
                        </DokanButton>
                        <DokanButton
                            type="button"
                            variant="secondary"
                            onClick={ () => setImportText( null ) }
                            disabled={ isImporting }
                        >
                            { __( 'Cancel', 'dokan' ) }
                        </DokanButton>
                    </div>
                </div>
            ) }

            <div className="border-t border-solid border-gray-200 pt-3 mt-2">
                <h4 className="text-sm font-semibold mb-1">
                    { __( 'Additional add-ons', 'dokan' ) }
                </h4>
                <p className="text-sm text-gray-600">
                    { __( 'You can create additional', 'dokan' ) }{ ' ' }
                    <DokanLink href={ globalAddonsUrl }>
                        { __( 'add-ons', 'dokan' ) }
                    </DokanLink>{ ' ' }
                    { __(
                        'that apply to all products or to certain categories.',
                        'dokan'
                    ) }
                </p>
            </div>

            <DokanModal
                isOpen={ null !== pendingDelete }
                namespace="dokan-product-addon-remove"
                onClose={ cancelRemove }
                dialogTitle={ __( 'Remove Add-on?', 'dokan' ) }
                onConfirm={ confirmRemove }
                confirmationTitle={ __( 'Remove Add-on?', 'dokan' ) }
                confirmationDescription={
                    pendingDelete
                        ? `${ __(
                              'Are you sure you want to remove',
                              'dokan'
                          ) } "${ pendingDelete.name }"? ${ __(
                              'This action cannot be undone.',
                              'dokan'
                          ) }`
                        : ''
                }
                confirmButtonText={ __( 'Remove', 'dokan' ) }
                confirmButtonVariant="primary"
            />
        </div>
    );
};

export default ProductAddonsEdit;
