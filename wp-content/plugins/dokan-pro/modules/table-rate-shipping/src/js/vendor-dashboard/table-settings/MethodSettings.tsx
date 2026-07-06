import { __ } from '@wordpress/i18n';
import { SearchableSelect, SimpleInput } from '@getdokan/dokan-ui';
import { TableRateSettingsData } from '../definations';

interface MethodSettingsProps {
    settings: TableRateSettingsData;
    handleSettingChange: (
        key: keyof TableRateSettingsData,
        value: any
    ) => void;
}

const MethodSettings = ( {
    settings,
    handleSettingChange,
}: MethodSettingsProps ) => {
    const taxStatusOptions = [
        { value: 'taxable', label: __( 'Taxable', 'dokan' ) },
        { value: 'none', label: __( 'None', 'dokan' ) },
    ];

    const taxRuleOptions = [
        {
            value: 'no',
            label: __(
                'No, I will enter costs below exclusive of tax',
                'dokan'
            ),
        },
        {
            value: 'yes',
            label: __(
                'Yes, I will enter costs below inclusive of tax',
                'dokan'
            ),
        },
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

            { /* Tax Including Rule */ }
            <div className="bg-white px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-3">
                <dt className="text-sm flex items-center gap-2 font-medium leading-6 text-gray-900">
                    <h3 className="text-base font-medium text-gray-900">
                        { __( 'Tax included in shipping costs', 'dokan' ) }
                    </h3>
                </dt>
                <dd className="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                    <SearchableSelect
                        options={ taxRuleOptions }
                        onChange={ ( option ) =>
                            handleSettingChange(
                                'prices_include_tax',
                                option.value
                            )
                        }
                        value={ taxRuleOptions.find(
                            ( option ) =>
                                option.value === settings?.prices_include_tax
                        ) }
                    />
                </dd>
            </div>

            { /* Shipping Handling Cost */ }
            <div className="bg-gray-50 px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-3">
                <dt className="text-sm flex items-center gap-2 font-medium leading-6 text-gray-900">
                    <h3 className="text-base font-medium text-gray-900">
                        { __( 'Handling Fee', 'dokan' ) }
                    </h3>
                </dt>
                <dd className="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                    <SimpleInput
                        type="number"
                        className={ `bg-white focus:bg-white` }
                        value={ settings?.handling_fee }
                        onChange={ ( e ) =>
                            handleSettingChange(
                                'handling_fee',
                                e.target.value
                            )
                        }
                        placeholder="0.00"
                    />
                </dd>
            </div>

            { /* Max Shipping Cost */ }
            <div className="bg-gray-50 px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-3">
                <dt className="text-sm flex items-center gap-2 font-medium leading-6 text-gray-900">
                    <h3 className="text-base font-medium text-gray-900">
                        { __( 'Maximum Shipping Cost', 'dokan' ) }
                    </h3>
                </dt>
                <dd className="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                    <SimpleInput
                        type="number"
                        className={ `bg-white focus:bg-white` }
                        value={ settings.max_shipping_cost }
                        onChange={ ( e ) =>
                            handleSettingChange(
                                'max_shipping_cost',
                                e.target.value
                            )
                        }
                        placeholder="0.00"
                    />
                </dd>
            </div>
        </dl>
    );
};

export default MethodSettings;
