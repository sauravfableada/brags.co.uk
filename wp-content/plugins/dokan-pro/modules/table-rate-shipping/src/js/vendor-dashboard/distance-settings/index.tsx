import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { NotFound } from '@dokan/components';
import { useEffect, useState } from '@wordpress/element';
import { Button, DokanToaster, useToast } from '@getdokan/dokan-ui';
import {
    DistanceRateSettingsData,
    DistanceRateSettingsResponse,
} from '../definations';
import DistanceMethodSettings from './DistanceMethodSettings';
import DistanceShippingAddress from './DistanceShippingAddress';
import DistanceRates from '../distance-rates';
import ShippingHeader from '@dokan-pro/features/shipping/ShippingHeader';
import SettingsSkeleton from '../common/SettingsSkeleton';

const DistanceRateShippingSettings = ( { params } ) => {
    const { zoneID: zoneId, instanceID: instanceId } = params;
    const [ isLoading, setIsLoading ] = useState< boolean >( true );
    const [ isSaving, setIsSaving ] = useState< boolean >( false );
    const [ isNotFound, setIsNotFound ] = useState< boolean >( false );
    const [ settings, setSettings ] = useState< DistanceRateSettingsData >( {
        title: '',
        tax_status: 'none',
        distance_rate_mode: 'driving',
        distance_rate_avoid: 'none',
        distance_rate_unit: 'metric',
        distance_rate_show_distance: 'yes',
        distance_rate_show_duration: 'yes',
        distance_rate_address_1: '',
        distance_rate_address_2: '',
        distance_rate_city: '',
        distance_rate_postal_code: '',
        distance_rate_state_province: '',
        distance_rate_country: '',
    } );

    const toast = useToast();

    useEffect( () => {
        apiFetch< DistanceRateSettingsResponse >( {
            path: `/dokan/v1/shipping/distance-rate/settings/zone/${ zoneId }/instance/${ instanceId }`,
        } )
            .then( ( response ) => {
                if ( response ) {
                    setSettings( { ...settings, ...response } );
                }
            } )
            .catch( ( error ) => {
                if ( error?.data?.status === 404 ) {
                    setIsNotFound( true );
                }
                toast( {
                    type: 'error',
                    title: __(
                        'Error loading distance rate settings',
                        'dokan'
                    ),
                    subtitle: error.message,
                } );
            } )
            .finally( () => setIsLoading( false ) );
    }, [] );

    const handleSettingChange = (
        key: keyof DistanceRateSettingsData,
        value: any
    ) => {
        setSettings( ( prev ) => ( {
            ...prev,
            [ key ]: value,
        } ) );
    };

    const onSaveSettings = () => {
        setIsSaving( true );

        apiFetch< DistanceRateSettingsResponse >( {
            path: `/dokan/v1/shipping/distance-rate/settings/zone/${ zoneId }/instance/${ instanceId }`,
            method: 'PUT',
            data: settings,
        } )
            .then( ( response ) => {
                toast( {
                    type: 'success',
                    title: __( 'Settings updated successfully', 'dokan' ),
                } );
            } )
            .catch( ( error ) => {
                toast( {
                    type: 'error',
                    title: __( 'Error updating settings', 'dokan' ),
                    subtitle: error.message,
                } );
            } )
            .finally( () => setIsSaving( false ) );
    };

    if ( isNotFound ) {
        return <NotFound />;
    }

    if ( isLoading ) {
        return (
            <SettingsSkeleton sections={ [ { fields: 4 }, { fields: 6 } ] } />
        );
    }

    return (
        <div className="dokan-distance-rate-shipping-settings-container">
            <ShippingHeader />

            { dokanTableRateShippingHelper?.map_api_key ? (
                <div className="distance-rate-settings">
                    <DokanToaster />
                    <div className="mt-6 border-t border-gray-100">
                        { /* Method settings */ }
                        <DistanceMethodSettings
                            settings={ settings }
                            handleSettingChange={ handleSettingChange }
                        />

                        { /* Shipping Address settings */ }
                        <DistanceShippingAddress
                            settings={ settings }
                            handleSettingChange={ handleSettingChange }
                        />

                        { /* Distance rate settings */ }
                        <DistanceRates
                            zoneId={ zoneId }
                            instanceId={ instanceId }
                        />

                        { /* Save Button */ }
                        <div className="flex justify-end mt-8">
                            <Button
                                color="primary"
                                loading={ isSaving }
                                className="dokan-btn"
                                onClick={ onSaveSettings }
                                label={ __( 'Save Changes', 'dokan' ) }
                            />
                        </div>
                    </div>
                </div>
            ) : (
                <div
                    className="p-4 mt-4 text-base font-medium rounded-lg dokan-alert-danger"
                    role="alert"
                >
                    { __(
                        'Distance rate shipping requires Google map API key.',
                        'dokan'
                    ) }
                </div>
            ) }
        </div>
    );
};

export default DistanceRateShippingSettings;
