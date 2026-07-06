import { __ } from '@wordpress/i18n';
import {
    AddonOption,
    AddonRestriction,
    AddonTitleFormat,
    AddonType,
    ProductAddon,
} from './types';
import {
    ADDON_TYPES,
    DISPLAY_FORMATS,
    Field,
    RESTRICTION_TYPES,
    RULES,
    RequiredBadge,
    SubFieldFlags,
    TITLE_FORMATS,
    inputCls,
    inputInvalidCls,
    isSubRequired,
    isSubVisible,
    labelWithRequired,
    subDescription,
    subLabel,
    subPlaceholder,
} from './shared';
import OptionsEditor from './OptionsEditor';
import PriceAdjustment from './PriceAdjustment';
import RestrictionsSection from './RestrictionsSection';

interface AddonBodyProps {
    addon: ProductAddon;
    subFields?: SubFieldFlags;
    onPatch: ( patch: Partial< ProductAddon > ) => void;
    onUpdateOptions: ( opts: AddonOption[] ) => void;
    titleTouched: boolean;
    onTitleBlur: () => void;
}

const AddonBody = ( {
    addon,
    subFields,
    onPatch,
    onUpdateOptions,
    titleTouched,
    onTitleBlur,
}: AddonBodyProps ) => {
    const rules = RULES[ addon.type ];
    const titleInvalid = titleTouched && '' === ( addon.name || '' ).trim();

    const showDisplayAs =
        rules.hasDisplayAs && isSubVisible( subFields, 'display_as' );
    const showFormatTitle =
        rules.hasFormatTitle && isSubVisible( subFields, 'format_title' );
    const showDescription = isSubVisible( subFields, 'description' );
    const showRequiredToggle =
        rules.hasRequiredToggle && isSubVisible( subFields, 'required_field' );
    const showRestrictions =
        rules.hasRestrictionsToggle &&
        isSubVisible( subFields, 'restrictions' );
    const showAdjustPrice =
        rules.hasAdjustPrice && isSubVisible( subFields, 'adjust_price' );

    // Legacy hides min/max when the short-text restriction is "email".
    const showRangeRow =
        rules.hasNumericRange &&
        ! (
            'custom_text' === addon.type && 'email' === addon.restrictions_type
        );

    const headerExtras = [
        showDisplayAs && (
            <Field
                key="display"
                label={ labelWithRequired(
                    subFields,
                    'display_as',
                    __( 'Display as', 'dokan' )
                ) }
                description={ subDescription( subFields, 'display_as' ) }
            >
                <select
                    className={ inputCls }
                    value={ addon.display }
                    onChange={ ( e ) =>
                        onPatch( {
                            display: e.target
                                .value as ProductAddon[ 'display' ],
                        } )
                    }
                >
                    { DISPLAY_FORMATS.map( ( opt ) => (
                        <option key={ opt.value } value={ opt.value }>
                            { opt.label }
                        </option>
                    ) ) }
                </select>
            </Field>
        ),
        rules.hasRestrictionTypeInHeader && (
            <Field key="rt" label={ __( 'Restriction', 'dokan' ) }>
                <select
                    className={ inputCls }
                    value={ addon.restrictions_type }
                    onChange={ ( e ) =>
                        onPatch( {
                            restrictions_type: e.target
                                .value as AddonRestriction,
                        } )
                    }
                >
                    { RESTRICTION_TYPES.map( ( opt ) => (
                        <option key={ opt.value } value={ opt.value }>
                            { opt.label }
                        </option>
                    ) ) }
                </select>
            </Field>
        ),
    ].filter( Boolean );

    const typeSpanFull = 0 === headerExtras.length;
    const titleSpanFull = ! showFormatTitle;

    // Description textarea is admin-controlled separately from the parent toggle.
    const showDescriptionText = isSubVisible( subFields, 'description_text' );

    return (
        <div className="p-4 space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Field
                    label={ labelWithRequired(
                        subFields,
                        'type',
                        __( 'Type', 'dokan' )
                    ) }
                    description={ subDescription( subFields, 'type' ) }
                    className={ typeSpanFull ? 'md:col-span-2' : '' }
                >
                    <select
                        className={ inputCls }
                        value={ addon.type }
                        onChange={ ( e ) =>
                            onPatch( {
                                type: e.target.value as AddonType,
                            } )
                        }
                    >
                        { ADDON_TYPES.map( ( opt ) => (
                            <option key={ opt.value } value={ opt.value }>
                                { opt.label }
                            </option>
                        ) ) }
                    </select>
                </Field>

                { headerExtras }

                <Field
                    label={
                        <>
                            { subLabel(
                                subFields,
                                'title',
                                __( 'Title', 'dokan' )
                            ) }
                            <RequiredBadge />
                        </>
                    }
                    description={ subDescription( subFields, 'title' ) }
                    className={ titleSpanFull ? 'md:col-span-2' : '' }
                    error={
                        titleInvalid
                            ? __( 'Title is required.', 'dokan' )
                            : undefined
                    }
                >
                    <input
                        className={ titleInvalid ? inputInvalidCls : inputCls }
                        type="text"
                        value={ addon.name }
                        onBlur={ onTitleBlur }
                        onChange={ ( e ) =>
                            onPatch( { name: e.target.value } )
                        }
                        placeholder={
                            subPlaceholder( subFields, 'title' ) ||
                            __( 'Add a label', 'dokan' )
                        }
                        required
                        aria-invalid={ titleInvalid || undefined }
                    />
                </Field>

                { showFormatTitle && (
                    <Field
                        label={ labelWithRequired(
                            subFields,
                            'format_title',
                            __( 'Format title', 'dokan' )
                        ) }
                        description={ subDescription(
                            subFields,
                            'format_title'
                        ) }
                    >
                        <select
                            className={ inputCls }
                            value={ addon.title_format }
                            onChange={ ( e ) =>
                                onPatch( {
                                    title_format: e.target
                                        .value as AddonTitleFormat,
                                } )
                            }
                        >
                            { TITLE_FORMATS.map( ( opt ) => (
                                <option key={ opt.value } value={ opt.value }>
                                    { opt.label }
                                </option>
                            ) ) }
                        </select>
                    </Field>
                ) }
            </div>

            { showDescription && (
                <div>
                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={ !! addon.description_enable }
                            onChange={ ( e ) =>
                                onPatch( {
                                    description_enable: e.target.checked
                                        ? 1
                                        : 0,
                                } )
                            }
                        />
                        { subLabel(
                            subFields,
                            'description',
                            __( 'Add description', 'dokan' )
                        ) }
                        { isSubRequired( subFields, 'description' ) && (
                            <RequiredBadge />
                        ) }
                    </label>
                    { subDescription( subFields, 'description' ) && (
                        <p className="text-xs text-gray-500 ml-6 mt-1">
                            { subDescription( subFields, 'description' ) }
                        </p>
                    ) }
                    { !! addon.description_enable && showDescriptionText && (
                        <Field
                            className="mt-2"
                            label={ labelWithRequired(
                                subFields,
                                'description_text',
                                __( 'Description text', 'dokan' )
                            ) }
                            description={ subDescription(
                                subFields,
                                'description_text'
                            ) }
                        >
                            <textarea
                                className={ inputCls }
                                rows={ 2 }
                                value={ addon.description }
                                onChange={ ( e ) =>
                                    onPatch( { description: e.target.value } )
                                }
                                placeholder={ subPlaceholder(
                                    subFields,
                                    'description_text'
                                ) }
                            />
                        </Field>
                    ) }
                </div>
            ) }

            { showRequiredToggle && (
                <div>
                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={ !! addon.required }
                            onChange={ ( e ) =>
                                onPatch( {
                                    required: e.target.checked ? 1 : 0,
                                } )
                            }
                        />
                        { subLabel(
                            subFields,
                            'required_field',
                            __( 'Required field', 'dokan' )
                        ) }
                        { isSubRequired( subFields, 'required_field' ) && (
                            <RequiredBadge />
                        ) }
                    </label>
                    { subDescription( subFields, 'required_field' ) && (
                        <p className="text-xs text-gray-500 ml-6 mt-1">
                            { subDescription( subFields, 'required_field' ) }
                        </p>
                    ) }
                </div>
            ) }

            { rules.hasOptions && (
                <OptionsEditor
                    options={ addon.options || [] }
                    display={ showDisplayAs ? addon.display : 'select' }
                    onChange={ onUpdateOptions }
                />
            ) }

            { showRestrictions && (
                <RestrictionsSection
                    addon={ addon }
                    label={ subLabel(
                        subFields,
                        'restrictions',
                        rules.restrictionLabel
                    ) }
                    helperText={ subDescription( subFields, 'restrictions' ) }
                    parentRequired={ isSubRequired(
                        subFields,
                        'restrictions'
                    ) }
                    minLabel={ subLabel(
                        subFields,
                        'restrictions_min',
                        rules.minLabel
                    ) }
                    maxLabel={ subLabel(
                        subFields,
                        'restrictions_max',
                        rules.maxLabel
                    ) }
                    minPlaceholder={ subPlaceholder(
                        subFields,
                        'restrictions_min'
                    ) }
                    maxPlaceholder={ subPlaceholder(
                        subFields,
                        'restrictions_max'
                    ) }
                    showMin={ isSubVisible( subFields, 'restrictions_min' ) }
                    showMax={ isSubVisible( subFields, 'restrictions_max' ) }
                    minRequired={ isSubRequired(
                        subFields,
                        'restrictions_min'
                    ) }
                    maxRequired={ isSubRequired(
                        subFields,
                        'restrictions_max'
                    ) }
                    showRange={ showRangeRow }
                    onPatch={ onPatch }
                />
            ) }

            { showAdjustPrice && (
                <PriceAdjustment
                    addon={ addon }
                    label={ subLabel(
                        subFields,
                        'adjust_price',
                        __( 'Adjust price', 'dokan' )
                    ) }
                    helperText={ subDescription( subFields, 'adjust_price' ) }
                    parentRequired={ isSubRequired(
                        subFields,
                        'adjust_price'
                    ) }
                    showPriceType={ isSubVisible(
                        subFields,
                        'adjust_price_type'
                    ) }
                    showPriceValue={ isSubVisible(
                        subFields,
                        'adjust_price_value'
                    ) }
                    priceTypeLabel={ subLabel(
                        subFields,
                        'adjust_price_type',
                        __( 'Price type', 'dokan' )
                    ) }
                    priceValueLabel={ subLabel(
                        subFields,
                        'adjust_price_value',
                        __( 'Price', 'dokan' )
                    ) }
                    priceValuePlaceholder={ subPlaceholder(
                        subFields,
                        'adjust_price_value'
                    ) }
                    priceTypeRequired={ isSubRequired(
                        subFields,
                        'adjust_price_type'
                    ) }
                    priceValueRequired={ isSubRequired(
                        subFields,
                        'adjust_price_value'
                    ) }
                    onPatch={ onPatch }
                />
            ) }
        </div>
    );
};

export default AddonBody;
