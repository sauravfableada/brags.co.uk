import { sprintf, __ } from '@wordpress/i18n';
import { dateI18n, getSettings } from '@wordpress/date';

/**
 * Format a start/end date pair as a human-readable range string.
 */
export const displayDateRange = ( startDate: string, endDate: string ) =>
	sprintf(
		/* translators: 1: start date, 2: end date */
		__( '%1$s - %2$s', 'dokan' ),
		dateI18n( getSettings().formats.date, startDate ),
		dateI18n( getSettings().formats.date, endDate )
	);
