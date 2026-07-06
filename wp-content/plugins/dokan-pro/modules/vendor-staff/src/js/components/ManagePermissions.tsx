import { useState, useEffect } from '@wordpress/element';
import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Fill, Spinner } from '@wordpress/components';
import { DokanToaster, SimpleCheckbox, useToast } from '@getdokan/dokan-ui';

import {
    DokanButton,
    DokanModal,
    NotFound,
    Forbidden,
    // @ts-ignore
    // eslint-disable-next-line import/no-unresolved
} from '@dokan/components';
import apiFetch from '@wordpress/api-fetch';
import { registerPlugin } from '@wordpress/plugins';
import permissionsStore from '../store';
import { CapabilityCategory, CapabilityItem } from '../types';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { usePermission } from '@dokan/hooks';

const ResetButton = () => {
    const hashParts = window.location.hash.split( '/' );
    const id = hashParts[ hashParts.length - 1 ];
    const [ isOpen, setIsOpen ] = useState( false );
    const allCapabilities = useSelect(
        ( select ) => select( permissionsStore ).getAllCapabilities(),
        []
    );
    const notFound = useSelect(
        ( select ) => select( permissionsStore ).getErrorCode(),
        []
    );

    const toast = useToast();
    const applyDefaultPermissions = async () => {
        if ( ! allCapabilities?.all || ! allCapabilities?.default ) {
            return;
        }

        const allCapabilitiesList: string[] = [];
        Object.values( allCapabilities.all ).forEach(
            ( category: CapabilityCategory ) => {
                Object.keys( category ).forEach( ( capability: string ) => {
                    allCapabilitiesList.push( capability );
                } );
            }
        );

        const capabilities: CapabilityItem[] = allCapabilitiesList.map(
            ( capability: string ): CapabilityItem => {
                return {
                    capability,
                    access: allCapabilities.default.includes( capability ),
                };
            }
        );

        try {
            await apiFetch< Record< string, boolean > >( {
                path: `dokan/v1/vendor-staff/${ id }/capabilities`,
                method: 'POST',
                data: {
                    capabilities,
                },
            } );
            await dispatch( permissionsStore ).getCapabilities( id );
            toast( {
                type: 'success',
                title: __(
                    'Default permissions applied successfully',
                    'dokan'
                ),
            } );
        } catch ( error ) {
            toast( {
                type: 'error',
                title: __( 'Failed to apply default permissions', 'dokan' ),
            } );
        }
    };

    if ( notFound ) {
        return null;
    }

    return (
        <>
            <Fill name="dokan-header-actions">
                <DokanButton
                    variant="tertiary"
                    onClick={ () => setIsOpen( true ) }
                >
                    { __( 'Reset', 'dokan' ) }
                </DokanButton>
            </Fill>

            <DokanModal
                isOpen={ isOpen }
                namespace="quick-confirm-permission-reset"
                onConfirm={ applyDefaultPermissions }
                onClose={ () => setIsOpen( false ) }
                dialogTitle={ __( 'Reset Staff Permissions', 'dokan' ) }
                confirmationTitle={ __(
                    'Are you sure you want to proceed?',
                    'dokan'
                ) }
                confirmationDescription={ __(
                    'This will restore all permissions to their default permissions.',
                    'dokan'
                ) }
                confirmButtonText={ __( 'Yes, Reset', 'dokan' ) }
            />
        </>
    );
};

registerPlugin( 'dokan-manage-staff-permissions', {
    render: ResetButton,
    scope: 'dokan-manage-staff-permissions',
} );

