import { __, sprintf } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import {
    AddonOption,
    AddonPriceType,
    AddonRestriction,
    AddonTitleFormat,
    AddonType,
    ProductAddon,
} from './types';

// REST routes for serialize / unserialize round-trips with the legacy PHP format.
export const SERIALIZE_PATH = '/dokan/v1/vendor/product-addons/serialize';
export const UNSERIALIZE_PATH = '/dokan/v1/vendor/product-addons/unserialize';

// Per-sub-control config resolved by PHP from the saved Form Manager option.
export interface SubFieldConfig {
    visible?: boolean;
    required?: boolean;
    label?: string;
    description?: string;
    placeholder?: string;
}

export interface SubFieldFlags {
    title?: SubFieldConfig;
    type?: SubFieldConfig;
    display_as?: SubFieldConfig;
    format_title?: SubFieldConfig;
    description?: SubFieldConfig;
    description_text?: SubFieldConfig;
    required_field?: SubFieldConfig;
    restrictions?: SubFieldConfig;
    restrictions_min?: SubFieldConfig;
    restrictions_max?: SubFieldConfig;
    adjust_price?: SubFieldConfig;
    adjust_price_type?: SubFieldConfig;
    adjust_price_value?: SubFieldConfig;
    import_export?: SubFieldConfig;
}

export type SubKey = keyof SubFieldFlags;

export const isSubVisible = (
    flags: SubFieldFlags | undefined,
    key: SubKey
): boolean => flags?.[ key ]?.visible !== false;

export const isSubRequired = (
    flags: SubFieldFlags | undefined,
    key: SubKey
): boolean => true === flags?.[ key ]?.required;

export const subLabel = (
    flags: SubFieldFlags | undefined,
    key: SubKey,
    fallback: string
): string => flags?.[ key ]?.label || fallback;

export const subDescription = (
    flags: SubFieldFlags | undefined,
    key: SubKey
): string => flags?.[ key ]?.description || '';

export const subPlaceholder = (
    flags: SubFieldFlags | undefined,
    key: SubKey
): string => flags?.[ key ]?.placeholder || '';

export const ADDON_TYPES: { value: AddonType; label: string }[] = [
    { value: 'multiple_choice', label: __( 'Multiple Choice', 'dokan' ) },
    { value: 'checkbox', label: __( 'Checkboxes', 'dokan' ) },
    { value: 'custom_text', label: __( 'Short Text', 'dokan' ) },
    { value: 'custom_textarea', label: __( 'Long Text', 'dokan' ) },
    { value: 'file_upload', label: __( 'File Upload', 'dokan' ) },
    { value: 'custom_price', label: __( 'Customer Defined Price', 'dokan' ) },
    { value: 'input_multiplier', label: __( 'Quantity', 'dokan' ) },
    { value: 'heading', label: __( 'Heading', 'dokan' ) },
];

export const TITLE_FORMATS: { value: AddonTitleFormat; label: string }[] = [
    { value: 'label', label: __( 'Label', 'dokan' ) },
    { value: 'heading', label: __( 'Heading', 'dokan' ) },
    { value: 'hide', label: __( 'Hide', 'dokan' ) },
];

export const DISPLAY_FORMATS = [
    { value: 'select', label: __( 'Dropdowns', 'dokan' ) },
    { value: 'radiobutton', label: __( 'Radio Buttons', 'dokan' ) },
    { value: 'images', label: __( 'Images', 'dokan' ) },
];

export const RESTRICTION_TYPES: { value: AddonRestriction; label: string }[] = [
    { value: 'any_text', label: __( 'Any Text', 'dokan' ) },
    { value: 'only_letters', label: __( 'Only Letters', 'dokan' ) },
    { value: 'only_numbers', label: __( 'Only Numbers', 'dokan' ) },
    {
        value: 'only_letters_numbers',
        label: __( 'Only Letters and Numbers', 'dokan' ),
    },
    { value: 'email', label: __( 'Only Email Address', 'dokan' ) },
];

export const PRICE_TYPES: { value: AddonPriceType; label: string }[] = [
    { value: 'flat_fee', label: __( 'Flat Fee', 'dokan' ) },
    { value: 'quantity_based', label: __( 'Quantity Based', 'dokan' ) },
    { value: 'percentage_based', label: __( 'Percentage Based', 'dokan' ) },
];

// Per-type rules that gate what the body renders; mirrors the legacy switch in product-addon/assets/js/scripts.js.
export type AddonRules = {
    hasOptions: boolean;
    hasDisplayAs: boolean;
    hasRestrictionTypeInHeader: boolean;
    hasRestrictionsToggle: boolean;
    hasNumericRange: boolean;
    hasAdjustPrice: boolean;
    hasRequiredToggle: boolean;
    hasFormatTitle: boolean;
    restrictionLabel: string;
    minLabel: string;
    maxLabel: string;
};

const DEFAULTS: AddonRules = {
    hasOptions: false,
    hasDisplayAs: false,
    hasRestrictionTypeInHeader: false,
    hasRestrictionsToggle: false,
    hasNumericRange: false,
    hasAdjustPrice: false,
    hasRequiredToggle: true,
    hasFormatTitle: true,
    restrictionLabel: '',
    minLabel: '',
    maxLabel: '',
};

