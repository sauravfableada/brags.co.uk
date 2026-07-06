export type TabKey =
    | 'general'
    | 'pages'
    | 'geographic'
    | 'system'
    | 'promotions'
    | 'keyword';

export interface AnalyticsHeader {
    key: string;
    label: string;
    type: 'dimension' | 'metric';
}

export interface AnalyticsRow {
    [ key: string ]: string | number;
}

export interface GeneralSummary {
    key: string;
    label: string;
    value: string;
}

export interface ChartDataPoint {
    date: string;
    users: number;
    sessions: number;
}

export interface TableResponse {
    headers: AnalyticsHeader[];
    rows: AnalyticsRow[];
}

export interface GeneralResponse {
    summary: GeneralSummary[];
    chart: ChartDataPoint[];
}

export interface LocationResponse extends TableResponse {
    map_data: Record< string, number >;
}

export type ApiFetchResponse = {
    json: () => Promise< any >;
    headers: Response[ 'headers' ] & {
        get: ( key: 'X-WP-Total' | 'X-WP-TotalPages' ) => string | null;
    };
};
