import { ChevronDown, Crown, Info } from 'lucide-react';
import { useRef, useState } from '@wordpress/element';
import { twMerge } from 'tailwind-merge';
import { __ } from '@wordpress/i18n';
import { Popover } from '@dokan/components';
import { SimpleInput, useToast } from '@getdokan/dokan-ui';
import { BadgeEvent } from './types';

type MapedEvents = {
    [ key: string ]: {
        group: {
            key: string;
            label: string;
        };
        events: BadgeEvent[];
    };
};

function BadgeEventInput( {
    events,
    value,
    onChange,
}: {
    events: BadgeEvent[];
    value?: BadgeEvent | null;
    onChange: ( ev: BadgeEvent ) => void;
} ) {
    const [ isOpen, setIsOpen ] = useState< boolean >( false );
    const [ popoverAnchor, setPopoverAnchor ] = useState();
    const contentRef = useRef< HTMLDivElement >( null );
    const toast = useToast();

    const mappedOnChange = ( ev: BadgeEvent ) => {
        if ( ev.created ) {
            return;
        }
        onChange?.( ev );
        setIsOpen( false );
    };

    const getEventsByGrouping = (): MapedEvents => {
        const mappedEvents = {};

        events.forEach( ( event ) => {
            if ( event.group.key && ! event.created ) {
                if ( ! mappedEvents[ event.group.key ] ) {
                    mappedEvents[ event.group.key ] = {
                        group: event.group,
                        events: [],
                    };
                }
                mappedEvents[ event.group.key ].events.push( event );
            }
        } );

        return mappedEvents;
    };

    const getSelectBody = () => {
        return (
            <div className="max-h-80 overflow-y-auto pr-1 dokan-seller-badge-scroll">
                { Object.values( getEventsByGrouping() ).map(
                    ( section: any ) => (
                        <div key={ section.group?.key } className="mb-3">
                            <div className="px-2 py-1 text-xs font-medium text-[#6B6B6B]">
                                { section.group?.title }
                            </div>
                            <ul
                                className="m-0 p-0 list-none"
                                role="listbox"
                                aria-label={ __( 'Badge events', 'dokan' ) }
                            >
                                { section.events.map( ( ev: any ) => (
                                    <li
                                        key={ ev.id }
                                        role="option"
                                        aria-selected={ value?.id === ev.id }
                                        tabIndex={ ev.created ? -1 : 0 }
                                        onKeyDown={ ( e ) => {
                                            if ( ev.created ) {
                                                return;
                                            }
                                            if (
                                                e.key === 'Enter' ||
                                                e.key === ' '
                                            ) {
                                                mappedOnChange?.( ev );
                                            }
                                        } }
                                        onClick={ () => {
                                            mappedOnChange?.( ev );
                                        } }
                                        aria-disabled={ ev.created }
                                        className={ twMerge(
                                            'flex items-start gap-3 px-3 py-2 rounded cursor-pointer',
                                            ! ev.created &&
                                                'hover:bg-[#F6F6F7]',
                                            ev.created &&
                                                'opacity-50 cursor-not-allowed',
                                            value?.id === ev.id &&
                                                'bg-[#F6F6F7]'
                                        ) }
                                    >
                                        <div className="w-8 h-8 rounded flex items-center justify-center overflow-hidden shrink-0">
                                            { ev.badge_logo_raw ||
                                            ev.badge_logo ? (
                                                // eslint-disable-next-line @next/next/no-img-element
                                                <img
                                                    src={
                                                        ev.badge_logo_raw ||
                                                        ev.badge_logo
                                                    }
                                                    alt=""
                                                    className="w-8 h-8 object-contain"
                                                />
                                            ) : (
                                                <Crown
                                                    size={ 18 }
                                                    color="#8B5CF6"
                                                />
                                            ) }
                                        </div>
                                        <div className="flex-1 min-w-0">
                                            <div className="text-sm font-medium text-[#25252D] truncate">
                                                { ev.title }
                                            </div>
                                            <div className="text-xs text-[#6B7280] whitespace-normal leading-5">
                                                { ev.description }
                                            </div>
                                        </div>
                                    </li>
                                ) ) }
                            </ul>
                        </div>
                    )
                ) }
            </div>
        );
    };

    const getEmptySelectBody = () => {
        return (
            <div className="max-h-80 overflow-y-auto pr-1 dokan-seller-badge-scroll">
                <span className="text-[#575757] font-[400] text-[12px]">
                    { __( 'All badges are created', 'dokan' ) }
                </span>
            </div>
        );
    };

    const openWhenClick = () => {
        setIsOpen( value?.created ? false : ! isOpen );
        if ( value?.created ) {
            toast( {
                title: __( 'You can not change event type.', 'dokan' ),
                type: 'warning',
            } );
        }
    };

    return (
        <div>
            { /* eslint-disable-next-line jsx-a11y/click-events-have-key-events,jsx-a11y/no-static-element-interactions */ }
            <div ref={ contentRef } onClick={ openWhenClick }>
                <SimpleInput
                    label={ __( 'Badge Event', 'dokan' ) }
                    value={ value?.title || '' }
                    input={ {
                        type: 'text',
                        readOnly: true,
                        placeholder: __( 'Select Badge Event', 'dokan' ),
                    } }
                    className="w-full dokan-seller-badge-event-input !cursor-pointer !text-[#25252D] read-only:!bg-white"
                    addOnLeft={
                        <Crown
                            size={ 20 }
                            color="#828282"
                            className="text-[#828282] dokan-seller-badge-event-input-crown"
                        />
                    }
                    addOnRight={
                        <ChevronDown
                            size={ 20 }
                            color="#828282"
                            className="text-[#828282] dokan-seller-badge-event-input-chevrond-down"
                        />
                    }
                    // @ts-ignore
                    ref={ setPopoverAnchor }
                    helpText={
                        value?.description ? (
                            <div className="flex items-start gap-2 mt-3 text-sm text-gray-600">
                                <Info
                                    size={ 16 }
                                    className="mt-0.5 shrink-0"
                                />
                                <p>{ value.description }</p>
                            </div>
                        ) : null
                    }
                />
            </div>

            { isOpen && (
                <Popover
                    anchor={ popoverAnchor }
                    focusOnMount={ true }
                    onClose={ () => setIsOpen( false ) }
                    onFocusOutside={ () => setIsOpen( false ) }
                    className={ twMerge(
                        'dokan-layout dokan-seller-badge-input-event-popover',
                        'bg-white'
                    ) }
                    style={ {
                        marginTop: value?.description ? '-20px' : '10px',
                        marginBottom: '10px',
                    } }
                >
                    <div
                        className="flex-auto overflow-hidden bg-white text-sm/6 z-[9999] p-3"
                        style={ {
                            width: contentRef?.current?.offsetWidth ?? 'auto',
                        } }
                    >
                        { Object.values( getEventsByGrouping() ).length > 0
                            ? getSelectBody()
                            : getEmptySelectBody() }
                    </div>
                </Popover>
            ) }
        </div>
    );
}

export default BadgeEventInput;
