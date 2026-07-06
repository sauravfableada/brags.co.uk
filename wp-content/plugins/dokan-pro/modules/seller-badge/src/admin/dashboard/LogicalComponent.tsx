import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { BadgeEvent } from './types';
import { DokanButton, DebouncedInput } from '@dokan/components';
import { Plus, Trash } from 'lucide-react';
import { getInputGroupIconElement } from './icons';

interface BadgeLevel {
    id: number;
    level_condition: string;
    level_data: string;
    formatted_condition?: string;
}

interface LogicalComponentProps {
    event: BadgeEvent;
    levels: BadgeLevel[];
    onLevelsChange: ( levels: BadgeLevel[] ) => void;
}

const getDefaultBadgeLevel = (): BadgeLevel => ( {
    id: 0,
    level_condition: '>',
    level_data: '10',
} );

export default function LogicalComponent( {
    event,
    levels,
    onLevelsChange,
}: LogicalComponentProps ) {
    const [ badgeLevels, setBadgeLevels ] = useState< BadgeLevel[] >( () => {
        if ( ! levels || levels.length === 0 ) {
            return [ getDefaultBadgeLevel() ];
        }
        // Normalize incoming levels to ensure level_data is a string
        return levels.map( ( lvl ) => ( {
            ...lvl,
            level_data: String( lvl.level_data ?? '' ),
        } ) );
    } );

    const [ addNewButtonDisabled, setAddNewButtonDisabled ] = useState( false );

    // Sync levels with parent
    useEffect( () => {
        onLevelsChange( badgeLevels );
    }, [ badgeLevels, onLevelsChange ] );

    useEffect( () => {
        disableAddNewButton();
    }, [ badgeLevels ] );

    const addLevel = () => {
        const length = badgeLevels.length - 1;
        const lastLevel = badgeLevels[ length ];
        const nextVal = parseInt( String( lastLevel.level_data ) ) + 1;
        const newLevel: BadgeLevel = {
            ...lastLevel,
            id: 0,
            level_data: String( nextVal ),
        } as BadgeLevel;
        setBadgeLevels( [ ...badgeLevels, newLevel ] );
    };

    const removeLevel = ( index: number ) => {
        const newLevels = badgeLevels.filter( ( _, i ) => i !== index );
        setBadgeLevels( newLevels );
    };

    const setLevelData = ( index: number, value: string ) => {
        const newLevels = [ ...badgeLevels ];
        let currentValue = parseInt( value );

        if ( currentValue < 0 || isNaN( currentValue ) ) {
            currentValue = 1;
        }

        newLevels[ index ].level_data = String( currentValue );

        // Sort levels by level_data
        newLevels.sort( ( a, b ) => {
            const value1 = parseInt( String( a.level_data ) );
            const value2 = parseInt( String( b.level_data ) );
            return value1 - value2;
        } );

        // Ensure each level is at least 1 greater than previous
        for ( let i = 0; i < newLevels.length; i++ ) {
            const current = parseInt( String( newLevels[ i ].level_data ) );
            if ( i === 0 ) {
                newLevels[ 0 ].level_data = current > 0 ? current : 1;
            } else {
                const previous = parseInt(
                    String( newLevels[ i - 1 ].level_data )
                );
                const minValue = previous + 1;
                newLevels[ i ].level_data =
                    current <= minValue ? minValue : current;
            }
        }

        setBadgeLevels( newLevels );
    };

    const getMinimumLevelData = ( previousIndex: number ): number => {
        const data = badgeLevels[ previousIndex ];
        return parseInt( String( data.level_data ) ) + 1;
    };

    const disableAddNewButton = () => {
        if ( ! badgeLevels.length ) {
            setAddNewButtonDisabled( false );
            return;
        }
        const length = badgeLevels.length - 1;
        const lastLevel = badgeLevels[ length ];
        setAddNewButtonDisabled(
            ! lastLevel.level_condition ||
                lastLevel.level_condition.length === 0 ||
                ! lastLevel.level_data
        );
    };

    const getIconClass = () => {
        if ( event.input_group_icon && event.input_group_icon.data ) {
            return event.input_group_icon.data;
        }
        return 'icon-count';
    };

    return (
        <div className="mt-3">
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
                            <span>{ event.condition_text?.prefix }</span>
                            <div>
                                <DebouncedInput
                                    addOnLeft={ getInputGroupIconElement( event, 'data', { width: 16, height: 16, className: 'inline-block align-middle' } ) }
                                    input={{
                                        type: 'number',
                                        min: index > 0 ? getMinimumLevelData( index - 1 ) : 1,
                                    }}
                                    value={ level.level_data }
                                    onChange={ ( value ) =>
                                        setLevelData( index, String( value ) )
                                    }
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
                    label={ <div className="flex items-center gap-[6px]">
                        <Plus size={ 16 } />
                        <span>{ __( 'Add Level', 'dokan' ) }</span>
                    </div> }
                    onClick={ addLevel }
                    disabled={ addNewButtonDisabled }
                    variant="secondary"
                    size="sm"
                />
            </div>
        </div>
    );
}
