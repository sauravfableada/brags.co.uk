import { __ } from '@wordpress/i18n';
import { SearchableSelect, SimpleInput } from '@getdokan/dokan-ui';
import { DistanceRateSettingsData } from '../definations';

interface DistanceMethodSettingsProps {
    settings: DistanceRateSettingsData;
    handleSettingChange: (
        key: keyof DistanceRateSettingsData,
        value: any
    ) => void;
}

const DistanceMethodSettings = ( {
    settings,
    handleSettingChange,
}: DistanceMethodSettingsProps ) => {
    const taxStatusOptions = [
        { value: 'taxable', label: __( 'Taxable', 'dokan' ) },
        { value: 'none', label: __( 'None', 'dokan' ) },
    ];

    const transportationModeOptions = [
        { value: 'driving', label: __( 'Driving', 'dokan' ) },
        { value: 'walking', label: __( 'Walking', 'dokan' ) },
        { value: 'bicycle', label: __( 'Bicycling', 'dokan' ) },
    ];

    const avoidOptions = [
        { value: 'none', label: __( 'None', 'dokan' ) },
        { value: 'tolls', label: __( 'Tolls', 'dokan' ) },
        { value: 'highways', label: __( 'Highways', 'dokan' ) },
        { value: 'ferries', label: __( 'Ferries', 'dokan' ) },
    ];

    const distanceUnitOptions = [
        { value: 'metric', label: __( 'Metric', 'dokan' ) },
        { value: 'imperial', label: __( 'Imperial', 'dokan' ) },
    ];

    const showOptions = [
        { value: 'yes', label: __( 'Yes', 'dokan' ) },
        { value: 'no', label: __( 'No', 'dokan' ) },
    ];

    return (
        <dl className="divide-y divide-gray-100">
            { /* Method Title */ }
            <div className="bg-gray-50 px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-3">
                <dt className="text-sm flex items-center gap-2 font-medium leading-6 text-gray-900">
                    <h3 className="text-base font-medium text-gray-900">
                        { __( 'Method Title', 'dokan' ) }
                    </h3>
                </dt>
                <dd className="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                    <SimpleInput
                        value={ settings?.title }
                        className={ `bg-white focus:bg-white` }
                        onChange={ ( e ) =>
                            handleSettingChange( 'title', e.target.value )
                        }
                        placeholder={ __( 'Enter method title', 'dokan' ) }
                        helpText={ __(
                            'This controls the title which the user sees during checkout.',
                            'dokan'
                        ) }
                    />
                </dd>
            </div>

            { /* Tax Status */ }
            <div className="bg-white px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-3">
                <dt className="text-sm flex items-center gap-2 font-medium leading-6 text-gray-900">
                    <h3 className="text-base font-medium text-gray-900">
                        { __( 'Tax Status', 'dokan' ) }
                    </h3>
                </dt>
                <dd className="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                    <SearchableSelect
                        options={ taxStatusOptions }
                        onChange={ ( option ) =>
                            handleSettingChange( 'tax_status', option.value )
                        }
                        value={ taxStatusOptions.find(
                            ( option ) => option.value === settings?.tax_status
                        ) }
                    />
                </dd>
            </div>

            { /* Transportation Mode */ }
            <div className="bg-gray-50 px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-3">
                <dt className="text-sm flex items-center gap-2 font-medium leading-6 text-gray-900">
                    <h3 className="text-base font-medium text-gray-900">
                        { __( 'Transportation Mode', 'dokan' ) }
                    </h3>
                </dt>
                <dd className="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                    <SearchableSelect
                        options={ transportationModeOptions }
                        onChange={ ( option ) =>
                            handleSettingChange(
                                'distance_rate_mode',
                                option.value
                            )
                        }
                        value={ transportationModeOptions.find(
                            ( option ) =>
                                option.value === settings?.distance_rate_mode
                        ) }
                    />
                </dd>
            </div>

            { /* Avoid */ }
            <div className="bg-white px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-3">
                <dt className="text-sm flex items-center gap-2 font-medium leading-6 text-gray-900">
                    <h3 className="text-base font-medium text-gray-900">
                        { __( 'Avoid', 'dokan' ) }
                    </h3>
                </dt>
                <dd className="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                    <SearchableSelect
                        options={ avoidOptions }
                        onChange={ ( option ) =>
                            handleSettingChange(
                                'distance_rate_avoid',
                                option.value
                            )
                        }
                        value={ avoidOptions.find(
                            ( option ) =>
                                option.value === settings?.distance_rate_avoid
                        ) }
                    />
                </dd>
            </div>

            { /* Distance Unit */ }
            <div className="bg-gray-50 px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-3">
                <dt className="text-sm flex items-center gap-2 font-medium leading-6 text-gray-900">
                    <h3 className="text-base font-medium text-gray-900">
                        { __( 'Distance Unit', 'dokan' ) }
                    </h3>
                </dt>
                <dd className="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                    <SearchableSelect
                        options={ distanceUnitOptions }
                        onChange={ ( option ) =>
                            handleSettingChange(
                                'distance_rate_unit',
                                option.value
                            )
                        }
                        value={ distanceUnitOptions.find(
                            ( option ) =>
                                option.value === settings?.distance_rate_unit
                        ) }
                    />
                </dd>
            </div>

            { /* Show Distance */ }
            <div className="bg-white px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-3">
                <dt className="text-sm flex items-center gap-2 font-medium leading-6 text-gray-900">
                    <h3 className="text-base font-medium text-gray-900">
                        { __( 'Show Distance', 'dokan' ) }
                    </h3>
                </dt>
                <dd className="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                    <SearchableSelect
                        options={ showOptions }
                        onChange={ ( option ) =>
                            handleSettingChange(
                                'distance_rate_show_distance',
                                option.value
                            )
                        }
                        value={ showOptions.find(
                            ( option ) =>
                                option?.value ===
                                settings?.distance_rate_show_distance
                        ) }
                        helpText={ __(
                            'Show the distance next to the shipping cost for the customer.',
                            'dokan'
                        ) }
                    />
                </dd>
            </div>

            { /* Show Duration */ }
            <div className="bg-gray-50 px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-3">
                <dt className="text-sm flex items-center gap-2 font-medium leading-6 text-gray-900">
                    <h3 className="text-base font-medium text-gray-900">
                        { __( 'Show Duration', 'dokan' ) }
                    </h3>
                </dt>
                <dd className="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                    <SearchableSelect
                        options={ showOptions }
                        onChange={ ( option ) =>
                            handleSettingChange(
                                'distance_rate_show_duration',
                                option.value
                            )
                        }
                        value={ showOptions.find(
                            ( option ) =>
                                option.value ===
                                settings?.distance_rate_show_duration
                        ) }
                        helpText={ __(
                            'Show the duration next to the shipping cost for the customer.',
                            'dokan'
                        ) }
                    />
                </dd>
            </div>
        </dl>
    );
};

export default DistanceMethodSettings;
