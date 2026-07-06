import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from '@wordpress/element';
import {
    DokanToaster,
    MaskedInput,
    SimpleInput,
    useToast,
} from '@getdokan/dokan-ui';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { DokanButton, NotFound, Forbidden } from '@dokan/components';
import apiFetch from '@wordpress/api-fetch';
import StaffFormSkeleton from './StaffFormSkeleton';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { usePermission } from '@dokan/hooks';

const CreateStaff = ( { navigate, params } ) => {
    const isStaff = usePermission( 'vendor_staff' );
    const { id } = params;
    const [ formData, setFormData ] = useState( {
        ID: id,
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
    } );
    const [ isLoading, setIsLoading ] = useState( false );
    const toast = useToast();
    const [ errorCode, setErrorCode ] = useState( 0 );

    const updateStaffData = async ( staffData ) => {
        return await apiFetch( {
            path: `dokan/v1/vendor-staff/${ staffData.ID }`,
            method: 'PUT',
            data: staffData,
        } );
    };

    const createStaff = async ( staffData ) => {
        return await apiFetch( {
            path: `dokan/v1/vendor-staff`,
            method: 'POST',
            data: staffData,
        } );
    };

    const resetData = () => {
        setFormData( {
            ID: undefined,
            first_name: '',
            last_name: '',
            email: '',
            phone: '',
        } );
    };

    const handleSubmit = async ( e: React.FormEvent ) => {
        e.preventDefault();

        // Validate form data
        if (
            ! formData.first_name ||
            ! formData.last_name ||
            ! formData.email
        ) {
            toast( {
                type: 'error',
                title: __( 'Please fill in all required fields.', 'dokan' ),
            } );
            return;
        }

        try {
            setIsLoading( true );
            if ( formData.ID ) {
                await updateStaffData( formData );
                toast( {
                    type: 'success',
                    title: __( 'Staff member updated successfully.', 'dokan' ),
                } );
            } else {
                await createStaff( formData );
                toast( {
                    type: 'success',
                    title: __( 'Staff member created successfully.', 'dokan' ),
                } );
                // reset form data
                resetData();
            }
        } catch ( err ) {
            toast( {
                type: 'error',
                title: err.data?.message || err.message,
            } );
        } finally {
            setIsLoading( false );
        }
    };

    const handleChange = ( e: React.ChangeEvent< HTMLInputElement > ) => {
        const { name, value } = e.target;
        setFormData( ( prev ) => ( {
            ...prev,
            [ name ]: value,
        } ) );
    };

    const fetchStaffData = useCallback( async () => {
        if ( ! id ) {
            return;
        }
        try {
            setIsLoading( true );
            setErrorCode( 0 );
            const response = await apiFetch< any >( {
                path: `dokan/v1/vendor-staff/${ id }`,
                method: 'GET',
            } );
            if ( response.ID ) {
                setFormData( {
                    ...response,
                    first_name: response.first_name || '',
                    last_name: response.last_name || '',
                    email: response.user_email || '',
                    phone: response.phone || '',
                } );
            }
        } catch ( staffError ) {
            setErrorCode( staffError?.data?.status || staffError?.code );
            throw staffError;
        } finally {
            setIsLoading( false );
        }
    }, [ id ] );

    useEffect( () => {
        fetchStaffData().catch( resetData );
    }, [ id, fetchStaffData ] );

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
            { isLoading ? (
                <StaffFormSkeleton />
            ) : (
                <form className="space-y-2" onSubmit={ handleSubmit }>
                    <div>
                        <SimpleInput
                            label={ __( 'First Name', 'dokan' ) }
                            required
                            input={ {
                                id: 'first_name',
                                name: 'first_name',
                                type: 'text',
                                placeholder: __( 'First name', 'dokan' ),
                            } }
                            value={ formData.first_name }
                            onChange={ handleChange }
                            disabled={ isLoading }
                        />
                    </div>
                    <div>
                        <SimpleInput
                            label={ __( 'Last Name', 'dokan' ) }
                            required
                            input={ {
                                id: 'last_name',
                                name: 'last_name',
                                type: 'text',
                                placeholder: __( 'Last name', 'dokan' ),
                            } }
                            value={ formData.last_name }
                            onChange={ handleChange }
                            disabled={ isLoading }
                        />
                    </div>
                    <div>
                        <SimpleInput
                            label={ __( 'Email Address', 'dokan' ) }
                            required
                            input={ {
                                id: 'email',
                                name: 'email',
                                type: 'email',
                                placeholder: __( 'Email Address', 'dokan' ),
                            } }
                            value={ formData.email }
                            onChange={ handleChange }
                            disabled={ isLoading }
                        />
                    </div>
                    <div>
                        <MaskedInput
                            label={ __( 'Phone', 'dokan' ) }
                            input={ {
                                id: 'phone',
                                name: 'phone',
                                type: 'text',
                                placeholder: __( 'Phone', 'dokan' ),
                            } }
                            value={ formData.phone }
                            onChange={ ( event: any ) => {
                                const { name, rawValue } = event.target;
                                setFormData( ( prev ) => ( {
                                    ...prev,
                                    [ name ]: rawValue,
                                } ) );
                            } }
                            disabled={ isLoading }
                            maskRule={ {
                                phone: true,
                            } }
                        />
                    </div>

                    <div className="pt-4 flex gap-4 justify-end">
                        <DokanButton
                            type="submit"
                            loading={ isLoading }
                            disabled={ isLoading }
                        >
                            { formData.ID
                                ? __( 'Update', 'dokan' )
                                : __( 'Create', 'dokan' ) }
                        </DokanButton>
                    </div>
                </form>
            ) }
            <DokanToaster />
        </div>
    );
};

export default CreateStaff;
