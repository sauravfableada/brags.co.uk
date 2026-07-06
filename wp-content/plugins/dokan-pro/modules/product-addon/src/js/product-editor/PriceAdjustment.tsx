import { AddonPriceType, ProductAddon } from './types';
import { Field, PRICE_TYPES, RequiredBadge, inputCls } from './shared';

interface PriceAdjustmentProps {
    addon: ProductAddon;
    label: string;
    helperText?: string;
    parentRequired?: boolean;
    showPriceType: boolean;
    showPriceValue: boolean;
    priceTypeLabel: string;
    priceValueLabel: string;
    priceValuePlaceholder?: string;
    priceTypeRequired?: boolean;
    priceValueRequired?: boolean;
    onPatch: ( patch: Partial< ProductAddon > ) => void;
}

const PriceAdjustment = ( {
    addon,
    label,
    helperText,
    parentRequired,
    showPriceType,
    showPriceValue,
    priceTypeLabel,
    priceValueLabel,
    priceValuePlaceholder,
    priceTypeRequired,
    priceValueRequired,
    onPatch,
}: PriceAdjustmentProps ) => (
    <div className="border-t border-gray-200 -mx-4 px-4 pt-4 space-y-3">
        <div>
            <label className="flex items-center gap-2 text-sm">
                <input
                    type="checkbox"
                    checked={ !! addon.adjust_price }
                    onChange={ ( e ) =>
                        onPatch( { adjust_price: e.target.checked ? 1 : 0 } )
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

        { !! addon.adjust_price && ( showPriceType || showPriceValue ) && (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                { showPriceType && (
                    <Field
                        label={
                            <>
                                { priceTypeLabel }
                                { priceTypeRequired && <RequiredBadge /> }
                            </>
                        }
                    >
                        <select
                            className={ inputCls }
                            value={ addon.price_type }
                            onChange={ ( e ) =>
                                onPatch( {
                                    price_type: e.target
                                        .value as AddonPriceType,
                                } )
                            }
                        >
                            { PRICE_TYPES.map( ( opt ) => (
                                <option key={ opt.value } value={ opt.value }>
                                    { opt.label }
                                </option>
                            ) ) }
                        </select>
                    </Field>
                ) }
                { showPriceValue && (
                    <Field
                        label={
                            <>
                                { priceValueLabel }
                                { priceValueRequired && <RequiredBadge /> }
                            </>
                        }
                    >
                        <input
                            className={ inputCls }
                            type="text"
                            inputMode="decimal"
                            value={ addon.price }
                            onChange={ ( e ) =>
                                onPatch( { price: e.target.value } )
                            }
                            placeholder={ priceValuePlaceholder || '0.00' }
                        />
                    </Field>
                ) }
            </div>
        ) }
    </div>
);

export default PriceAdjustment;
