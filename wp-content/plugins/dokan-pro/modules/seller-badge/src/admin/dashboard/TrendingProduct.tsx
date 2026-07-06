import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { BadgeEvent } from './types';
import { Plus } from 'lucide-react';
import { DebouncedInput, Select } from '@dokan/components';
import { getInputGroupIconElement } from './icons';

interface BadgeLevel {
    id: number;
    level_condition: string;
    level_data: string;
}

interface TrendingProductProps {
    event: BadgeEvent;
    levels: BadgeLevel[];
    onLevelsChange: ( levels: BadgeLevel[] ) => void;
}

const getDefaultBadgeLevel = (): BadgeLevel => ( {
    id: 0,
    level_condition: 'week',
    level_data: '5',
} );

export default function TrendingProduct( {
    event,
    levels,
    onLevelsChange,
}: TrendingProductProps ) {
    const [ badgeLevels, setBadgeLevels ] = useState< BadgeLevel[] >( () => {
        if ( ! levels || levels.length === 0 ) {
            return [ getDefaultBadgeLevel() ];
        }
        return levels.map( ( lvl ) => ( {
            ...lvl,
            level_data: String( ( lvl as any ).level_data ?? '' ),
        } ) );
    } );

    const levenOptions = [
        {
            value: 'week',
            label: __( 'Weekly', 'dokan' ),
        },
        {
            value: 'month',
            label: __( 'Monthly', 'dokan' ),
        },
    ];

    const getOptionValue = ( option: any ) => {
        return levenOptions.find( ( opt ) => opt.value === option );
    };

    // Sync levels with parent
    useEffect( () => {
        onLevelsChange( [ badgeLevels[ 0 ] ] );
    }, [ badgeLevels, onLevelsChange ] );

    const setLevelCondition = ( value: string ) => {
        const newLevels = [ ...badgeLevels ];
        newLevels[ 0 ].level_condition = value;
        setBadgeLevels( newLevels );
    };

    const setLevelData = ( value: string ) => {
        const currentValue = parseInt( value );
        const newLevels = [ ...badgeLevels ];
        if ( currentValue < 1 || isNaN( currentValue ) ) {
            newLevels[ 0 ].level_data = '1';
            setBadgeLevels( newLevels );
            return;
        }
        newLevels[ 0 ].level_data = String( currentValue );
        setBadgeLevels( newLevels );
    };

    return (
        <div className="dokan-logical-container">
            <ul className="list-none p-0 m-0">
                <li className="dokan-logical-label-li">
                    <div className="level-condition flex items-center gap-2.5 text-sm">
                        { __( 'Announce this badge', 'dokan' ) }
                        <div className="input-group flex items-stretch">
                            <Select
                                options={ levenOptions }
                                icon={ getInputGroupIconElement(
                                    event,
                                    'condition',
                                    {
                                        width: 16,
                                        height: 16,
                                        className: 'inline-block align-middle',
                                    }
                                ) }
                                value={ getOptionValue(
                                    badgeLevels[ 0 ].level_condition
                                ) }
                                onChange={ ( option ) => {
                                    setLevelCondition( option?.value || '' );
                                } }
                                className="!min-w-[150px] dokan-select-addon"
                                menuPortalTarget={ document.body }
                            />
                        </div>
                        <DebouncedInput
                            addOnLeft={ getInputGroupIconElement(
                                event,
                                'data',
                                {
                                    width: 16,
                                    height: 16,
                                    className: 'inline-block align-middle',
                                }
                            ) }
                            input={ {
                                type: 'number',
                                min: '10',
                                step: '1',
                            } }
                            value={ badgeLevels[ 0 ].level_data }
                            onChange={ ( e ) => setLevelData( e ) }
                            className="!min-w-[150px]"
                        />
                        <span className="break-words">
                            { __(
                                'products based on the top-selling items',
                                'dokan'
                            ) }
                        </span>
                    </div>
                </li>
            </ul>
        </div>
    );
}
