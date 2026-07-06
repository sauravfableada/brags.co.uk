import { ProductAddon } from './types';
import { Field, RequiredBadge, inputCls } from './shared';

interface RestrictionsSectionProps {
    addon: ProductAddon;
    label: string;
    helperText?: string;
    parentRequired?: boolean;
    minLabel: string;
    maxLabel: string;
    minPlaceholder?: string;
    maxPlaceholder?: string;
    showMin: boolean;
    showMax: boolean;
    minRequired?: boolean;
    maxRequired?: boolean;
    showRange: boolean;
    onPatch: ( patch: Partial< ProductAddon > ) => void;
}

const RestrictionsSection = ( {
    addon,
    label,
    helperText,
    parentRequired,
    minLabel,
    maxLabel,
    minPlaceholder,
    maxPlaceholder,
    showMin,
    showMax,
    minRequired,
    maxRequired,
    showRange,
    onPatch,
}: RestrictionsSectionProps ) => (
    <div className="border-t border-gray-200 -mx-4 px-4 pt-4 space-y-3">
        <div>
            <label className="flex items-center gap-2 text-sm">
                <input
                    type="checkbox"
                    checked={ !! addon.restrictions }
                    onChange={ ( e ) =>
                        onPatch( { restrictions: e.target.checked ? 1 : 0 } )
                    }
                />
                { label }
                { parentRequired && <RequiredBadge /> }
            </label>
            { helperText && (
                <p className="text-xs text-gray-500 ml-6 mt-1">
                    { helperText }
                </p>
            ) }
        </div>

        { !! addon.restrictions && showRange && ( showMin || showMax ) && (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                { showMin && (
                    <Field
                        label={
                            <>
                                { minLabel }
                                { minRequired && <RequiredBadge /> }
                            </>
                        }
                    >
                        <input
                            className={ inputCls }
                            type="number"
                            min={ 0 }
                            value={ addon.min }
                            placeholder={ minPlaceholder || '0' }
                            onChange={ ( e ) =>
                                onPatch( { min: Number( e.target.value ) } )
                            }
                        />
                    </Field>
                ) }
                { showMax && (
                    <Field
                        label={
                            <>
                                { maxLabel }
                                { maxRequired && <RequiredBadge /> }
                            </>
                        }
                    >
                        <input
                            className={ inputCls }
                            type="number"
                            min={ 0 }
                            value={ addon.max }
                            placeholder={ maxPlaceholder || '999' }
                            onChange={ ( e ) =>
                                onPatch( { max: Number( e.target.value ) } )
                            }
                        />
                    </Field>
                ) }
            </div>
        ) }
    </div>
);

export default RestrictionsSection;
