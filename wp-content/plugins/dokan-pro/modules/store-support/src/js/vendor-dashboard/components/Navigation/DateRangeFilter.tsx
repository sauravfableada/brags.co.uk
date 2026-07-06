import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { dateI18n, getSettings, type Moment } from '@wordpress/date';

// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { DateRangePicker } from '@dokan/components';
import { DateRangeFilterProps } from '../../../types/store-support';

export default function DateRangeFilter( {
    startDate = null,
    endDate = null,
    loadFilters,
    onChange,
    onLoadComplete,
}: DateRangeFilterProps ) {
    const [ isOpen, setIsOpen ] = useState< boolean >( false );
    const [ after, setAfter ] = useState< Moment >( startDate );
    const [ afterText, setAfterText ] = useState< string >(
        startDate ? dateI18n( getSettings().formats.date, startDate ) : ''
    );
    const [ before, setBefore ] = useState< Moment >( endDate );
    const [ beforeText, setBeforeText ] = useState< string >(
        endDate ? dateI18n( getSettings().formats.date, endDate ) : ''
    );
    const [ focusedInput, setFocusedInput ] = useState<
        'startDate' | 'endDate' | null
    >( 'startDate' );

    // Initialize date texts when component mounts or dates change from props
    useEffect( () => {
        if ( startDate ) {
            setAfter( startDate );
            setAfterText( dateI18n( getSettings().formats.date, startDate ) );
        }

        if ( endDate ) {
            setBefore( endDate );
            setBeforeText( dateI18n( getSettings().formats.date, endDate ) );
        }

        if ( ! startDate && ! endDate ) {
            onChange( startDate, endDate );
            setAfter( null );
            setAfterText( '' );
        }
    }, [ startDate, endDate ] );

    useEffect( () => {
        if ( loadFilters ) {
            onLoadComplete();
        }
    }, [ loadFilters, onLoadComplete ] );

    // Handle date range updates
    const handleDateUpdate = ( update: {
        after?: Moment;
        afterText?: string;
        before?: Moment;
        beforeText?: string;
        focusedInput?: 'startDate' | 'endDate' | null;
    } ) => {
        if ( update.after !== undefined ) {
            setAfter( update.after );
        }
        if ( update.afterText !== undefined ) {
            setAfterText( update.afterText );
        }
        if ( update.before !== undefined ) {
            setBefore( update.before );
        }
        if ( update.beforeText !== undefined ) {
            setBeforeText( update.beforeText );
        }

        if ( update.focusedInput !== undefined ) {
            setFocusedInput( update.focusedInput );

            // Close the picker when focusedInput is not endDate
            if ( update.focusedInput !== 'endDate' ) {
                setIsOpen( false );
            }

            // Keep the date picker open for end date selection
            if ( update.focusedInput === 'endDate' && update.after ) {
                setIsOpen( true );
            }
        }

        // Only call onChange when we have both dates
        if ( update.after && update.before ) {
            onChange( update.after, update.before );
        }
    };

    return (
        <div className="dokan-support-ticket-date-range-filter">
            <DateRangePicker
                show={ isOpen }
                setShow={ setIsOpen }
                after={ after }
                afterText={ afterText }
                before={ before }
                beforeText={ beforeText }
                focusedInput={ focusedInput }
                onUpdate={ handleDateUpdate }
                shortDateFormat="MM/DD/YYYY"
            >
                <div className="dokan-layout w-96">
                    <span
                        className={ `components-button woocommerce-dropdown-button is-multi-line p-2.5 h-full !text-dokan-btn-secondary-text !border-dokan-btn-secondary-border !ring-dokan-btn-secondary-border after:bg-dokan-btn focus:!shadow-none focus-visible:!border-dokan-btn-secondary-border` }
                    >
                        <span className="woocommerce-dropdown-button__label font-semibold">
                            { after && before
                                ? `${ dateI18n(
                                      getSettings().formats.date,
                                      after
                                  ) } - ${ dateI18n(
                                      getSettings().formats.date,
                                      before
                                  ) }`
                                : __( 'Select date range', 'dokan' ) }
                        </span>
                    </span>
                </div>
            </DateRangePicker>
        </div>
    );
}
