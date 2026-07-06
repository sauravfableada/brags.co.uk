import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { NotFound } from '@dokan/components';
import { useEffect, useState } from '@wordpress/element';
import { Button, DokanToaster, useToast } from '@getdokan/dokan-ui';
import {
    TableRateSettingsData,
    TableRateSettingsResponse,
} from '../definations';
import { RouterProps } from '@dokan-pro/Definitions/RouterProps';
import MethodSettings from './MethodSettings';
import RateSettings from './RateSettings';
import ClassPrioritySettings from './ClassPrioritySettings';
import TableRates from '../table-rates';
import ShippingHeader from '@dokan-pro/features/shipping/ShippingHeader';
import SettingsSkeleton from '../common/SettingsSkeleton';

const TableRateShippingSettings = ( { params }: RouterProps ) => {
    const { zoneID: zoneId, instanceID: instanceId } = params;
    const [ isLoading, setIsLoading ] = useState< boolean >( true );
    const [ isSaving, setIsSaving ] = useState< boolean >( false );
    const [ isNotFound, setIsNotFound ] = useState< boolean >( false );
    const [ settings, setSettings ] = useState< TableRateSettingsData >( {
        title: '',
        tax_status: 'none',
        prices_include_tax: 'no',
        handling_fee: '',
        max_shipping_cost: '',
        calculation_type: '',
        order_handling_fee: '',
        min_cost: '',
        max_cost: '',
        classes_priorities: {},
        default_priority: 10,
    } );

    const toast = useToast();

    useEffect( () => {
        apiFetch< TableRateSettingsResponse >( {
            path: `/dokan/v1/shipping/table-rate/settings/zone/${ zoneId }/instance/${ instanceId }`,
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
                    title: __( 'Error loading table rate settings', 'dokan' ),
                    subtitle: error.message,
                } );
            } )
            .finally( () => setIsLoading( false ) );
    }, [] );

    const handleSettingChange = (
        key: keyof TableRateSettingsData,
        value: any
    ) => {
        setSettings( ( prev ) => ( {
            ...prev,
            [ key ]: value,
        } ) );
    };

    const onSaveSettings = () => {
        setIsSaving( true );

        apiFetch< TableRateSettingsResponse >( {
            path: `/dokan/v1/shipping/table-rate/settings/zone/${ zoneId }/instance/${ instanceId }`,
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
        return <NotFound navigateButton={ <></> } />;
    }

    if ( isLoading ) {
        return <SettingsSkeleton />;
    }

    return (
        <div className="dokan-table-rate-shipping-settings-container">
            <ShippingHeader />
            <div className="table-rate-settings">
                <DokanToaster />
                <div className="mt-6 border-t border-gray-100">
                    { /* Method settings */ }
                    <MethodSettings
                        settings={ settings }
                        handleSettingChange={ handleSettingChange }
                    />

                    { /* Rate settings */ }
                    <RateSettings
                        settings={ settings }
                        handleSettingChange={ handleSettingChange }
                    />

                    { /* Table rate settings */ }
                    <TableRates zoneId={ zoneId } instanceId={ instanceId } />

                    { /* Class priority settings */ }
                    { ! settings?.calculation_type && (
                        <ClassPrioritySettings
                            settings={ settings }
                            handleSettingChange={ handleSettingChange }
                        />
                    ) }

                    { /* Save Button */ }
                    <div className="flex justify-end mt-8">
                        <Button
                            loading={ isSaving }
                            className="dokan-btn"
                            onClick={ onSaveSettings }
                            label={ __( 'Save Changes', 'dokan' ) }
                        />
                    </div>
                </div>
            </div>
        </div>
    );
};

export default TableRateShippingSettings;
