import { useState, useCallback } from '@wordpress/element';
import { dateI18n } from '@wordpress/date';

interface FilterArgsWithDates {
	start_date: string;
	end_date: string;
	page: number;
}

/**
 * Shared hook that manages the date-range picker state and wires it to a
 * filterArgs setter.  Used by both AuctionList and AuctionActivity.
 */
export function useDateRangeFilter< T extends FilterArgsWithDates >(
	setFilterArgs: ( updater: ( prev: T ) => T ) => void
) {
	const [ after, setAfter ] = useState( '' );
	const [ afterText, setAfterText ] = useState( '' );
	const [ before, setBefore ] = useState( '' );
	const [ beforeText, setBeforeText ] = useState( '' );
	const [ focusedInput, setFocusedInput ] = useState( 'startDate' );

	const applyDateFilter = useCallback( () => {
		setFilterArgs( ( prev ) => ( {
			...prev,
			start_date: after ? dateI18n( 'Y-m-d', after ) : '',
			end_date: before ? dateI18n( 'Y-m-d', before ) : '',
			page: 1,
		} ) );
	// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ after, before ] );

	const clearDateFilter = useCallback( () => {
		setAfter( '' );
		setAfterText( '' );
		setBefore( '' );
		setBeforeText( '' );
		setFilterArgs( ( prev ) => ( {
			...prev,
			start_date: '',
			end_date: '',
			page: 1,
		} ) );
	// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const onPickerUpdate = useCallback( ( update: Record< string, any > ) => {
		if ( update.after ) setAfter( update.after );
		if ( update.afterText ) setAfterText( update.afterText );
		if ( update.before ) setBefore( update.before );
		if ( update.beforeText ) setBeforeText( update.beforeText );
		if ( update.focusedInput ) {
			setFocusedInput( update.focusedInput );
			if ( update.focusedInput === 'endDate' && after ) {
				setBefore( '' );
				setBeforeText( '' );
			}
		}
	// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ after ] );

	return {
		after,
		afterText,
		before,
		beforeText,
		focusedInput,
		applyDateFilter,
		clearDateFilter,
		onPickerUpdate,
	};
}
