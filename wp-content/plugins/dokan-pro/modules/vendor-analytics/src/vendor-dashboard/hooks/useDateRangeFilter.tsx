import { useState, useCallback, useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { dateI18n, getDate, getSettings } from '@wordpress/date';
import { Calendar } from 'lucide-react';
import { SimpleInput } from '@getdokan/dokan-ui';

// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { DateRangePicker } from '@dokan/components';

interface UseDateRangeFilterArgs {
    pickerKey: string;
    onDateChange?: ( start?: string, end?: string ) => void;
    serverStartDate?: string;
    serverEndDate?: string;
}

interface UseDateRangeFilterReturn {
    appliedStartDate: string;
    appliedEndDate: string;
    dateFilterConfig: {
        id: string;
        label: string;
        field: JSX.Element;
    };
    clearDateFilter: () => void;
}

const formatDateRange = ( start: string, end: string ) =>
    sprintf(
        /* translators: 1: start date, 2: end date */
        __( '%1$s - %2$s', 'dokan' ),
        dateI18n( getSettings().formats.date, getDate( start ) ),
        dateI18n( getSettings().formats.date, getDate( end ) )
    );

export const useDateRangeFilter = ( {
    pickerKey,
    onDateChange,
    serverStartDate = '',
    serverEndDate = '',
}: UseDateRangeFilterArgs ): UseDateRangeFilterReturn => {
    const [ after, setAfter ] = useState( '' );
    const [ afterText, setAfterText ] = useState( '' );
    const [ before, setBefore ] = useState( '' );
    const [ beforeText, setBeforeText ] = useState( '' );
    const [ focusedInput, setFocusedInput ] = useState( 'startDate' );

    const [ appliedStartDate, setAppliedStartDate ] = useState( '' );
    const [ appliedEndDate, setAppliedEndDate ] = useState( '' );

    const applyDateFilter = useCallback( () => {
        const start = after ? dateI18n( 'Y-m-d', after ) : '';
        const end = before ? dateI18n( 'Y-m-d', before ) : '';
        setAppliedStartDate( start );
        setAppliedEndDate( end );
        if ( onDateChange ) {
            onDateChange( start, end );
        }
    }, [ after, before, onDateChange ] );

    const clearDateFilter = useCallback( () => {
        setAfter( '' );
        setAfterText( '' );
        setBefore( '' );
        setBeforeText( '' );
        setAppliedStartDate( '' );
        setAppliedEndDate( '' );
        if ( onDateChange ) {
            onDateChange( '', '' );
        }
    }, [ onDateChange ] );

    const dateFilterConfig = useMemo( () => ( {
        id: 'date-range',
        label: __( 'Date Range', 'dokan' ),
        field: (
            <DateRangePicker
                key={ pickerKey }
                after={ after }
                afterText={ afterText }
                before={ before }
                beforeText={ beforeText }
                onUpdate={ ( update: any ) => {
                    if ( update.after ) {
                        setAfter( update.after );
                    }
                    if ( update.afterText ) {
                        setAfterText( update.afterText );
                    }
                    if ( update.before ) {
                        setBefore( update.before );
                    }
                    if ( update.beforeText ) {
                        setBeforeText( update.beforeText );
                    }
                    if ( update.focusedInput ) {
                        setFocusedInput( update.focusedInput );
                        if (
                            update.focusedInput === 'endDate' &&
                            after
                        ) {
                            setBefore( '' );
                            setBeforeText( '' );
                        }
                    }
                } }
                shortDateFormat="MM/DD/YYYY"
                focusedInput={ focusedInput }
                isInvalidDate={ () => false }
                wrapperClassName="w-full"
                pickerToggleClassName="block"
                wpPopoverClassName="dokan-layout"
                onClear={ clearDateFilter }
                onOk={ applyDateFilter }
            >
                <SimpleInput
                    addOnLeft={ <Calendar size="16" /> }
                    className="border rounded px-3 py-1.5 w-full bg-white shadow-none"
                    onChange={ () => {} }
                    input={ {
                        type: 'text',
                        value:
                            after && before
                                ? formatDateRange( after, before )
                                : serverStartDate && serverEndDate
                                    ? formatDateRange( serverStartDate, serverEndDate )
                                    : '',
                        placeholder: __( 'Select Date Range', 'dokan' ),
                        readOnly: true,
                    } }
                />
            </DateRangePicker>
        ),
    } ), [
        pickerKey,
        after,
        afterText,
        before,
        beforeText,
        focusedInput,
        serverStartDate,
        serverEndDate,
        clearDateFilter,
        applyDateFilter,
    ] );

    return {
        appliedStartDate,
        appliedEndDate,
        dateFilterConfig,
        clearDateFilter,
    };
};
