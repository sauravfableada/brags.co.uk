import { Card, MaskedInput, SimpleInput, useToast } from '@getdokan/dokan-ui';
import { __ } from '@wordpress/i18n';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { DokanAlert, DokanButton } from '@dokan/components';
import { PhoneVerification } from '../../frontend/types';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Slot } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import CountdownTimer from '../../../../../src/components/CountdownTimer';

type PhoneCardProps = {
    phone: PhoneVerification;
    fetchMethod?: () => void;
};

const defaultResendTime = 60; // Default resend time in seconds

const PhoneVerificationCard = ( { phone }: PhoneCardProps ) => {
    const currentUser = useSelect( ( select ) => {
        // @ts-ignore
        return select( 'dokan/core' ).getCurrentUser();
    }, [] );
    const toast = useToast();
    const [ phoneNumber, setPhoneNumber ] = useState( '' );
    const [ isLoading, setIsLoading ] = useState( false );
    const [ verifyOtp, setVerifyOtp ] = useState( false );
    const [ optCode, setOtpCode ] = useState( '' );
    const [ canResend, setCanResend ] = useState( false );

    const handleTimerComplete = () => {
        setCanResend( true );
    };

    const verifyOtpHandler = async () => {
        // handle otp verify
        if ( ! optCode ) {
            toast( {
                type: 'error',
                title: __( 'Please enter OTP', 'dokan' ),
            } );
            return;
        }
        try {
            setIsLoading( true );
            await apiFetch( {
                path: 'dokan/v1/vendor-verification/verify-otp',
                method: 'POST',
                data: {
                    sms_code: optCode,
                },
            } );
            toast( {
                type: 'success',
                title: __( 'OTP verified successfully', 'dokan' ),
            } );
            setVerifyOtp( false );
            setPhoneNumber( '' );
            setOtpCode( '' );
            setCanResend( false );
            phone.phone_status = 'verified';
        } catch ( error ) {
            setIsLoading( false );
            toast( {
                type: 'error',
                title: error.message,
            } );
        }
    };

    const sendOtpCode = async ( isResend = false ) => {
        if ( verifyOtp && ! isResend ) {
            await verifyOtpHandler();
            return;
        }
        // handle otp send
        if ( ! phoneNumber ) {
            toast( {
                type: 'error',
                title: __( 'Please enter a phone number', 'dokan' ),
            } );
            return;
        }
        try {
            setIsLoading( true );
            await apiFetch( {
                path: 'dokan/v1/vendor-verification/send-otp',
                method: 'POST',
                data: {
                    phone: phoneNumber,
                },
            } );
            toast( {
                type: 'success',
                title: isResend
                    ? __( 'OTP resent successfully', 'dokan' )
                    : __( 'OTP sent successfully', 'dokan' ),
            } );
            setVerifyOtp( true );
            setCanResend( false );
        } catch ( error ) {
            toast( {
                type: 'error',
                title: error.message,
            } );
        } finally {
            setIsLoading( false );
        }
    };

    const handleResendOtp = () => {
        sendOtpCode( true );
    };

    if ( ! phone.is_configured ) {
        return null;
    }

    return (
        <Card>
            <Card.Header>
                <h4 className="text-base font-medium p-0 m-0">
                    { __( 'Phone Verification', 'dokan' ) }
                </h4>
            </Card.Header>
            <Card.Body>
                { phone.phone_status !== 'verified' ? (
                    <div>
                        { phone.active_gateway === 'nexmo' ? (
                            <div className="[&_p]:m-0">
                                <DokanAlert variant="warning">
                                    { __(
                                        'When entering US phone numbers, exclude the plus sign from the phone number.',
                                        'dokan'
                                    ) }
                                </DokanAlert>
                            </div>
                        ) : (
                            <div className="w-1/2">
                                { verifyOtp ? (
                                    <SimpleInput
                                        label={ __( 'Enter OTP:', 'dokan' ) }
                                        value={ optCode }
                                        onChange={ ( event ) => {
                                            setOtpCode( event.target.value );
                                        } }
                                        input={ {
                                            id: 'otp',
                                            name: 'otp',
                                            type: 'text',
                                            placeholder: __( 'OTP', 'dokan' ),
                                        } }
                                    />
                                ) : (
                                    <MaskedInput
                                        label={ __( 'Phone No:', 'dokan' ) }
                                        input={ {
                                            id: 'phone',
                                            name: 'phone',
                                            type: 'text',
                                            placeholder: __( 'Phone', 'dokan' ),
                                        } }
                                        value={ phoneNumber }
                                        onChange={ ( event: any ) => {
                                            const { rawValue } = event.target;
                                            setPhoneNumber( rawValue );
                                        } }
                                        maskRule={ {
                                            phone: true,
                                        } }
                                    />
                                ) }
                                { ! canResend && verifyOtp && (
                                    <CountdownTimer
                                        time={ defaultResendTime }
                                        onComplete={ handleTimerComplete }
                                    />
                                ) }
                                <Slot
                                    name="dokan_vendor_verification_before_button"
                                    fillProps={ {
                                        currentUser,
                                    } }
                                />
                                <div className="flex items-center gap-3 mt-4">
                                    <DokanButton
                                        label={
                                            verifyOtp
                                                ? __( 'Verify', 'dokan' )
                                                : __( 'Send', 'dokan' )
                                        }
                                        variant="primary"
                                        disabled={ isLoading }
                                        onClick={ () => sendOtpCode() }
                                        loading={ isLoading && ! canResend }
                                    />
                                    { verifyOtp && (
                                        <div className="flex items-center gap-2">
                                            <DokanButton
                                                label={ __(
                                                    'Resend',
                                                    'dokan'
                                                ) }
                                                variant="secondary"
                                                disabled={
                                                    ! canResend || isLoading
                                                }
                                                onClick={ handleResendOtp }
                                                loading={
                                                    isLoading && canResend
                                                }
                                            />
                                        </div>
                                    ) }
                                </div>
                            </div>
                        ) }
                    </div>
                ) : (
                    <div className="[&_p]:m-0">
                        <DokanAlert variant="success">
                            { __( 'Your Verified Phone number is :', 'dokan' ) }{ ' ' }
                            { phone.phone_no }
                        </DokanAlert>
                    </div>
                ) }
            </Card.Body>
        </Card>
    );
};

export default PhoneVerificationCard;