const ManagePermissions = ( { navigate, params } ) => {
    const { id } = params;
    const toast = useToast();
    const [ isSubmitting, setIsSubmitting ] = useState( false );
    const [ localCapabilities, setLocalCapabilities ] = useState( {} );
    // Select data from the store
    const allCapabilities = useSelect(
        ( select ) => select( permissionsStore ).getAllCapabilities(),
        []
    );

    const editCapabilities = useSelect(
        ( select ) => select( permissionsStore ).getEditCapabilities(),
        []
    );

    const errorCode = useSelect(
        ( select ) => select( permissionsStore ).getErrorCode(),
        []
    );

    const isLoading = useSelect(
        ( select ) => select( permissionsStore ).isLoading(),
        []
    );
    const isStaff = usePermission( 'vendor_staff' );

    useEffect( () => {
        dispatch( permissionsStore ).fetchAllCapabilities();
    }, [] );

    useEffect( () => {
        if ( id ) {
            dispatch( permissionsStore ).setLoading( true );
            dispatch( permissionsStore ).getCapabilities( id );
        }
    }, [ id ] );

    useEffect( () => {
        setLocalCapabilities( editCapabilities );
    }, [ editCapabilities ] );

    const handleTogglePermission = ( key: string ) => {
        setLocalCapabilities( ( prev ) => ( {
            ...prev,
            [ key ]: ! prev[ key ],
        } ) );
    };

    const updateCapabilities = async () => {
        const capabilities = Object.entries( localCapabilities ).map(
            ( [ key, value ] ) => ( {
                capability: key,
                access: value,
            } )
        );

        return await apiFetch( {
            path: `dokan/v1/vendor-staff/${ id }/capabilities`,
            method: 'PUT',
            data: {
                id,
                capabilities,
            },
        } );
    };

    const handleSave = async () => {
        if ( ! id || isSubmitting ) {
            return;
        }

        setIsSubmitting( true );
        try {
            await updateCapabilities();
            await dispatch( permissionsStore ).updateEditCapabilities(
                localCapabilities
            );
            toast( {
                type: 'success',
                title: __( 'Permissions updated successfully', 'dokan' ),
            } );
        } catch ( error ) {
            toast( {
                type: 'error',
                title: __( 'Failed to update permissions', 'dokan' ),
            } );
        } finally {
            setIsSubmitting( false );
        }
    };

    const filterSectionName = ( section: string ) => {
        return section
            .replace( /_/g, ' ' )
            .replace( /\b\w/g, ( char ) => char.toUpperCase() );
    };

    // Show loading if data is not available
    if ( isLoading || ! allCapabilities || ! localCapabilities ) {
        return (
            <div className="p-4">
                <div className="flex justify-center items-center min-h-[200px]">
                    <Spinner />
                </div>
            </div>
        );
    }

    // Show not found page if staff is not found
    const NavigateToStaffList = () => (
        <DokanButton variant="primary" onClick={ () => navigate( '/staffs' ) }>
            { __( 'Back to List', 'dokan' ) }
        </DokanButton>
    );

    if ( errorCode === 404 ) {
        return <NotFound navigateButton={ <NavigateToStaffList /> } />;
    } else if ( errorCode === 403 || errorCode === 401 || isStaff ) {
        return <Forbidden navigateButton={ <NavigateToStaffList /> } />;
    }

    return (
        <div className="py-4">
            <div className="space-y-6">
                { Object.entries( allCapabilities.all ).map(
                    ( [ section, sectionCapabilities ] ) => (
                        <div key={ section } className="border rounded-lg p-4">
                            <h2 className="text-lg font-semibold mb-4 capitalize">
                                { filterSectionName( section ) }
                            </h2>
                            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                                { Object.entries( sectionCapabilities ).map(
                                    ( [ key, label ] ) => (
                                        <div
                                            key={ key }
                                            className="flex items-center space-x-3"
                                        >
                                            <SimpleCheckbox
                                                input={ {
                                                    type: 'checkbox',
                                                    id: key,
                                                } }
                                                checked={
                                                    !! localCapabilities[ key ]
                                                }
                                                onChange={ () =>
                                                    handleTogglePermission(
                                                        key
                                                    )
                                                }
                                                disabled={ isSubmitting }
                                                className="w-4 h-4 rounded border-gray-300"
                                            />
                                            <label
                                                htmlFor={ key }
                                                className={ `text-sm ${
                                                    isSubmitting
                                                        ? 'text-gray-500'
                                                        : ''
                                                }` }
                                            >
                                                { label }
                                            </label>
                                        </div>
                                    )
                                ) }
                            </div>
                        </div>
                    )
                ) }
            </div>

            <div className="mt-6 flex flex-wrap gap-4">
                <DokanButton onClick={ handleSave } disabled={ isSubmitting }>
                    { isSubmitting ? (
                        <span className="flex items-center gap-2">
                            <Spinner />
                            { __( 'Saving…', 'dokan' ) }
                        </span>
                    ) : (
                        __( 'Save Permissions', 'dokan' )
                    ) }
                </DokanButton>

                <DokanButton
                    variant="secondary"
                    type="button"
                    onClick={ () => navigate( '/staffs' ) }
                    disabled={ isSubmitting }
                >
                    { __( 'Cancel', 'dokan' ) }
                </DokanButton>
            </div>
            <DokanToaster />
        </div>
    );
};

export default ManagePermissions;
