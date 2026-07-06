import { Card } from '@getdokan/dokan-ui';
import {
    Download,
    CircleCheck,
    OctagonAlert,
    TriangleAlert,
} from 'lucide-react';
import { RawHTML, useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { VerificationRequestList } from './VerificationRequestType';
import { VerificationMethod } from './VerificationMethodType';
import { twMerge } from 'tailwind-merge';
import VerificationCardSkeletonLoader from './VerificationCardSkeletonLoader';
import { NoInformation } from '@dokan/components';
import { __ } from '@wordpress/i18n';
import DropdownSelect from './DropdownSelect';

interface VerificationTabProps {
    vendor: Record< any, any >;
    vendorStats: Record< any, any > | null;
}

const VerificationTab = ( { vendor }: VerificationTabProps ) => {
    const [ verificationRequests, setVerificationRequests ] =
        useState< VerificationRequestList >( [] );
    const [ verificationMethods, setVerificationMethods ] = useState<
        VerificationMethod[]
    >( [] );
    const [ isLoading, setIsLoading ] = useState( true );

    const getVerificationRequest = ( method: VerificationMethod ) => {
        return verificationRequests.find(
            ( req ) => req.method_id === method.id
        );
    };

    const getStatusIcon = ( method: VerificationMethod ) => {
        const request = getVerificationRequest( method );

        if ( ! request ) {
            return null;
        }

        switch ( request?.status ) {
            case 'pending':
                return <OctagonAlert className="w-4 h-4 text-red-500" />;
            case 'approved':
                return <CircleCheck className="w-4 h-4 text-green-500" />;
            case 'rejected':
                return <TriangleAlert className="w-4 h-4 text-yellow-500" />;
            default:
                return null;
        }
    };

    const getStatusText = ( method: VerificationMethod ) => {
        const request = getVerificationRequest( method );

        return request?.status_title ?? '';
    };

    const getStatusColor = ( method: VerificationMethod ) => {
        const request = verificationRequests.find(
            ( req ) => req.method_id === method.id
        );

        switch ( request?.status ) {
            case 'pending':
                return twMerge( 'text-red-500' );
            case 'approved':
                return twMerge( 'text-green-500' );
            case 'rejected':
                return twMerge( 'text-yellow-500' );
            default:
                return '';
        }
    };

    const getDocuments = ( methodId: number ) => {
        const documents = [];

        // now find all the document urls of a method.
        verificationRequests.forEach( ( request ) => {
            if ( request.method_id === methodId ) {
                Object.entries( request.document_urls ).forEach(
                    ( [ key, value ] ) => {
                        documents.push( {
                            title: value.title,
                            url: value.url,
                        } );
                    }
                );
            }
        } );

        return documents;
    };

    const downloadDocument = ( documentItem ) => {
        const link = window.document.createElement( 'a' );
        link.href = documentItem.url;
        link.download = '';
        window.document.body.appendChild( link );
        link.click();
        window.document.body.removeChild( link );
    };

    const handleStatusChange = async (
        method: VerificationMethod,
        status: string
    ) => {
        try {
            await apiFetch( {
                path: `/dokan/v1/verification-requests/${ getVerificationRequest(
                    method
                )?.id }`,
                method: 'POST',
                data: { status },
            } );
            getVerificationsRequests();
        } catch ( error ) {
            // eslint-disable-next-line no-console
            console.error( 'Error updating status:', error );
        }
    };

    const getVerificationsRequests = () => {
        if ( ! vendor || ! vendor.id ) {
            setIsLoading( true );
            return;
        }

        Promise.all( [
            apiFetch( {
                path: addQueryArgs( '/dokan/v1/verification-requests', {
                    vendor_id: vendor.id,
                } ),
            } ),
            apiFetch( { path: '/dokan/v1/verification-methods' } ),
        ] )
            .then( ( [ requests, methods ] ) => {
                setVerificationRequests( requests as VerificationRequestList );
                setVerificationMethods( methods as VerificationMethod[] );

                setIsLoading( false );
            } )
            .catch( ( error ) => {
                // Handle error as needed
                console.error( error );

                setIsLoading( false );
            } );
    };

    useEffect( () => {
        getVerificationsRequests();
    }, [] );

    if ( isLoading ) {
        return <VerificationCardSkeletonLoader />;
    } else if (
        verificationMethods.length === 0 ||
        verificationRequests.length === 0
    ) {
        return <NoInformation />;
    }

    return (
        <div>
            { verificationMethods.map( ( method, index ) => (
                <Card key={ index } className="bg-white shadow mb-6 last:mb-0">
                    { /* Section Header */ }
                    <div className="flex items-center justify-between border-b p-6">
                        <div className="flex items-center gap-4">
                            <h3 className="text-lg font-medium text-neutral-900">
                                { method?.title }
                            </h3>
                            { getStatusText( method ) && (
                                <div
                                    className={ `flex items-center gap-2 py-1 rounded-full text-sm font-medium ${ getStatusColor(
                                        method
                                    ) }` }
                                >
                                    { getStatusIcon( method ) }
                                    { getStatusText( method ) }
                                </div>
                            ) }
                        </div>
                        <div>
                            { getVerificationRequest( method )?.status !==
                                'rejected' && (
                                <DropdownSelect
                                    onChange={ ( value ) =>
                                        handleStatusChange( method, value )
                                    }
                                />
                            ) }
                        </div>
                    </div>

                    { /* Documents List */ }
                    { getDocuments( method.id ).length > 0 ? (
                        <div>
                            { getDocuments( method.id ).map(
                                ( documentItem, docIndex ) => (
                                    <div
                                        key={ docIndex }
                                        className="flex items-center justify-between border-b last:border-b-0 p-6"
                                    >
                                        <div className="flex items-center gap-3">
                                            <a
                                                href={ documentItem.url }
                                                className="font-medium text-neutral-900 text-wrap"
                                                target="_blank"
                                                rel="noreferrer"
                                            >
                                                <RawHTML>
                                                    { documentItem.title }
                                                </RawHTML>
                                            </a>
                                        </div>
                                        <button
                                            className="p-1 text-neutral-600 hover:bg-neutral-50 transition-colors border rounded"
                                            onClick={ () =>
                                                downloadDocument( documentItem )
                                            }
                                        >
                                            <Download className="w-5 h-5" />
                                        </button>
                                    </div>
                                )
                            ) }
                        </div>
                    ) : (
                        <div className="p-6">
                            <p className="text-red-500">
                                { __( 'No documents submitted', 'dokan' ) }
                            </p>
                        </div>
                    ) }
                </Card>
            ) ) }
        </div>
    );
};

export default VerificationTab;
