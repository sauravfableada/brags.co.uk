import SkeletonCard from './SkeletonCard';
import VerificationCard from './VerificationCard';
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { VerificationResponse } from '../../frontend/types';
import { DokanToaster } from '@getdokan/dokan-ui';
import PhoneVerificationCard from './PhoneVerificationCard';
import SocialProfile from './SocialProfile';

const Verification = () => {
    const [ verificationMethods, setVerificationMethods ] =
        useState< VerificationResponse >( {
            verification_methods: [],
            social_providers: [],
            phone_verification: {
                is_configured: false,
                active_gateway: '',
            },
        } );

    const [ isLoading, setIsLoading ] = useState( true );

    const fetchVerificationMethods = async () => {
        setIsLoading( true );
        try {
            const response = await apiFetch< VerificationResponse >( {
                path: '/dokan/v1/vendor-verification',
            } );
            setVerificationMethods( response );
        } catch ( error ) {
            throw error;
        } finally {
            setIsLoading( false );
        }
    };
    useEffect( () => {
        // eslint-disable-next-line no-console
        fetchVerificationMethods().catch( console.error );
    }, [] );

    const LoadingSkeletonCard = () => {
        return (
            <div className="flex flex-col gap-4">
                { Array.from( { length: 4 } ).map( ( _, index ) => (
                    <SkeletonCard key={ index } />
                ) ) }
            </div>
        );
    };

    if ( isLoading ) {
        return <LoadingSkeletonCard />;
    }

    return (
        <div>
            <div className="flex flex-col gap-4 ">
                { verificationMethods.verification_methods.map(
                    ( method, index ) => (
                        <VerificationCard
                            key={ index }
                            method={ method }
                            fetchMethod={ fetchVerificationMethods }
                        />
                    )
                ) }
                <PhoneVerificationCard
                    phone={ verificationMethods.phone_verification }
                />
                <SocialProfile
                    providers={ verificationMethods.social_providers }
                />
            </div>
            <DokanToaster />
        </div>
    );
};

export default Verification;
