import { RawHTML } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { RequiredBadge } from './shared';

// Custom checkbox Edit for `_product_addons_exclude_global` so its (REQUIRED) badge stays inline; reads `rawLabel` because `getFieldConfig` wraps `label` in JSX upstream.

interface ExcludeGlobalEditProps {
    data: Record< string, any >;
    field: {
        id: string;
        rawLabel?: string;
        label?: React.ReactNode;
        description?: React.ReactNode;
        required?: boolean;
    };
    onChange: ( changes: Record< string, any > ) => void;
}

const ExcludeGlobalEdit = ( {
    data,
    field,
    onChange,
}: ExcludeGlobalEditProps ) => {
    const checked = !! data[ field.id ];
    let labelText = '';
    if ( typeof field.rawLabel === 'string' ) {
        labelText = field.rawLabel;
    } else if ( typeof field.label === 'string' ) {
        labelText = field.label;
    }

    return (
        <div className="flex flex-col gap-1">
            <label className="flex items-center gap-2 text-sm">
                <input
                    type="checkbox"
                    checked={ checked }
                    onChange={ ( e ) =>
                        onChange( { [ field.id ]: e.target.checked } )
                    }
                />
                <span className="flex items-baseline gap-1">
                    { labelText ? (
                        <RawHTML className="dokan-form-field-label">
                            { decodeEntities( labelText ) }
                        </RawHTML>
                    ) : null }
                    { field.required && <RequiredBadge /> }
                </span>
            </label>
            { field.description ? (
                <div className="text-xs text-gray-500 ml-6">
                    { field.description }
                </div>
            ) : null }
        </div>
    );
};

export default ExcludeGlobalEdit;
