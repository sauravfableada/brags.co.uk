import { Card } from '@getdokan/dokan-ui';
import { Select } from '@dokan/components';
import { __ } from '@wordpress/i18n';
import { Slot } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';

function VendorEdit() {
    const [ subscriptions, setSubscriptions ] = useState( [] );
    const [ currentSubscription, setCurrentSubscription ] = useState( null );
    const { setCreateOrEditVendor } = useDispatch( 'dokan/vendors' );

    const vendor = useSelect( ( select ) => {
        // @ts-ignore
        return select( 'dokan/vendors' ).getCreateOrEditVendor();
    }, [] );

    const handleOnChange = ( newValue ) => {
        setCreateOrEditVendor( {
            ...vendor,
            current_subscription: {
                label: newValue.label,
                name: newValue.value,
            },
            assigned_subscription: newValue.value,
            // @ts-ignore
            subscription_nonce: dokanAdminDashboard?.nonce,
        } );
    };

    useEffect( () => {
        if ( ! vendor?.current_subscription ) {
            return;
        }

        setCurrentSubscription( {
            label: vendor?.current_subscription?.label ?? '',
            value: vendor?.current_subscription?.name ?? '',
        } );
    }, [ vendor ] );

    useEffect( () => {
        const currentSubscriptoins: Array< { name: string; label: string } > =
            // @ts-ignore
            window?.dokanAdminDashboard?.non_recurring_subscription_packs;
        if (
            ! currentSubscriptoins ||
            ! Array.isArray( currentSubscriptoins )
        ) {
            return;
        }

        setSubscriptions(
            currentSubscriptoins.map( ( item ) => {
                return {
                    value: item.name,
                    label: item.label,
                };
            } )
        );
    }, [] );
    return (
        <>
            <div className="mt-6">
                <Card className="bg-white">
                    <div className="border-b p-6">
                        <h2 className="text-lg font-bold">
                            { __( 'Vendor Subscription', 'dokan' ) }
                        </h2>
                    </div>
                    <div className="p-6 flex flex-col gap-3">
                        <Select
                            options={ subscriptions }
                            label={ __( 'Assign Subscription Pack', 'dokan' ) }
                            helpText={ __(
                                'You can only assign non-recurring packs.',
                                'dokan'
                            ) }
                            value={ currentSubscription }
                            onChange={ handleOnChange }
                        />
                    </div>
                </Card>
            </div>

            <Slot name={ `dokan-edit-vendor-after-send-email` } />
        </>
    );
}

export default VendorEdit;
