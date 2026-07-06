import FullCalendar from '@fullcalendar/react';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import globalLocales from '@fullcalendar/core/locales-all';
import {
    useEffect,
    useState,
    useCallback,
    useRef,
    RawHTML,
} from '@wordpress/element';
import { Fill } from '@wordpress/components';
import { SearchableSelect } from '@getdokan/dokan-ui';
import {
    DokanTooltip as Tooltip,
    // @ts-ignore
    // eslint-disable-next-line import/no-unresolved
} from '@dokan/components';
import { __, _n, sprintf } from '@wordpress/i18n';
import { format } from '@wordpress/date';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import type { CalendarEvent, CalendarFilterGroup } from '../types';
import { decodeEntities } from '@wordpress/html-entities';
import { decodeEntity } from 'html-entities';

interface FilterOption {
    label: string;
    value: string;
}

interface FilterGroup {
    label: string;
    options: FilterOption[];
}

const BookingCalendarPage = () => {
    const [ local, setLocal ] = useState( 'en' );
    const [ loading, setLoading ] = useState( false );
    const [ events, setEvents ] = useState< CalendarEvent[] >( [] );
    const [ currentRange, setCurrentRange ] = useState< {
        start?: Date;
        end?: Date;
    } >( {} );
    const calendarRef = useRef< FullCalendar >( null );

    const [ filterGroups, setFilterGroups ] = useState< FilterGroup[] >( [] );
    const [ selectedFilter, setSelectedFilter ] = useState< FilterOption >( {
        label: __( 'Filter Bookings', 'dokan' ),
        value: '',
    } );

    // Build grouped options for react-select.
    const groupedOptions: ( FilterOption | FilterGroup )[] = [
        { label: __( 'Filter Bookings', 'dokan' ), value: '' },
        ...filterGroups.map( ( group ) => ( {
            label: group.label,
            options: group.options.map( ( opt ) => ( {
                label: '\u00A0\u00A0\u00A0' + opt.label,
                value: String( opt.value ),
            } ) ),
        } ) ),
    ];

    useEffect( () => {
        apiFetch< CalendarFilterGroup[] >( {
            path: '/dokan/v1/booking/calendar-events/filters',
        } )
            .then( ( groups ) => {
                setFilterGroups(
                    groups.map( ( g ) => ( {
                        label: g.label,
                        options: g.options.map( ( o ) => ( {
                            label: o.label,
                            value: String( o.value ),
                        } ) ),
                    } ) )
                );
            } )
            .catch( () => {} );
    }, [] );

    const fetchEvents = useCallback( () => {
        if ( ! currentRange.start || ! currentRange.end ) {
            return;
        }

        const startDate = format( 'Y-m-d', currentRange.start );
        const endDate = format( 'Y-m-d', currentRange.end );
        setLoading( true );

        const queryParams: Record< string, string > = {
            start_date: startDate,
            end_date: endDate,
        };

        if ( selectedFilter.value ) {
            queryParams.filter_bookings = selectedFilter.value;
        }

        apiFetch< CalendarEvent[] >( {
            path: addQueryArgs(
                '/dokan/v1/booking/calendar-events',
                queryParams
            ),
        } )
            .then( ( responseEvents ) => {
                setEvents( responseEvents );
            } )
            .catch( () => {} )
            .finally( () => setLoading( false ) );
    }, [ currentRange, selectedFilter.value ] );

    // Locale detection.
    useEffect( () => {
        const foundLocal = globalLocales.find( ( currentLocal ) => {
            return (
                // @ts-ignore
                currentLocal.code === window.dokan_full_calendar_i18n?.code ||
                // @ts-ignore
                currentLocal.code === window.dokan_full_calendar_i18n?.code_1
            );
        } );
        setLocal( foundLocal?.code || 'en' );
    }, [] );

    useEffect( () => {
        fetchEvents();
    }, [ fetchEvents ] );

    return (
        <div className="dokan-booking-calendar-wrapper">
            <Fill name="dokan-header-actions">
                <div className="min-w-50">
                    <SearchableSelect
                        placeholder={ __( 'Filter Bookings', 'dokan' ) }
                        menuPortalTarget={
                            document.querySelector(
                                '.dokan-layout'
                            ) as HTMLElement
                        }
                        options={ groupedOptions }
                        value={ selectedFilter }
                        onChange={ ( value: any ) => {
                            if ( value ) {
                                setSelectedFilter( value );
                            } else {
                                setSelectedFilter( {
                                    label: __( 'Filter Bookings', 'dokan' ),
                                    value: '',
                                } );
                            }
                        } }
                        isClearable
                    />
                </div>
            </Fill>

            <div className="relative w-full h-full dokan-booking-dashboard-calendar">
                { loading && (
                    <div className="absolute inset-0 backdrop-blur-sm flex items-center justify-center z-50">
                        <p className="text-gray-600 font-medium">
                            { __( 'Loading events\u2026', 'dokan' ) }
                        </p>
                    </div>
                ) }
                <FullCalendar
                    ref={ calendarRef }
                    plugins={ [
                        dayGridPlugin,
                        timeGridPlugin,
                        interactionPlugin,
                        listPlugin,
                    ] }
                    initialView="dayGridMonth"
                    initialDate={ new Date() }
                    headerToolbar={ {
                        start: 'title',
                        center: '',
                        end: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek today prev,next',
                    } }
                    events={ events }
                    locales={ globalLocales }
                    locale={ local }
                    firstDay={
                        // @ts-ignore
                        window.dokan_helper?.week_starts_day || 0
                    }
                    dayMaxEvents={ 3 }
                    moreLinkClick="popover"
                    moreLinkContent={ ( args ) =>
                        sprintf(
                            // eslint-disable-next-line @wordpress/i18n-translator-comments
                            _n(
                                '+%d more item',
                                '+%d more items',
                                args.num,
                                'dokan'
                            ),
                            args.num
                        )
                    }
                    datesSet={ ( dateInfo ) => {
                        setCurrentRange( {
                            start: dateInfo.start,
                            end: dateInfo.end,
                        } );
                    } }
                    eventContent={ ( arg ) => {
                        const tooltip =
                            arg.event.extendedProps.info?.body ?? '';
                        return (
                            <Tooltip content={ <RawHTML>{ tooltip }</RawHTML> }>
                                <div className="fc-event-main-frame cursor-pointer">
                                    { arg.timeText && (
                                        <div className="fc-event-time">
                                            { arg.timeText }
                                        </div>
                                    ) }
                                    <div className="fc-event-title-container">
                                        <div className="fc-event-title fc-sticky">
                                            { arg.event.title }
                                        </div>
                                    </div>
                                </div>
                            </Tooltip>
                        );
                    } }
                />
            </div>
        </div>
    );
};

export default BookingCalendarPage;
