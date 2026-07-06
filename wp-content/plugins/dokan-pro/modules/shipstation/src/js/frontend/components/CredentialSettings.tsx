import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { DokanButton, DokanAlert, DokanModal, DokanTooltip as Tooltip } from '@dokan/components';
import { Card, useToast } from '@getdokan/dokan-ui';
import CopyToClipboard from '../../../../../../src/components/CopyToClipboard';
import { ApiCredentials } from '../definition/CredentialTypes';
import CredentialSettingsSkeleton from './skeleton/CredentialSettingsSkeleton';

interface CredentialSettingsProps {
    vendorId?: number;
}

const CredentialSettings = ( { vendorId }: CredentialSettingsProps ) => {
    const toast = useToast();
    const [ apiCredential, setApiCredential ] = useState( {
        keyId: 0,
        authKey: '',
        consumerKey: '',
        consumerSecret: '',
    } );
    const [ loading, setLoading ] = useState( true );
    const [ isAlertOpen, setIsAlertOpen ] = useState( false );
    const [ isConfirmationModalOpen, setIsConfirmationModalOpen ] =
        useState( false );

    useEffect( () => {
        const fetchApiCredential = async () => {
            setLoading( true );

            try {
                const response: ApiCredentials = await apiFetch( {
                    path: `dokan/v1/shipstation/credentials/${ vendorId }`,
                    method: 'GET',
                } );

                if ( response.key_id ) {
                    setApiCredential( {
                        keyId: response?.key_id,
                        authKey: response?.dokan_auth_key,
                        consumerKey: response?.consumer_key,
                        consumerSecret: '',
                    } );
                }
            } catch ( error ) {
                if ( 'dokan_pro_rest_no_resource' === error.code ) {
                    return;
                }

                toast( {
                    type: 'error',
                    title:
                        __( 'Error fetching API credentials: ', 'dokan' ) +
                        error?.message,
                } );
            } finally {
                setLoading( false );
            }
        };

        if ( vendorId ) {
            fetchApiCredential();
        }
    }, [ vendorId ] );

    // Generate credential handler.
    const handleGenerateCredential = async () => {
        setLoading( true );

        try {
            const response: ApiCredentials = await apiFetch( {
                path: '/dokan/v1/shipstation/credentials/create',
                method: 'POST',
                data: { vendor_id: vendorId },
            } );

            if ( response.key_id ) {
                setApiCredential( {
                    keyId: response.key_id,
                    authKey: response.dokan_auth_key,
                    consumerKey: response.consumer_key,
                    consumerSecret: response.consumer_secret,
                } );

                setIsAlertOpen( true );
            }
        } catch ( error ) {
            toast( {
                type: 'error',
                title:
                    __( 'Error generating API credentials: ', 'dokan' ) +
                    error?.message,
            } );
        } finally {
            setLoading( false );
        }
    };

    // Revoke credential handler.
    const handleRevokeCredential = async () => {
        setLoading( true );

        try {
            const response: ApiCredentials = await apiFetch( {
                path: `/dokan/v1/shipstation/credentials/${ vendorId }`,
                method: 'DELETE',
            } );

            if ( response.key_id ) {
                setApiCredential( {
                    keyId: 0,
                    authKey: '',
                    consumerKey: '',
                    consumerSecret: '',
                } );

                toast( {
                    type: 'success',
                    title: __(
                        'API credentials revoked successfully.',
                        'dokan'
                    ),
                } );
            } else {
                toast( {
                    type: 'error',
                    title: __( 'Error revoking API credentials.', 'dokan' ),
                } );
            }
        } catch ( error ) {
            toast( {
                type: 'error',
                title:
                    __( 'Error revoking API credentials: ', 'dokan' ) +
                    error?.message,
            } );
        } finally {
            setLoading( false );
            setIsConfirmationModalOpen( false );
        }
    };

    return (
        <>
            <Card className="mb-5">
                <Card.Header>
                    <Card.Title>
                        { __( 'Credential Details', 'dokan' ) }
                    </Card.Title>
                </Card.Header>
                <Card.Body>
                    { loading ? (
                        <CredentialSettingsSkeleton />
                    ) : ! apiCredential.keyId ? (
                        <div className="flex flex-wrap justify-between items-center">
                            <p className="mb-4 lg:mb-0">
                                { __(
                                    'Generate credential to connect your store with ShipStation.',
                                    'dokan'
                                ) }
                            </p>
                            <DokanButton
                                variant="primary"
                                label={ __( 'Generate Credentials', 'dokan' ) }
                                onClick={ handleGenerateCredential }
                            />
                        </div>
                    ) : (
                        <div className="flex flex-wrap justify-between items-center -mb-3">
                            <p className="mb-4 lg:mb-6">
                                { __(
                                    'Use these credentials to connect your ShipStation account.',
                                    'dokan'
                                ) }
                            </p>
                            <DokanButton
                                variant="secondary"
                                label={ __( 'Revoke Credentials', 'dokan' ) }
                                onClick={ () =>
                                    setIsConfirmationModalOpen( true )
                                }
                            />

                            <div className="basis-full flex justify-start items-baseline mb-5 flex-wrap flex-col lg:flex-nowrap lg:flex-row">
                                <label
                                    className="basis-3/12 mb-2 lg:mb-0"
                                    htmlFor="dokan-shipstation-auth-key"
                                >
                                    { __( 'Authentication Key', 'dokan' ) }
                                    <Tooltip
                                        content={ __(
                                            'This is the Auth Key you set in ShipStation and allows ShipStation to communicate with your store.',
                                            'dokan'
                                        ) }
                                        direction={ 'bottom' }
                                    >
                                        <span className="ml-2">
                                            <i className="fas fa-question-circle text-gray-400"></i>
                                        </span>
                                    </Tooltip>
                                </label>
                                <div className="flex basis-7/12">
                                    <code className="dokan-shipstation-code text-xs sm:text-sm inline-block min-w-80 sm:min-w-96 px-2.5 py-1.5 rounded">
                                        { apiCredential.authKey }
                                    </code>

                                    <CopyToClipboard
                                        content={ apiCredential.authKey }
                                        className="px-1 sm:px-2"
                                    />
                                </div>
                            </div>

                            <div className="basis-full flex justify-start items-baseline mb-5 flex-wrap flex-col lg:flex-nowrap lg:flex-row">
                                <label
                                    className="basis-3/12 mb-2 lg:mb-0"
                                    htmlFor="dokan-shipstation-consumer-key"
                                >
                                    { __( 'Consumer Key', 'dokan' ) }
                                    <Tooltip
                                        content={ __(
                                            'An unique identifier required for establishing a secure connection between your store and ShipStation.',
                                            'dokan'
                                        ) }
                                        direction={ 'bottom' }
                                    >
                                        <span className="ml-2">
                                            <i className="fas fa-question-circle text-gray-400"></i>
                                        </span>
                                    </Tooltip>
                                </label>
                                <div className="flex basis-7/12">
                                    <code className="dokan-shipstation-code text-xs sm:text-sm inline-block min-w-80 sm:min-w-96 px-2.5 py-1.5 rounded">
                                        { apiCredential.consumerKey }
                                    </code>

                                    <CopyToClipboard
                                        content={ apiCredential.consumerKey }
                                        className="px-1 sm:px-2"
                                    />
                                </div>
                            </div>

                            { apiCredential.consumerSecret && (
                                <div className="basis-full flex justify-start items-baseline mb-5 flex-wrap flex-col lg:flex-nowrap lg:flex-row">
                                    <label
                                        className="basis-3/12 mb-2 lg:mb-0"
                                        htmlFor="dokan-shipstation-secret-key"
                                    >
                                        { __( 'Consumer Secret', 'dokan' ) }
                                        <Tooltip
                                            content={ __(
                                                'Functions as a secure password for API access. It is imperative that this key is kept confidential.',
                                                'dokan'
                                            ) }
                                            direction={ 'bottom' }
                                        >
                                            <span className="ml-2">
                                                <i className="fas fa-question-circle text-gray-400"></i>
                                            </span>
                                        </Tooltip>
                                    </label>
                                    <div className="flex basis-7/12 flex-col flex-wrap">
                                        <div className="flex">
                                            <code className="dokan-shipstation-code text-xs sm:text-sm inline-block min-w-80 sm:min-w-96 px-2.5 py-1.5 rounded mb-2">
                                                { apiCredential.consumerSecret }
                                            </code>

                                            <CopyToClipboard
                                                content={
                                                    apiCredential.consumerSecret
                                                }
                                                className="px-1 sm:px-2"
                                            />
                                        </div>

                                        <DokanAlert
                                            variant="danger"
                                            label={ __(
                                                'Note: Once this page is refreshed, the consumer secret will no longer be available.',
                                                'dokan'
                                            ) }
                                            className="basis-full max-w-80 sm:max-w-96"
                                        ></DokanAlert>
                                    </div>
                                </div>
                            ) }
                        </div>
                    ) }
                </Card.Body>
            </Card>

            <DokanModal
                namespace="dokan-shipstation-credentials-generation-alert"
                isOpen={ isAlertOpen }
                dialogTitle={ __( 'Attention!', 'dokan' ) }
                confirmationTitle={ __(
                    'Make sure to copy your new keys.',
                    'dokan'
                ) }
                confirmationDescription={ __(
                    'API Keys generated successfully. Make sure to copy your new keys now as the secret key will be hidden once you leave this page.',
                    'dokan'
                ) }
                confirmButtonText={ __( 'Confirm', 'dokan' ) }
                onConfirm={ () => setIsAlertOpen( false ) }
                onClose={ () => setIsAlertOpen( false ) }
                hideCancelButton={ true }
            />

            <DokanModal
                namespace="dokan-shipstation-credentials-revoke"
                isOpen={ isConfirmationModalOpen }
                dialogTitle={ __( 'Revoke Credentials', 'dokan' ) }
                confirmationTitle={ __(
                    'Are you sure you want to proceed?',
                    'dokan'
                ) }
                confirmationDescription={ __(
                    "Revoking will immediately disconnect your store from ShipStation, potentially affecting ongoing shipments. You won't be able to manage orders until new credentials are set up.",
                    'dokan'
                ) }
                confirmButtonText={ __( 'Yes, Revoke', 'dokan' ) }
                cancelButtonText={ __( 'Cancel', 'dokan' ) }
                onConfirm={ () => handleRevokeCredential() }
                onClose={ () => setIsConfirmationModalOpen( false ) }
            />
        </>
    );
};

export default CredentialSettings;
