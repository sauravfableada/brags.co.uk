import { useEffect, useState } from '@wordpress/element';
import { BadgeEvent } from './types';

interface BadgeLevel {
    id: number;
    level_condition: string;
    level_data: string | number;
}

interface NoLevelsProps {
    event: BadgeEvent;
    levels: BadgeLevel[];
    onLevelsChange: ( levels: BadgeLevel[] ) => void;
}

const getDefaultBadgeLevel = (): BadgeLevel => ( {
    id: 0,
    level_condition: '',
    level_data: 0,
} );

export default function NoLevels( {
    event,
    levels,
    onLevelsChange,
}: NoLevelsProps ) {
    const [ badgeLevels ] = useState< BadgeLevel[] >( () => {
        if ( ! levels || levels.length === 0 ) {
            return [ getDefaultBadgeLevel() ];
        }
        return levels;
    } );

    // Sync levels with parent
    useEffect( () => {
        onLevelsChange( badgeLevels );
    }, [ badgeLevels, onLevelsChange ] );

    return (
        <div className="dokan-logical-container">
            <ul className="list-none p-0 m-0">
                <li className="dokan-logical-label-li">
                    <div className="level-condition flex items-center gap-2.5 text-sm">
                        <span>{ event.condition_text?.prefix }</span>
                    </div>
                </li>
            </ul>
        </div>
    );
}
