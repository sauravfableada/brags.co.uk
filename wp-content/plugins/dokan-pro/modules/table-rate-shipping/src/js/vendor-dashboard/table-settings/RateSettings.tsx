import { __ } from '@wordpress/i18n';
import { TableRateSettingsData } from '../definations';
import { SearchableSelect, SimpleInput } from '@getdokan/dokan-ui';

interface RateSettingsProps {
    settings: TableRateSettingsData;
    handleSettingChange: (
        key: keyof TableRateSettingsData,
        value: any
    ) => void;
}

const RateSettings = ( {
    settings,
    handleSettingChange,
}: RateSettingsProps ) => {
    const calculationTypeOptions = [
        { value: '', label: __( 'Per Order', 'dokan' ) },
        { value: 'item', label: __( 'Calculated rates per item', 'dokan' ) },
        {
            value: 'line',
            label: __( 'Calculated rates per line item', 'dokan' ),
        },
        {
            value: 'class',
            label: __( 'Calculated rates per shipping class', 'dokan' ),
        },
    ];

    const getLabels = () => {
        switch ( settings?.calculation_type ) {
            case 'item':
                return {
                    handling: __( 'Handling Fee Per Item', 'dokan' ),
                    minimum: __( 'Minimum Cost Per Item', 'dokan' ),
                    maximum: __( 'Maximum Cost Per Item', 'dokan' ),
                };
            case 'line':
                return {
                    handling: __( 'Handling Fee Per Line Item', 'dokan' ),
                    minimum: __( 'Minimum Cost Per Line Item', 'dokan' ),
                    maximum: __( 'Maximum Cost Per Line Item', 'dokan' ),
                };
            case 'class':
                return {
                    handling: __( 'Handling Fee Per Class', 'dokan' ),
                    minimum: __( 'Minimum Cost Per Class', 'dokan' ),
                    maximum: __( 'Maximum Cost Per Class', 'dokan' ),
                };
            default:
                return {
                    handling: __( 'Handling Fee Per Order', 'dokan' ),
                    minimum: __( 'Minimum Cost Per Order', 'dokan' ),
                    maximum: __( 'Maximum Cost Per Order', 'dokan' ),
                };
        }
    };

    return (
        <>
            <div className="py-6 px-1">
                <h3 className="text-base font-medium text-gray-900 mb-1">
                    { __( 'Rates', 'dokan' ) }
                </h3>
                <p className="text-sm text-gray-500">
                    { __(
                        'This is where you define your table rates which are applied to an order.',
                        'dokan'
                    ) }
                </p>
            </div>
            <dl className="divide-y divide-gray-100">
                { /* Shipping calculation type */ }
                <div className="bg-gray-50 px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-3">
                    <dt className="text-sm flex items-center gap-2 font-medium leading-6 text-gray-900">
                        <h3 className="text-base font-medium text-gray-900">
                            { __( 'Calculation Type', 'dokan' ) }
                        </h3>
                    </dt>
                    <dd className="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <SearchableSelect
                            options={ calculationTypeOptions }
                            onChange={ ( option ) =>
                                handleSettingChange(
                                    'calculation_type',
                                    option.value
                                )
                            }
                            value={ calculationTypeOptions.find(
                                ( option ) =>
                                    option.value === settings?.calculation_type
                            ) }
                        />
                    </dd>
                </div>

                { /* Shipping order handling fee */ }
                <div className="bg-white px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-3">
                    <dt className="text-sm flex items-center gap-2 font-medium leading-6 text-gray-900">
                        <h3 className="text-base font-medium text-gray-900">
                            { getLabels()?.handling }
                        </h3>
                    </dt>
                    <dd className="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <SimpleInput
                            type="number"
                            className={ `bg-white focus:bg-white` }
                            value={ settings?.order_handling_fee }
                            onChange={ ( e ) =>
                                handleSettingChange(
                                    'order_handling_fee',
                                    e.target.value
                                )
                            }
                            placeholder="0.00"
                        />
                    </dd>
                </div>

                { /* Shipping min cost per order */ }
                <div className="bg-white px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-3">
                    <dt className="text-sm flex items-center gap-2 font-medium leading-6 text-gray-900">
                        <h3 className="text-base font-medium text-gray-900">
                            { getLabels()?.minimum }
                        </h3>
                    </dt>
                    <dd className="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <SimpleInput
                            type="number"
                            className={ `bg-white focus:bg-white` }
                            value={ settings?.min_cost }
                            onChange={ ( e ) =>
                                handleSettingChange(
                                    'min_cost',
                                    e.target.value
                                )
                            }
                            placeholder="0.00"
                        />
                    </dd>
                </div>

                { /* Shipping max cost per order */ }
                <div className="bg-gray-50 px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-3">
                    <dt className="text-sm flex items-center gap-2 font-medium leading-6 text-gray-900">
                        <h3 className="text-base font-medium text-gray-900">
                            { getLabels()?.maximum }
                        </h3>
                    </dt>
                    <dd className="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <SimpleInput
                            type="number"
                            className={ `bg-white focus:bg-white` }
                            value={ settings?.max_cost }
                            onChange={ ( e ) =>
                                handleSettingChange(
                                    'max_cost',
                                    e.target.value
                                )
                            }
                            placeholder="0.00"
                        />
                    </dd>
                </div>
            </dl>
        </>
    );
};

export default RateSettings;
