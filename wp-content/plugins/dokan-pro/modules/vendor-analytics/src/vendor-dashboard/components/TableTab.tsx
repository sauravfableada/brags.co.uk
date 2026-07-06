import { useRef, useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { DataViews } from '@dokan/components';

import { useAnalytics } from '../hooks/useAnalytics';
import { useDateRangeFilter } from '../hooks/useDateRangeFilter';
import type { TabKey, AnalyticsRow } from '../types/analytics';

interface TableTabProps {
    tab: TabKey;
}

/**
 * Static field definitions per tab so DataViews always has columns for skeleton rendering.
 */
const TAB_FIELDS: Record<
    string,
    { id: string; label: string; width: string }[]
> = {
    pages: [
        {
            id: 'pageTitle',
            label: __( 'Page Title', 'dokan' ),
            width: '30%',
        },
        {
            id: 'pagePath',
            label: __( 'Page Path', 'dokan' ),
            width: '25%',
        },
        {
            id: 'screenPageViews',
            label: __( 'Page Views', 'dokan' ),
            width: '15%',
        },
        {
            id: 'averageSessionDuration',
            label: __( 'Avg Time', 'dokan' ),
            width: '15%',
        },
        {
            id: 'bounceRate',
            label: __( 'Bounce Rate', 'dokan' ),
            width: '15%',
        },
    ],
    system: [
        {
            id: 'browser',
            label: __( 'Browser', 'dokan' ),
            width: '25%',
        },
        {
            id: 'operatingSystem',
            label: __( 'Operating System', 'dokan' ),
            width: '30%',
        },
        {
            id: 'operatingSystemVersion',
            label: __( 'OS Version', 'dokan' ),
            width: '25%',
        },
        {
            id: 'screenPageViews',
            label: __( 'Sessions', 'dokan' ),
            width: '20%',
        },
    ],
    promotions: [
        {
            id: 'source',
            label: __( 'Source', 'dokan' ),
            width: '25%',
        },
        {
            id: 'medium',
            label: __( 'Medium', 'dokan' ),
            width: '25%',
        },
        {
            id: 'sourcePlatform',
            label: __( 'Source Platform', 'dokan' ),
            width: '30%',
        },
        {
            id: 'sessions',
            label: __( 'Sessions', 'dokan' ),
            width: '20%',
        },
    ],
    keyword: [
        {
            id: 'googleAdsKeyword',
            label: __( 'Keyword', 'dokan' ),
            width: '60%',
        },
        {
            id: 'sessions',
            label: __( 'Sessions', 'dokan' ),
            width: '40%',
        },
    ],
};

const getFieldsForTab = ( tab: TabKey ) => {
    const defs = TAB_FIELDS[ tab ] ?? [];
    return defs.map( ( def ) => ( {
        id: def.id,
        label: def.label,
        render: ( { item }: { item: AnalyticsRow } ) => (
            <span>{ item[ def.id ] ?? '' }</span>
        ),
    } ) );
};

const getLayoutStylesForTab = ( tab: TabKey ) => {
    const defs = TAB_FIELDS[ tab ] ?? [];
    return defs.reduce< Record< string, { width: string } > >(
        ( acc, def ) => {
            acc[ def.id ] = { width: def.width };
            return acc;
        },
        {}
    );
};

export default function TableTab( { tab }: TableTabProps ) {
    const [ view, setView ] = useState( {
        type: 'table',
        perPage: 10,
        page: 1,
        search: '',
        layout: {
            styles: getLayoutStylesForTab( tab ),
        },
    } );

    const resetPage = useCallback( () => {
        setView( ( prev ) => ( { ...prev, page: 1 } ) );
    }, [] );

    const [ appliedStartDate, setAppliedStartDate ] = useState( '' );
    const [ appliedEndDate, setAppliedEndDate ] = useState( '' );

    // Reset page synchronously when tab changes to avoid a wasted fetch.
    const prevTabRef = useRef( tab );
    let currentPage = view.page;

    if ( prevTabRef.current !== tab ) {
        prevTabRef.current = tab;
        currentPage = 1;
        setView( ( prev ) => ( {
            ...prev,
            page: 1,
            layout: { styles: getLayoutStylesForTab( tab ) },
        } ) );
    }

    const { data, isLoading, totalItems, totalPages } = useAnalytics( {
        tab,
        startDate: appliedStartDate,
        endDate: appliedEndDate,
        page: currentPage,
        perPage: view.perPage,
    } );

    const {
        dateFilterConfig,
        clearDateFilter,
    } = useDateRangeFilter( {
        pickerKey: `${ tab }-date-range`,
        serverStartDate: data?.start_date ?? '',
        serverEndDate: data?.end_date ?? '',
        onDateChange: ( start?: string, end?: string ) => {
             resetPage();
             if ( start !== undefined && end !== undefined ) {
               setAppliedStartDate( start );
               setAppliedEndDate( end );
            }
        },
    } );

    const rows: AnalyticsRow[] = data?.rows ?? [];
    const fields = getFieldsForTab( tab );

    const onViewChange = useCallback( ( newView: typeof view ) => {
        setView( newView );
    }, [] );

    const filter = {
        fields: [ dateFilterConfig ],
        onReset: clearDateFilter,
        onFilterRemove: ( filterId: string ) => {
            if ( filterId === 'date-range' ) {
                clearDateFilter();
            }
        },
    };

    return (
        <DataViews
            namespace={ `dokan-analytics-${ tab }-data-view` }
            data={ rows }
            fields={ fields }
            view={ view }
            onChangeView={ onViewChange }
            getItemId={ ( _item: AnalyticsRow, index: number ) => index }
            isLoading={ isLoading }
            paginationInfo={ { totalItems, totalPages } }
            filter={ filter }
            search={ false }
        />
    );
}
