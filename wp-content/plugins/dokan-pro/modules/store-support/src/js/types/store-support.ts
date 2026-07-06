import { Moment } from '@wordpress/date';

export interface SupportTicket {
    id: number;
    title: string;
    content: string;
    status: 'open' | 'closed';
    date_created: string;
    date_modified: string;
    date_formatted: string;
    customer: {
        id: number;
        name: string;
        email: string;
        avatar: string;
    };
    order?: {
        id: number;
        number: string;
        status: string;
        total: string;
        url?: string;
    };
    vendor_id: number;
    replies_count: number;
    topic_url: string;
    replies: SupportTicketReply[];
}

export interface SupportTicketReply {
    id: number;
    content: string;
    date: string;
    date_formatted: string;
    author: {
        id: number;
        name: string;
        type: 'customer' | 'vendor' | 'admin';
        avatar: string;
        site_name?: string;
    };
    human_time_diff: string;
}

export interface SupportTicketStats {
    open: number;
    closed: number;
    total: number;
}

export interface SupportStatus {
    key: string;
    label: string;
    count: number;
}

export type SupportTicketStatus = 'open' | 'closed' | 'all';

export interface CreateReplyParams {
    message: string;
    close_ticket?: boolean;
}

export interface ExportParams {
    format: 'csv' | 'json';
    status: SupportTicketStatus;
}

export interface BatchUpdateParams {
    open?: number[];
    close?: number[];
}

export interface SupportTicketsQueryParams {
    status?: SupportTicketStatus;
    customer_id?: number;
    search?: string;
    start_date?: string;
    end_date?: string;
    page?: number;
    per_page?: number;
    orderby?: 'date' | 'id' | 'title' | 'status';
    order?: 'asc' | 'desc';
}

export interface CsvExportResponse {
    filename: string;
    content: string;
}

export type TicketsListProps = {
    navigate: ( path: any, options?: any ) => void;
};

export type FilterState = {
    page: number;
    per_page: number;
    status: SupportTicketStatus;
    customer_id?: number | null;
    start_date?: string | null;
    end_date?: string | null;
    ticket_keyword?: string | null;
};

export type DateRangeFilterProps = {
    startDate?: Moment | null;
    endDate?: Moment | null;
    loadFilters: boolean;
    onChange: ( startDate, endDate ) => void;
    onLoadComplete: () => void;
};

export type StatusNavigationProps = {
    statusParam: SupportTicketStatus;
    loadFilters?: boolean;
    onChange: ( status: SupportTicketStatus ) => void;
    onLoadComplete?: () => void;
};

export type ReplyListProps = {
    ticket: SupportTicket;
    setTicket?: ( ticket: SupportTicket ) => void;
};

export type MainTopicSectionProps = {
    ticket: SupportTicket;
};

export type TicketHeaderProps = {
    ticket: SupportTicket;
};

export type ApiFetchResponse = {
    json: () => SupportTicket[];
    headers: Response[ 'headers' ] & {
        get: (
            key:
                | 'X-WP-Total'
                | 'X-WP-TotalPages'
                | 'X-WP-CurrentPage'
                | 'X-Status-Open'
                | 'X-Status-Closed'
        ) => string | null;
    };
};

export type FilterArgs = {
    page: number | string;
    per_page: number | string;
    customer_id?: number | string;
    start_date?: string;
    end_date?: string;
    status?: SupportTicketStatus;
    search?: string;
    order?: 'asc' | 'desc';
    orderby?: 'date' | 'id' | 'title' | 'status';
};

export type StatusCounts = {
    open: number;
    closed: number;
    total: number;
};

export type UseTicketsReturn = {
    tickets: SupportTicket[];
    isLoading: boolean;
    fetchTickets: ( status?: SupportTicketStatus ) => Promise< void >;
    updateStatus: ( ticket: SupportTicket, status: string ) => Promise< void >;
    getTotalStats: () => Promise< StatusCounts >;
    totalItems: number;
    totalPages: number;
    currentPage: number;
    statusCounts: StatusCounts;
};

export type UseTicketReplyReturn = {
    replies: SupportTicketReply[];
    isLoading: boolean;
    addReply: ( message: string, closeTicket?: boolean ) => Promise< any >;
    editReply: ( replyId: number, message: string ) => Promise< any >;
    deleteReply: ( replyId: number ) => Promise< any >;
    isPending: boolean;
};

export type UseTicketReturn = {
    ticket: SupportTicket;
    setTicket: ( request: SupportTicket ) => void;
    isLoading: boolean;
    fetchTicket: () => Promise< void >;
    isNotFound: boolean;
    isNotPermitted: boolean;
};

export type UseStatusFiltersReturn = {
    statusCounts: SupportStatus[];
    isLoading: boolean;
    fetchStatusCounts: () => Promise< void >;
    getCurrentStatusLabel: (
        statusName: SupportTicketStatus | string
    ) => string;
};

export type CustomerOption = {
    value: number;
    label: string;
};

export type SimpleInputEvent = {
    target: {
        value: string;
    };
};
