import { Card, useToast } from '@getdokan/dokan-ui';
import {
    FileDocument,
    LastStatus,
    SubmitResponse,
    VarificationFormData,
    VerificationMethod,
} from '../../frontend/types';
import { __ } from '@wordpress/i18n';
import { Trash } from 'lucide-react';

import {
    DokanBadge,
    DokanButton,
    MediaUploader,
    DokanModal,
    // @ts-ignore
    // eslint-disable-next-line import/no-unresolved
} from '@dokan/components';

import { RawHTML, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { useSelect } from '@wordpress/data';
import { Slot } from '@wordpress/components';

const statusMap = {
    pending: 'primary',
    approved: 'success',
    rejected: 'danger',
    cancelled: 'danger',
};

type CardProp = {
    method: VerificationMethod;
    fetchMethod: () => void;
};

const VerificationCard = ( { method, fetchMethod }: CardProp ) => {
    const toast = useToast();
    const currentUser = useSelect( ( select ) => {
        // @ts-ignore
        return select( 'dokan/core' ).getCurrentUser();
    }, [] );
    const lastStatus = method.last_verification?.status;
    const badgeStatus = statusMap[ lastStatus ] || 'pending';
    const [ startVerification, setStartVerification ] = useState( false );
    const [ selectedFiles, setSelectedFiles ] = useState(
        [] as FileDocument[]
    );
    const [ isLoading, setIsLoading ] = useState( false );
    const [ isConfirm, setIsConfirm ] = useState( false );

    const prepareFormData = ( data = {} ) => {
        return {
            vendor_id: currentUser.id, // replace with actual vendor id
            method_id: method.id,
            documents: selectedFiles.map( ( file ) => file.id ),
            ...data,
        };
    };

    const validateFormData = ( formData: VarificationFormData ): boolean => {
        if ( formData.documents.length === 0 ) {
            toast( {
                type: 'error',
                title: __( 'Please upload at least one file.', 'dokan' ),
            } );
            return false;
        }
        return true;
    };

    const submitHandler = async () => {
        // handle upload into server
        const formData = prepareFormData();
        if ( ! validateFormData( formData ) ) {
            return;
        }
        try {
            setIsLoading( true );
            const response = await apiFetch< SubmitResponse >( {
                path: 'dokan/v1/verification-requests',
                method: 'POST',
                data: formData,
            } );
            toast( {
                type: 'success',
                title: __(
                    'Verification request submitted successfully.',
                    'dokan'
                ),
            } );
            method.last_verification = {
                id: response.id,
                label: response.status_title,
                note: '',
                status: response.status,
                documents: response.documents.map( ( id ) => {
                    return {
                        id,
                        name: response.document_urls[ id ].title,
                        url: response.document_urls[ id ].url,
                    };
                } ),
            } satisfies LastStatus;
        } catch ( error ) {
            // handle error
            toast( {
                type: 'error',
                title: error.message,
            } );
        } finally {
            setIsLoading( false );
            setStartVerification( false );
        }
    };

    const cancelVerification = async () => {
        const formData = prepareFormData( {
            id: method.last_verification.id,
            status: 'cancelled',
            documents: method.last_verification.documents.map(
                ( file ) => file.id
            ),
        } );
        if ( ! validateFormData( formData ) ) {
            return;
        }
        try {
            setIsLoading( true );
            await apiFetch( {
                path: `dokan/v1/verification-requests/${ method.id }`,
                method: 'PUT',
                data: formData,
            } );
            toast( {
                type: 'success',
                title: __(
                    'Verification request cancelled successfully.',
                    'dokan'
                ),
            } );
        } catch ( error ) {
            // handle error
            toast( {
                type: 'error',
                title: error.message,
            } );
        } finally {
            setIsLoading( false );
            fetchMethod();
        }
    };

    const fileUploadHandler = ( file: FileDocument ) => {
        setSelectedFiles( ( prev ) => [ ...prev, file ] );
    };

    const removeFileHandler = ( fileId: string ) => {
        setSelectedFiles( ( prev ) =>
            prev.filter( ( file ) => file.id !== fileId )
        );
    };

    const resubmitHandler = () => {
        setStartVerification( true );
        setSelectedFiles( method.last_verification.documents );
    };

    return (
        <Card>
            <Card.Header>
                <h4 className="text-base font-medium p-0 m-0">
                    { method.title }{ ' ' }
                    <span className="text-dokan-danger-text font-normal text-sm">
                        { method.required ? __( '(Required)', 'dokan' ) : '' }
                    </span>
                </h4>
            </Card.Header>
            <Card.Body>
                { ! startVerification &&
                lastStatus &&
                lastStatus !== 'cancelled' ? (
                    <div className="space-y-2">
                        <div className="flex items-center space-x-2">
                            <span>
                                { __(
                                    'Your verification request is',
                                    'dokan'
                                ) }{ ' ' }
                            </span>
                            <DokanBadge
                                variant={ badgeStatus }
                                label={ method.last_verification.label }
                            />
                        </div>
                        { method.kind === 'address' && (
                            <div>
                                <div className="font-bold">
                                    { __( 'Address:', 'dokan' ) }
                                </div>
                                <address>
                                    <RawHTML>{ method.seller_address }</RawHTML>
                                </address>
                            </div>
                        ) }
                        { method.last_verification.note && (
                            <div>
                                <div className="font-bold">
                                    { __( 'Note:', 'dokan' ) }
                                </div>
                                { method.last_verification.note }
                            </div>
                        ) }

                        { method.last_verification.documents.length > 0 && (
                            <div>
                                <div className="font-bold">
                                    { __( 'Files:', 'dokan' ) }
                                </div>
                                <div className="list-disc w-max">
                                    { method.last_verification.documents.map(
                                        ( document ) => (
                                            <a
                                                key={ document.id }
                                                href={ document.url }
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            >
                                                <RawHTML>
                                                    { document.name }
                                                </RawHTML>
                                            </a>
                                        )
                                    ) }
                                </div>
                            </div>
                        ) }

                        { lastStatus === 'rejected' && (
                            <DokanButton
                                className="mt-2"
                                onClick={ resubmitHandler }
                            >
                                { __( 'Resubmit', 'dokan' ) }
                            </DokanButton>
                        ) }
                        { lastStatus !== 'approved' && (
                            <>
                                { lastStatus !== 'rejected' && (
                                    <>
                                        <DokanButton
                                            label={ __( 'Cancel', 'dokan' ) }
                                            loading={ isLoading }
                                            disabled={ isLoading }
                                            onClick={ () =>
                                                setIsConfirm( true )
                                            }
                                        />
                                    </>
                                ) }
                            </>
                        ) }
                    </div>
                ) : (
                    <>
                        { startVerification ? (
                            <div className="flex flex-col gap-2">
                                <div>
                                    { selectedFiles.map( ( file ) => (
                                        <div
                                            key={ file.id }
                                            className="flex items-center gap-2"
                                        >
                                            <a
                                                href={ file.url }
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            >
                                                <RawHTML>{ file.name }</RawHTML>
                                            </a>
                                            <DokanButton
                                                size="sm"
                                                className="!p-1"
                                                variant="danger"
                                                onClick={ () =>
                                                    removeFileHandler( file.id )
                                                }
                                            >
                                                <Trash className="size-5" />
                                            </DokanButton>
                                        </div>
                                    ) ) }
                                </div>
                                <div>{ method.help_text }</div>
                                <MediaUploader onSelect={ fileUploadHandler }>
                                    <DokanButton
                                        variant="secondary"
                                        className="w-full"
                                    >
                                        <i className="fas fa-cloud-upload-alt" />
                                        { __( 'Upload Files', 'dokan' ) }
                                    </DokanButton>
                                </MediaUploader>
                                <Slot
                                    name="dokan_vendor_verification_before_button"
                                    fillProps={ {
                                        method,
                                        currentUser,
                                    } }
                                />
                                <div className="flex gap-2 mt-2">
                                    <DokanButton
                                        variant="primary"
                                        label={ __( 'Submit', 'dokan' ) }
                                        loading={ isLoading }
                                        disabled={ isLoading }
                                        onClick={ submitHandler }
                                    />

                                    <DokanButton
                                        variant="secondary"
                                        onClick={ () =>
                                            setStartVerification( false )
                                        }
                                    >
                                        { __( 'Cancel', 'dokan' ) }
                                    </DokanButton>
                                </div>
                            </div>
                        ) : (
                            <DokanButton
                                className="mt-2"
                                onClick={ () => setStartVerification( true ) }
                            >
                                { __( 'Start Verification', 'dokan' ) }
                            </DokanButton>
                        ) }
                    </>
                ) }
            </Card.Body>
            <DokanModal
                isOpen={ isConfirm }
                namespace="quick-confirm-cancel-documents"
                onConfirm={ cancelVerification }
                onClose={ () => setIsConfirm( false ) }
                dialogTitle={ __( 'Cancel Verification Request', 'dokan' ) }
                confirmationTitle={ __(
                    'Are you sure that you want to cancel the verification request?',
                    'dokan'
                ) }
                confirmationDescription={ __(
                    'This action cannot be undone.',
                    'dokan'
                ) }
                confirmButtonText={ __( 'Yes, Cancel', 'dokan' ) }
            />
        </Card>
    );
};

export default VerificationCard;
