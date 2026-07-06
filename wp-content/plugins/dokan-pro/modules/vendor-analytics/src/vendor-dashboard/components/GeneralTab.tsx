import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
    ChartLegend,
    ChartLegendContent,
    recharts,
} from '@wedevs/plugin-ui';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { ListEmpty } from '@dokan/components';

import { useAnalytics } from '../hooks/useAnalytics';
import { useDateRangeFilter } from '../hooks/useDateRangeFilter';
import StatsSidebar from './StatsSidebar';
import type { GeneralResponse, ChartDataPoint } from '../types/analytics';

const { AreaChart, Area, CartesianGrid, XAxis, YAxis } = recharts;

const chartConfig = {
    users: { label: __( 'Users', 'dokan' ), color: '#3498db' },
    sessions: { label: __( 'Sessions', 'dokan' ), color: '#1abc9c' },
};

const formatDateLabel = ( dateStr: string ) => {
    if ( dateStr.length === 8 ) {
        return `${ dateStr.slice( 4, 6 ) }/${ dateStr.slice( 6, 8 ) }`;
    }
    return dateStr;
};

export default function GeneralTab() {
    const [ appliedStartDate, setAppliedStartDate ] = useState( '' );
    const [ appliedEndDate, setAppliedEndDate ] = useState( '' );

    const { data, isLoading } = useAnalytics( {
        tab: 'general',
        startDate: appliedStartDate,
        endDate: appliedEndDate,
        page: 1,
        perPage: 100,
    } );

    const { dateFilterConfig } = useDateRangeFilter( {
        pickerKey: 'general-date-range',
        serverStartDate: data?.start_date ?? '',
        serverEndDate: data?.end_date ?? '',
        onDateChange: ( start?: string, end?: string ) => {
            if ( start !== undefined && end !== undefined ) {
                setAppliedStartDate( start );
                setAppliedEndDate( end );
            }
        },
    } );

    const generalData = data as GeneralResponse | null;

    const chartData = ( generalData?.chart ?? [] ).map(
        ( point: ChartDataPoint ) => ( {
            date: formatDateLabel( point.date ),
            users: point.users,
            sessions: point.sessions,
        } )
    );

    const hasSummary = ( generalData?.summary?.length ?? 0 ) > 0;
    const showSidebar = isLoading || hasSummary;

    return (
        <div>
            <div className="mb-4">
                <div className="w-64">
                    { dateFilterConfig.field }
                </div>
            </div>

            <div className="flex flex-col md:flex-row gap-6">
                { showSidebar && (
                    <div className="w-full md:w-1/4">
                        <StatsSidebar
                            summary={ generalData?.summary ?? [] }
                            isLoading={ isLoading }
                        />
                    </div>
                ) }
                <div className={ `w-full ${ showSidebar ? 'md:w-3/4' : '' }` }>
                    <div className="bg-white border border-solid border-gray-200 rounded p-4">
                        <h3 className="text-base font-semibold text-gray-800 mt-0 mb-4">
                            { __( 'Analytics', 'dokan' ) }
                        </h3>
                        { isLoading ? (
                            <div className="h-[350px] bg-gray-100 rounded animate-pulse" />
                        ) : ! chartData.length ? (
                            <ListEmpty />
                        ) : (
                            <ChartContainer
                                config={ chartConfig }
                                className="aspect-auto h-[400px] w-full [&_.recharts-wrapper]:overflow-visible"
                            >
                                <AreaChart
                                    data={ chartData }
                                    margin={ { top: 10, right: 30, left: 0, bottom: 5 } }
                                >
                                    <CartesianGrid vertical={ false } />
                                    <XAxis
                                        dataKey="date"
                                        tickLine={ false }
                                        axisLine={ false }
                                        tickMargin={ 8 }
                                    />
                                    <YAxis
                                        tickLine={ false }
                                        axisLine={ false }
                                        tickMargin={ 8 }
                                        allowDecimals={ false }
                                    />
                                    <ChartTooltip
                                        cursor={ false }
                                        content={ <ChartTooltipContent indicator="dot" /> }
                                    />
                                    <ChartLegend content={ <ChartLegendContent /> } />
                                    <Area
                                        dataKey="users"
                                        type="natural"
                                        fill="var(--color-users)"
                                        fillOpacity={ 0.4 }
                                        stroke="var(--color-users)"
                                    />
                                    <Area
                                        dataKey="sessions"
                                        type="natural"
                                        fill="var(--color-sessions)"
                                        fillOpacity={ 0.4 }
                                        stroke="var(--color-sessions)"
                                    />
                                </AreaChart>
                            </ChartContainer>
                        ) }
                    </div>
                </div>
            </div>
        </div>
    );
}