export const RULES: Record< AddonType, AddonRules > = {
    multiple_choice: { ...DEFAULTS, hasOptions: true, hasDisplayAs: true },
    checkbox: { ...DEFAULTS, hasOptions: true },
    custom_text: {
        ...DEFAULTS,
        hasRestrictionTypeInHeader: true,
        hasRestrictionsToggle: true,
        hasNumericRange: true,
        hasAdjustPrice: true,
        restrictionLabel: __( 'Limit character length', 'dokan' ),
        minLabel: __( 'Minimum characters', 'dokan' ),
        maxLabel: __( 'Maximum characters', 'dokan' ),
    },
    custom_textarea: {
        ...DEFAULTS,
        hasRestrictionsToggle: true,
        hasNumericRange: true,
        hasAdjustPrice: true,
        restrictionLabel: __( 'Limit character length', 'dokan' ),
        minLabel: __( 'Minimum characters', 'dokan' ),
        maxLabel: __( 'Maximum characters', 'dokan' ),
    },
    custom_price: {
        ...DEFAULTS,
        hasRestrictionsToggle: true,
        hasNumericRange: true,
        restrictionLabel: __( 'Limit price range', 'dokan' ),
        minLabel: __( 'Minimum price', 'dokan' ),
        maxLabel: __( 'Maximum price', 'dokan' ),
    },
    input_multiplier: {
        ...DEFAULTS,
        hasRestrictionsToggle: true,
        hasNumericRange: true,
        hasAdjustPrice: true,
        restrictionLabel: __( 'Limit quantity range', 'dokan' ),
        minLabel: __( 'Minimum quantity', 'dokan' ),
        maxLabel: __( 'Maximum quantity', 'dokan' ),
    },
    file_upload: { ...DEFAULTS, hasAdjustPrice: true },
    heading: { ...DEFAULTS, hasRequiredToggle: false, hasFormatTitle: false },
};

// w-full lives outside inputBase so explicit widths (w-32, flex-1, ...) aren't lost to Tailwind's source-order cascade.
export const inputBase =
    'rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';

export const inputCls = `w-full ${ inputBase }`;

export const inputInvalidCls =
    'w-full rounded border border-red-400 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500';

// Flex baseline keeps the (REQUIRED) badge on the same row as the field label.
export const labelCls =
    'flex items-baseline gap-1 text-sm font-medium text-gray-700 mb-1';

export const randomId = (): string =>
    Math.random().toString( 36 ).slice( 2, 10 );

export const blankOption = (): AddonOption => ( {
    label: '',
    price: '',
    image: '',
    price_type: 'flat_fee',
} );

export const blankAddon = ( position: number ): ProductAddon => ( {
    id: randomId(),
    name: '',
    title_format: 'label',
    description_enable: 0,
    description: '',
    type: 'multiple_choice',
    display: 'select',
    position,
    required: 0,
    restrictions: 0,
    restrictions_type: 'any_text',
    adjust_price: 0,
    price_type: 'flat_fee',
    price: '',
    min: 0,
    max: 0,
    options: [ blankOption() ],
} );

// Matches the dokan-lite CustomField (REQUIRED) marker; inline-block defeats the class's default `display:block` so the badge sits next to the label.
export const RequiredBadge = () => (
    <span
        className="dokan-form-field-label ml-1 text-gray-500"
        style={ { display: 'inline-block' } }
    >
        { __( '(REQUIRED)', 'dokan' ) }
    </span>
);

// Builds a Field label that pairs the resolved sub-control label with the optional (REQUIRED) marker.
export const labelWithRequired = (
    flags: SubFieldFlags | undefined,
    key: SubKey,
    fallback: string
): React.ReactNode => {
    const text = subLabel( flags, key, fallback );
    if ( ! isSubRequired( flags, key ) ) {
        return text;
    }
    return (
        <>
            { text }
            <RequiredBadge />
        </>
    );
};

interface FieldProps {
    label: React.ReactNode;
    error?: string;
    description?: string;
    className?: string;
    children: React.ReactNode;
}

export const Field = ( {
    label,
    error,
    description,
    className = '',
    children,
}: FieldProps ) => (
    <div className={ className }>
        <label className={ labelCls }>{ label }</label>
        { children }
        { description && (
            <p className="text-xs text-gray-500 mt-1">{ description }</p>
        ) }
        { error && <p className="text-xs text-red-600 mt-1">{ error }</p> }
    </div>
);

// --- Validation -------------------------------------------------------------

export interface AddonIssue {
    key: string;
    message: string;
}

