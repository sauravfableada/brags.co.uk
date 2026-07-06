import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { BadgeEvent } from './types';
import { Plus, Trash } from 'lucide-react';
import { DokanButton, Select } from '@dokan/components';
import { getInputGroupIconElement } from './icons';

interface BadgeLevel {
    id: number;
    level_condition: string;
    level_data: string;
}

interface VerificationType {
    id: string;
    title: string;
    disabled: boolean;
}

interface VerifiedSellerProps {
    event: BadgeEvent;
    levels: BadgeLevel[];
    onLevelsChange: ( levels: BadgeLevel[] ) => void;
}

const getDefaultBadgeLevel = (): BadgeLevel => ( {
    id: 0,
    level_condition: '',
    level_data: '1',
} );

export default function VerifiedSeller( {
    event,
    levels,
    onLevelsChange,
}: VerifiedSellerProps ) {
    const [ badgeLevels, setBadgeLevels ] = useState< BadgeLevel[] >( () => {
        if ( ! levels || levels.length === 0 ) {
            return [ getDefaultBadgeLevel() ];
        }
        return levels.map( ( lvl ) => ( {
            ...lvl,
            level_data: String( ( lvl as any ).level_data ?? '' ),
        } ) );
    } );

    const [ verificationTypes, setVerificationTypes ] = useState<
        Record< string, VerificationType >
    >( {} );
    const [ isLoading, setIsLoading ] = useState( false );
    const [ addNewButtonDisabled, setAddNewButtonDisabled ] = useState( false );

    // Fetch verification types
    useEffect( () => {
        setIsLoading( true );
        apiFetch< Record< string, VerificationType > >( {
            path: '/dokan/v1/seller-badge/verification-types',
        } )
            .then( ( data ) => {
                setVerificationTypes( data || {} );
            } )
            .catch( () => {
                setVerificationTypes( {} );
            } )
            .finally( () => {
                setIsLoading( false );
            } );
    }, [] );

    // Sync levels with parent
    useEffect( () => {
        onLevelsChange( badgeLevels );
    }, [ badgeLevels, onLevelsChange ] );

    // Update verification types disabled state when levels change
    useEffect( () => {
        disableVerificationTypeOptions();
        disableAddNewButton();
    }, [ badgeLevels, verificationTypes ] );

    const addLevel = () => {
        setBadgeLevels( [ ...badgeLevels, getDefaultBadgeLevel() ] );
    };

    const removeLevel = ( index: number ) => {
        const selectedMethod = badgeLevels[ index ].level_condition;
        if ( verificationTypes[ selectedMethod ] ) {
            const newTypes = { ...verificationTypes };
            newTypes[ selectedMethod ].disabled = false;
            setVerificationTypes( newTypes );
        }
        const newLevels = badgeLevels.filter( ( _, i ) => i !== index );
        setBadgeLevels( newLevels );
    };

    const setLevelCondition = ( index: number, value: string ) => {
        const newLevels = [ ...badgeLevels ];
        newLevels[ index ].level_condition = String( value );
        setBadgeLevels( newLevels );
    };

    const disableVerificationTypeOptions = () => {
        const newTypes = { ...verificationTypes };
        // Reset all to enabled
        Object.keys( newTypes ).forEach( ( key ) => {
            newTypes[ key ].disabled = false;
        } );
        // Disable selected ones
        badgeLevels.forEach( ( level ) => {
            if ( newTypes[ level.level_condition ] ) {
                newTypes[ level.level_condition ].disabled = true;
            }
        } );
        // Only update state if something actually changed to avoid update loops
        const didChange = Object.keys( newTypes ).some( ( key ) => {
            const prev = verificationTypes[ key ]?.disabled ?? false;
            const next = newTypes[ key ]?.disabled ?? false;
            return prev !== next;
        } );
        if ( didChange ) {
            setVerificationTypes( newTypes );
        }
    };

    const disableAddNewButton = () => {
        const length = badgeLevels.length - 1;
        if ( badgeLevels[ length ]?.level_condition === '' ) {
            setAddNewButtonDisabled( true );
            return;
        }
        // Check if we have available verification method to choose from
        const hasAvailable = Object.values( verificationTypes ).some(
            ( type ) => ! type.disabled
        );
        setAddNewButtonDisabled( ! hasAvailable );
    };

    const getValue = ( label ) => {
        const found = Object.values( verificationTypes ).find(
            ( type ) => String( type.id ) === String( label.level_condition )
        );

        if ( found ) {
            return {
                label: found.title,
                value: found.id,
            };
        }
        return {
            label: __( 'Select a method', 'dokan' ),
            value: '',
        };
    };
    const getOptions = () => {
        const options = [
            {
                label: __( 'Select a method', 'dokan' ),
                value: '',
                isDisabled: false,
            },
        ];

        Object.values( verificationTypes ).map( ( type ) => {
            options.push( {
                label: type.title,
                value: type.id,
                isDisabled: type.disabled,
            } );
        } );

        return options;
    };

    if ( isLoading ) {
        return (
            <div className="flex items-center justify-center py-8">
                <div className="text-gray-500">
                    { __( 'Loading…', 'dokan' ) }
                </div>
            </div>
        );
    }

    return (
        <div className="dokan-logical-container">
            <ul className="list-none p-0 m-0">
                { badgeLevels.map( ( level, index ) => (
                    <li
                        key={ `${ level.id }-${ index }` }
                        className="flex items-center gap-[28px] mb-[20px]"
                    >
                        <span className="rounded-2xl px-4 py-1.5 inline-block text-xs font-semibold text-[#575757] border border-[#E9E9E9]">
                            { __( 'Level', 'dokan' ) } { index + 1 }
                        </span>
                        <div className="flex items-center gap-[28px] text-sm">
                            { event.condition_text?.prefix }

                            <div className="input-group flex items-stretch">
                                <span className="input-group-text flex items-center px-3 !m-[-1px] !h-[42px] bg-[#f5f5f5] border border-[#e2e2e2] border-r-0 rounded-l-md">
                                    { getInputGroupIconElement( event, 'data', { width: 16, height: 16, className: 'inline-block align-middle' } ) }
                                </span>
                                <Select
                                    options={ getOptions() }
                                    value={ getValue( level ) }
                                    onChange={ ( option ) => {
                                        setLevelCondition(
                                            index,
                                            option?.value || ''
                                        );
                                    } }
                                    className="!min-w-48 dokan-seller-badge-select-input-box"
                                    menuPortalTarget={ document.body }
                                />
                            </div>

                            { event.condition_text?.suffix && (
                                <span>{ event.condition_text.suffix }</span>
                            ) }
                            { index > 0 && (
                                <DokanButton
                                    label={ <Trash size={ 16 } /> }
                                    onClick={ () => removeLevel( index ) }
                                    className="!w-9 !h-9 !bg-white hover:!bg-[#E4E4EB] !border !border-[#E9E9E9] !text-[#828282] hover:!text-[#828282]"
                                />
                            ) }
                        </div>
                    </li>
                ) ) }
            </ul>
            <div>
                <DokanButton
                    label={
                        <div className="flex items-center gap-[6px]">
                            <Plus size={ 16 } />
                            <span>{ __( 'Add Level', 'dokan' ) }</span>
                        </div>
                    }
                    onClick={ addLevel }
                    disabled={ addNewButtonDisabled }
                    variant="secondary"
                    size="sm"
                />
            </div>
        </div>
    );
}
