import { getDate, dateI18n, getSettings } from '@wordpress/date';
import { debounce } from '@wordpress/compose';
import { DateType } from '../Types';
import { useEffect, useState } from '@wordpress/element';
import useWordPressTimeFormat from '../../../../src/Hooks/useWordPressTimeFormat';
import { WpDatePicker } from '@dokan/components';
import { __ } from '@wordpress/i18n';
import { SimpleInput } from '@getdokan/dokan-ui';
type PropType = {
    dateType: DateType;
    setSchedulerDateType: ( data: object ) => void;
};
function ScheduleItem( { dateType, setSchedulerDateType }: PropType ) {
    const { is24Hour } = useWordPressTimeFormat();

    const [ hourAndMinutes, setSetHourAndMinutes ] = useState( {
        hour: dateType?.date_site
            ? // @ts-ignore
              getDate( new Date( dateType?.date_site ) ).getHours()
            : '',
        minutes: dateType?.date_site
            ? // @ts-ignore
              getDate( new Date( dateType?.date_site ) ).getMinutes()
            : '',
    } );

    const updateHourTime = ( hourOrTime, type ) => {
        const updatedDate = new Date( dateType.date_site );
        hourOrTime = Number( hourOrTime );

        if (
            'hour' === type &&
            ! isNaN( hourOrTime ) &&
            ! isNaN( updatedDate.getHours() ) &&
            hourOrTime >= 0 &&
            ( ( is24Hour && hourOrTime <= 24 ) ||
                ( ! is24Hour && hourOrTime <= 12 ) )
        ) {
            updatedDate.setHours( hourOrTime );
        }

        if (
            dateType.date_site &&
            'minutes' === type &&
            ! isNaN( hourOrTime ) &&
            ! isNaN( updatedDate.getMinutes() ) &&
            hourOrTime >= 0 &&
            hourOrTime <= 60
        ) {
            updatedDate.setMinutes( hourOrTime );
        }

        if ( isNaN( updatedDate.getDate() ) ) {
            setSetHourAndMinutes( () => {
                return {
                    hour: '',
                    minutes: '',
                };
            } );

            updateDate( '', '', '' );
        } else {
            setSetHourAndMinutes( () => {
                return {
                    hour: updatedDate.getHours(),
                    minutes: updatedDate.getMinutes(),
                };
            } );

            updateDate(
                updatedDate,
                updatedDate.getHours(),
                updatedDate.getMinutes()
            );
        }
    };

    const debouncedHourTime = debounce( updateHourTime, 500 );

    const updateDate = ( date: any, hour, minutes ) => {
        const updatedDate = new Date( date );
        updatedDate.setHours( Number( hour ) );
        updatedDate.setMinutes( Number( minutes ) );

        // Return in site's timezone with WordPress format
        setSchedulerDateType( {
            date: isNaN( updatedDate.getDate() ) ? '' : updatedDate.toString(),
            dateType,
        } );
    };

    useEffect( () => {
        if ( ! dateType?.date_site ) {
            return;
        }

        const updatedDate = new Date( dateType?.date_site );

        if (
            Number( updatedDate.getHours() ) ===
                Number( hourAndMinutes.hour ) &&
            Number( updatedDate.getMinutes() ) ===
                Number( hourAndMinutes.minutes )
        ) {
            return;
        }

        updatedDate.setHours( hourAndMinutes.hour );
        updatedDate.setMinutes( hourAndMinutes.minutes );

        updateDate(
            updatedDate.toString(),
            hourAndMinutes.hour,
            hourAndMinutes.minutes
        );
    }, [ dateType ] );

    return (
        <div className="space-y-2">
            { dateType?.can_date_be_updated ? (
                <div className="space-y-1">
                    { /* eslint-disable-next-line jsx-a11y/label-has-associated-control */ }
                    <span className="text-sm text-gray-500">
                        <strong>{ dateType.date_label }:</strong>
                    </span>
                    <div className="flex flex-col">
                        <WpDatePicker
                            onChange={ ( date ) =>
                                updateDate(
                                    date,
                                    hourAndMinutes.hour,
                                    hourAndMinutes.minutes
                                )
                            }
                            currentDate={
                                dateType?.date_site
                                    ? new Date( dateType?.date_site )
                                    : new Date()
                            }
                        >
                            <SimpleInput
                                className="bg-transparent"
                                onChange={ () => {} }
                                value={
                                    dateType?.date_site
                                        ? // ? new Date( dateType?.date_site )
                                          dateI18n(
                                              getSettings().formats.date,
                                              dateType?.date_site,
                                              getSettings().timezone.string
                                          )
                                        : ''
                                }
                                input={ {
                                    id: `dokan-schedule-date-input-item-${ dateType?.internal_date_key }`,
                                    name: `dokan_schedule_date_input_item_${ dateType?.internal_date_key }`,
                                    type: 'text',
                                    autoComplete: 'off',
                                    placeholder: __( 'Enter Date', 'dokan' ),
                                    required: false,
                                } }
                            />
                        </WpDatePicker>

                        <span className="flex justify-center">@</span>
                        <div className="flex">
                            <SimpleInput
                                className="bg-transparent"
                                onChange={ ( e ) => {
                                    debouncedHourTime( e.target.value, 'hour' );
                                } }
                                defaultValue={ hourAndMinutes.hour }
                                input={ {
                                    id: `dokan-schedule-date-input-item-hour-${ dateType?.internal_date_key }`,
                                    name: `dokan_schedule_date_input_item_hour_${ dateType?.internal_date_key }`,
                                    type: 'text',
                                    autoComplete: 'off',
                                    placeholder: __( 'HH', 'dokan' ),
                                    required: false,
                                } }
                            />
                            <span className="pl-1 pr-1 flex items-center">
                                :
                            </span>
                            <SimpleInput
                                className="bg-transparent"
                                onChange={ ( e ) => {
                                    debouncedHourTime(
                                        e.target.value,
                                        'minutes'
                                    );
                                } }
                                defaultValue={ hourAndMinutes.minutes }
                                input={ {
                                    id: `dokan-schedule-date-input-item-minutes-${ dateType?.internal_date_key }`,
                                    name: `dokan_schedule_date_input_item_minutes_${ dateType?.internal_date_key }`,
                                    type: 'text',
                                    autoComplete: 'off',
                                    placeholder: __( 'MM', 'dokan' ),
                                    required: false,
                                } }
                            />
                        </div>
                    </div>
                </div>
            ) : (
                <div className="space-y-1 flex">
                    <span className="text-sm text-gray-500"><strong>{ dateType?.date_label }:</strong></span>
                    &nbsp;
                    <span className="text-gray-500 text-sm !m-0">
                        <div>{ dateType?.get_date_to_display }</div>
                    </span>
                </div>
            ) }
        </div>
    );
}

export default ScheduleItem;