// Short sentence-fragments reused by both the inline per-add-on errors
// ("Needs a price.") and the aggregated card message ("2 add-ons need a price.").
// Customisable via the `dokan_product_addon_validation_labels` filter.
export const getValidationLabels = (): Record< string, string > =>
    applyFilters( 'dokan_product_addon_validation_labels', {
        title: __( 'a title', 'dokan' ),
        description: __( 'a description', 'dokan' ),
        description_text: __( 'description text', 'dokan' ),
        required_field: __( 'to be marked as required', 'dokan' ),
        restrictions: __( 'restrictions enabled', 'dokan' ),
        restrictions_min: __( 'a minimum value', 'dokan' ),
        restrictions_max: __( 'a maximum value', 'dokan' ),
        adjust_price: __( 'price adjustment enabled', 'dokan' ),
        adjust_price_value: __( 'a price', 'dokan' ),
    } ) as Record< string, string >;

type AddonCheck = {
    key: string;
    // null => always enforced (title); otherwise gated by the admin sub-control.
    subKey: SubKey | null;
    predicate: ( addon: ProductAddon ) => boolean;
};

const TITLE_CHECK: AddonCheck = {
    key: 'title',
    subKey: null,
    predicate: ( a ) => ! a?.name || '' === String( a.name ).trim(),
};

// A required sub-control must be satisfied before Save unlocks: a required
// toggle (description / required_field / restrictions / adjust_price) must be
// enabled, and a required value it reveals (description text, the min/max range,
// the price) must be filled in. The problematic add-on is flagged with the
// card's "Needs attention" tag.
const REQUIRED_CHECKS: AddonCheck[] = [
    {
        key: 'description',
        subKey: 'description',
        predicate: ( a ) => ! a?.description_enable,
    },
    {
        key: 'description_text',
        subKey: 'description_text',
        predicate: ( a ) =>
            !! a?.description_enable &&
            '' === String( a?.description || '' ).trim(),
    },
    {
        key: 'required_field',
        subKey: 'required_field',
        predicate: ( a ) => ! a?.required,
    },
    {
        key: 'restrictions',
        subKey: 'restrictions',
        predicate: ( a ) => ! a?.restrictions,
    },
    {
        key: 'restrictions_min',
        subKey: 'restrictions_min',
        predicate: ( a ) =>
            !! a?.restrictions &&
            ( undefined === a?.min ||
                null === a?.min ||
                '' === String( a.min ) ),
    },
    {
        key: 'restrictions_max',
        subKey: 'restrictions_max',
        predicate: ( a ) =>
            !! a?.restrictions &&
            ( undefined === a?.max ||
                null === a?.max ||
                '' === String( a.max ) ),
    },
    {
        key: 'adjust_price',
        subKey: 'adjust_price',
        predicate: ( a ) => ! a?.adjust_price,
    },
    {
        key: 'adjust_price_value',
        subKey: 'adjust_price_value',
        predicate: ( a ) =>
            !! a?.adjust_price && '' === String( a?.price ?? '' ).trim(),
    },
];

const isCheckActive = (
    check: AddonCheck,
    subFields?: SubFieldFlags
): boolean => {
    if ( null === check.subKey ) {
        return true;
    }
    const cfg = subFields?.[ check.subKey ];
    return cfg?.visible !== false && true === cfg?.required;
};

// Per-add-on list of unmet requirements — drives the inline card errors.
// `labels` defaults to a fresh lookup but callers iterating many add-ons should
// pass a single precomputed map so the filter only runs once per pass.
export const getAddonIssues = (
    addon: ProductAddon,
    subFields?: SubFieldFlags,
    labels: Record< string, string > = getValidationLabels()
): AddonIssue[] => {
    const issues: AddonIssue[] = [];

    [ TITLE_CHECK, ...REQUIRED_CHECKS ].forEach( ( check ) => {
        if ( isCheckActive( check, subFields ) && check.predicate( addon ) ) {
            issues.push( {
                key: check.key,
                message: sprintf(
                    /* translators: %s: the missing requirement, e.g. "a price". */
                    __( 'Needs %s.', 'dokan' ),
                    labels[ check.key ] ?? check.key
                ),
            } );
        }
    } );

    return issues;
};

// Number of add-ons with at least one unmet requirement. The editor's own
// "needs attention" badge counts this whole repeater as one field, so we
// surface this accurate per-add-on count inside the card ourselves.
export const countAddonsNeedingAttention = (
    addons: ProductAddon[],
    subFields?: SubFieldFlags
): number => {
    if ( ! Array.isArray( addons ) ) {
        return 0;
    }
    const labels = getValidationLabels();
    return addons.filter(
        ( addon ) => getAddonIssues( addon, subFields, labels ).length > 0
    ).length;
};

// Single, generic message for the editor's field-level validity (it gates Save
// and drives the card's "needs attention" badge). The specifics live in the
// inline per-add-on errors; integrations can reword this via the filter.
export const getAddonsValidationMessage = (
    addons: ProductAddon[],
    subFields?: SubFieldFlags
): string | null => {
    const invalid = countAddonsNeedingAttention( addons, subFields );
    if ( 0 === invalid ) {
        return null;
    }

    const message = __(
        'Some add-ons need attention before you can save.',
        'dokan'
    );

    return (
        ( applyFilters(
            'dokan_product_addon_validation_message',
            message,
            addons,
            subFields,
            invalid
        ) as string ) || null
    );
};
