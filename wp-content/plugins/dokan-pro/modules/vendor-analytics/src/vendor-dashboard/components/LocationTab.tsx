import {
    useEffect,
    useRef,
    useState,
    useCallback,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import {
    DataViews,
    // @ts-ignore
    // eslint-disable-next-line import/no-unresolved
} from '@dokan/components';

import { useAnalytics } from '../hooks/useAnalytics';
import { useDateRangeFilter } from '../hooks/useDateRangeFilter';
import type {
    LocationResponse,
    AnalyticsRow,
} from '../types/analytics';

declare global {
    interface Window {
        echarts: any;
    }
}

export default function LocationTab() {
    const mapRef = useRef< HTMLDivElement >( null );
    const mapInstanceRef = useRef< any >( null );

    const [ view, setView ] = useState( {
        type: 'table',
        perPage: 10,
        page: 1,
        search: '',
        layout: {
            styles: {
                city: { width: '18%' },
                country: { width: '18%' },
                activeUsers: { width: '16%' },
                screenPageViews: { width: '16%' },
                averageSessionDuration: { width: '16%' },
                bounceRate: { width: '16%' },
            },
        },
    } );

    const resetPage = useCallback( () => {
        setView( ( prev ) => ( { ...prev, page: 1 } ) );
    }, [] );

    const [ appliedStartDate, setAppliedStartDate ] = useState( '' );
    const [ appliedEndDate, setAppliedEndDate ] = useState( '' );

    const { data, isLoading, totalItems, totalPages } =
        useAnalytics( {
            tab: 'geographic',
            startDate: appliedStartDate,
            endDate: appliedEndDate,
            page: view.page,
            perPage: view.perPage,
        } );

    const {
        dateFilterConfig,
        clearDateFilter,
    } = useDateRangeFilter( {
        pickerKey: 'location-date-range',
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

    const locationData = data as LocationResponse | null;

    // Initialize and update map.
    useEffect( () => {
        if (
            ! mapRef.current ||
            ! window.echarts ||
            ! locationData?.map_data
        ) {
            return;
        }

        if ( ! mapInstanceRef.current ) {
            mapInstanceRef.current = window.echarts.init( mapRef.current );
        }

        const mapData = locationData.map_data;
        const chartData: { name: string; value: number }[] = [];
        let max = 0;

        for ( const country in mapData ) {
            const users = mapData[ country ];
            if ( users > max ) {
                max = users;
            }
            chartData.push( { name: country, value: users } );
        }

        const option = {
            tooltip: {
                trigger: 'item',
                backgroundColor: '#ffffff',
                borderWidth: 1,
                borderColor: '#eaeaea',
                textStyle: {
                    color: '#444444',
                    fontSize: 12,
                },
                formatter( params: any ) {
                    if ( ! params.name ) {
                        return null;
                    }
                    return (
                        '<strong>' +
                        params.name +
                        '</strong><br /> ' +
                        __( 'Users', 'dokan' ) +
                        ': <strong>' +
                        ( params.value || 0 ) +
                        '</strong>'
                    );
                },
            },
            visualMap: {
                min: 0,
                max: max || 1,
                text: [ __( 'High', 'dokan' ), __( 'Low', 'dokan' ) ],
                realtime: true,
                calculable: true,
                inRange: {
                    color: [ '#eeeeee', '#9be7ff', '#002f6c' ],
                },
            },
            series: [
                {
                    type: 'map',
                    mapType: 'world',
                    roam: false,
                    data: chartData,
                    emphasis: {
                        label: {
                            show: false,
                        },
                    },
                    itemStyle: {
                        areaColor: '#f3f3f3',
                        borderColor: '#cacaca',
                    },
                },
            ],
        };

        mapInstanceRef.current.setOption( option, true );

        const handleResize = () => mapInstanceRef.current?.resize();
        window.addEventListener( 'resize', handleResize );

        return () => {
            window.removeEventListener( 'resize', handleResize );
        };
    }, [ locationData ] );

    // Cleanup map on unmount.
    useEffect( () => {
        return () => {
            mapInstanceRef.current?.dispose();
            mapInstanceRef.current = null;
        };
    }, [] );

    const rows: AnalyticsRow[] = locationData?.rows ?? [];

    const fields = [
        {
            id: 'city',
            label: __( 'City', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: AnalyticsRow } ) => (
                <span>{ item.city ?? '' }</span>
            ),
        },
        {
            id: 'country',
            label: __( 'Country', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: AnalyticsRow } ) => (
                <span>{ item.country ?? '' }</span>
            ),
        },
        {
            id: 'activeUsers',
            label: __( 'Users', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: AnalyticsRow } ) => (
                <span>{ item.activeUsers ?? '' }</span>
            ),
        },
        {
            id: 'screenPageViews',
            label: __( 'Page Views', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: AnalyticsRow } ) => (
                <span>{ item.screenPageViews ?? '' }</span>
            ),
        },
        {
            id: 'averageSessionDuration',
            label: __( 'Avg Time', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: AnalyticsRow } ) => (
                <span>{ item.averageSessionDuration ?? '' }</span>
            ),
        },
        {
            id: 'bounceRate',
            label: __( 'Bounce Rate', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: AnalyticsRow } ) => (
                <span>{ item.bounceRate ?? '' }</span>
            ),
        },
    ];

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
        <div>
            { /* Map Section */ }
            <div className="mb-6">
                { isLoading ? (
                    <div className="h-[340px] bg-gray-100 rounded animate-pulse" />
                ) : ! locationData?.map_data ||
                  Object.keys( locationData.map_data ).length === 0 ? (
                    <div className="h-[340px] flex items-center justify-center text-gray-500 bg-gray-50 rounded">
                        { __( 'No location data available.', 'dokan' ) }
                    </div>
                ) : (
                    <div
                        ref={ mapRef }
                        style={ { width: '100%', height: '340px' } }
                    />
                ) }
            </div>

            { /* Table Section */ }
            <DataViews
                namespace="dokan-analytics-location-data-view"
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
        </div>
    );
}
