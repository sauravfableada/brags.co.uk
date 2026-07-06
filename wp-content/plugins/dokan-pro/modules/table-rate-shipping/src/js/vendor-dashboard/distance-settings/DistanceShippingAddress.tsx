import { __ } from '@wordpress/i18n';
import { SimpleInput, SearchableSelect } from '@getdokan/dokan-ui';
import { DistanceRateSettingsData } from '../definations';
import { addQueryArgs } from '@wordpress/url';

interface DistanceShippingAddressProps {
    settings: DistanceRateSettingsData;
    handleSettingChange: (
        key: keyof DistanceRateSettingsData,
        value: any
    ) => void;
}

const DistanceShippingAddress = ( {
    settings,
    handleSettingChange,
}: DistanceShippingAddressProps ) => {
    const fullAddress = [
        settings?.distance_rate_address_1,
        settings?.distance_rate_address_2,
        settings?.distance_rate_city,
        settings?.distance_rate_postal_code,
        settings?.distance_rate_state_province,
        settings?.distance_rate_country,
    ]
        .filter( Boolean )
        .join( ', ' );

    const countries = wc?.wcSettings?.COUNTRIES || {},
        countryListOptions = Object.keys( countries ).map( ( countryCode ) => {
            return {
                label: countries[ countryCode ],
                value: countryCode,
            };
        } );

    // @ts-ignore
    return (
        <>
            <div className="py-6 px-1">
                <h3 className="text-base font-medium text-gray-900 mb-1">
                    { __( 'Shipping Address', 'dokan' ) }
                </h3>
                <p className="text-sm text-gray-500">
                    { __(
                        'Please enter the address that you are shipping from below to work out the distance of the customer from the shipping location.',
                        'dokan'
                    ) }
                </p>
            </div>

            <dl className="divide-y divide-gray-100">
                { /* Address 1 */ }
                <div className="bg-gray-50 px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-3">
                    <dt className="text-sm flex items-center gap-2 font-medium leading-6 text-gray-900">
                        <h3 className="text-base font-medium text-gray-900">
                            { __( 'Address 1', 'dokan' ) }
                        </h3>
                    </dt>
                    <dd className="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <SimpleInput
                            value={ settings?.distance_rate_address_1 }
                            className={ `bg-white focus:bg-white` }
                            onChange={ ( e ) =>
                                handleSettingChange(
                                    'distance_rate_address_1',
                                    e.target.value
                                )
                            }
                            helpText={ __(
                                'First address line of where you are shipping from.',
                                'dokan'
                            ) }
                        />
                    </dd>
                </div>

                { /* Address 2 */ }
                <div className="bg-white px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-3">
                    <dt className="text-sm flex items-center gap-2 font-medium leading-6 text-gray-900">
                        <h3 className="text-base font-medium text-gray-900">
                            { __( 'Address 2', 'dokan' ) }
                        </h3>
                    </dt>
                    <dd className="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <SimpleInput
                            value={ settings?.distance_rate_address_2 }
                            className={ `bg-white focus:bg-white` }
                            onChange={ ( e ) =>
                                handleSettingChange(
                                    'distance_rate_address_2',
                                    e.target.value
                                )
                            }
                            helpText={ __(
                                'Second address line of where you are shipping from.',
                                'dokan'
                            ) }
                        />
                    </dd>
                </div>

                { /* City */ }
                <div className="bg-gray-50 px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-3">
                    <dt className="text-sm flex items-center gap-2 font-medium leading-6 text-gray-900">
                        <h3 className="text-base font-medium text-gray-900">
                            { __( 'City', 'dokan' ) }
                        </h3>
                    </dt>
                    <dd className="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <SimpleInput
                            value={ settings?.distance_rate_city }
                            className={ `bg-white focus:bg-white` }
                            onChange={ ( e ) =>
                                handleSettingChange(
                                    'distance_rate_city',
                                    e.target.value
                                )
                            }
                            helpText={ __(
                                'City of where you are shipping from.',
                                'dokan'
                            ) }
                        />
                    </dd>
                </div>

                { /* Zip/Postal Code */ }
                <div className="bg-white px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-3">
                    <dt className="text-sm flex items-center gap-2 font-medium leading-6 text-gray-900">
                        <h3 className="text-base font-medium text-gray-900">
                            { __( 'Zip/Postal Code', 'dokan' ) }
                        </h3>
                    </dt>
                    <dd className="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <SimpleInput
                            value={ settings?.distance_rate_postal_code }
                            className={ `bg-white focus:bg-white` }
                            onChange={ ( e ) =>
                                handleSettingChange(
                                    'distance_rate_postal_code',
                                    e.target.value
                                )
                            }
                            helpText={ __(
                                'Zip or Postal Code of where you are shipping from.',
                                'dokan'
                            ) }
                        />
                    </dd>
                </div>

                { /* State/Province */ }
                <div className="bg-gray-50 px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-3">
                    <dt className="text-sm flex items-center gap-2 font-medium leading-6 text-gray-900">
                        <h3 className="text-base font-medium text-gray-900">
                            { __( 'State/Province', 'dokan' ) }
                        </h3>
                    </dt>
                    <dd className="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <SimpleInput
                            value={ settings?.distance_rate_state_province }
                            className={ `bg-white focus:bg-white` }
                            onChange={ ( e ) =>
                                handleSettingChange(
                                    'distance_rate_state_province',
                                    e.target.value
                                )
                            }
                            helpText={ __(
                                'State/Province of where you are shipping from.',
                                'dokan'
                            ) }
                        />
                    </dd>
                </div>

                { /* Country */ }
                <div className="bg-white px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-3">
                    <dt className="text-sm flex items-center gap-2 font-medium leading-6 text-gray-900">
                        <h3 className="text-base font-medium text-gray-900">
                            { __( 'Country', 'dokan' ) }
                        </h3>
                    </dt>
                    <dd className="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <SearchableSelect
                            value={ countryListOptions.find( ( country ) => {
                                return (
                                    country?.label ===
                                    settings?.distance_rate_country
                                );
                            } ) }
                            helpText={ __(
                                'Country of where you are shipping from.',
                                'dokan'
                            ) }
                            placeholder={ __( 'Select a country', 'dokan' ) }
                            options={ countryListOptions || [] }
                            onChange={ ( option ) =>
                                handleSettingChange(
                                    'distance_rate_country',
                                    option?.label
                                )
                            }
                        />
                    </dd>
                </div>

                { /* Map Display */ }
                { fullAddress && (
                    <div className="bg-gray-50 px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-3">
                        <dt className="text-sm flex items-center gap-2 font-medium leading-6 text-gray-900">
                            <h3 className="text-base text-gray-900">
                                { __( 'Map', 'dokan' ) }
                            </h3>
                        </dt>
                        <dd className="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                            <p className="text-base">
                                <strong>{ __( 'Address: ', 'dokan' ) }</strong>
                                <span className={ `text-gray-500` }>
                                    { fullAddress }
                                </span>
                            </p>
                            <div className="mt-4 bg-gray-200 rounded p-1 text-center">
                                { /* eslint-disable-next-line jsx-a11y/iframe-has-title */ }
                                <iframe
                                    className="rounded w-full min-h-80"
                                    src={ addQueryArgs(
                                        'https://www.google.com/maps/embed/v1/place',
                                        {
                                            q: fullAddress,
                                            // @ts-ignore
                                            key: dokanTableRateShippingHelper?.map_api_key,
                                        }
                                    ) }
                                ></iframe>
                            </div>
                        </dd>
                    </div>
                ) }
            </dl>
        </>
    );
};

export default DistanceShippingAddress;
