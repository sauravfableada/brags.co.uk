export type AddonType =
    | 'multiple_choice'
    | 'checkbox'
    | 'custom_text'
    | 'custom_textarea'
    | 'file_upload'
    | 'custom_price'
    | 'input_multiplier'
    | 'heading';

export type AddonDisplay = 'select' | 'radiobutton' | 'images';

export type AddonTitleFormat = 'label' | 'heading' | 'hide';

export type AddonPriceType = 'flat_fee' | 'quantity_based' | 'percentage_based';

export type AddonRestriction =
    | 'any_text'
    | 'only_letters'
    | 'only_numbers'
    | 'only_letters_numbers'
    | 'email';

export interface AddonOption {
    label: string;
    price: string | number;
    image: string;
    price_type: AddonPriceType;
}

export interface ProductAddon {
    id: string;
    name: string;
    title_format: AddonTitleFormat;
    description_enable: 0 | 1;
    description: string;
    type: AddonType;
    display: AddonDisplay;
    position: number;
    required: 0 | 1;
    restrictions: 0 | 1;
    restrictions_type: AddonRestriction;
    adjust_price: 0 | 1;
    price_type: AddonPriceType;
    price: string | number;
    min: number;
    max: number;
    options?: AddonOption[];
}
