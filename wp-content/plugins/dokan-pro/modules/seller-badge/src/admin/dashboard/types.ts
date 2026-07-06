export type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

export interface ConditionText {
    prefix: string;
    suffix: string;
    type: string;
}

export interface BadgeGroup {
    key: string;
    title: string;
    type: string;
}

export interface InputGroupIcon {
    condition: string;
    data: string;
}

export interface BadgeEvent {
    id: string;
    title: string;
    description: string;
    condition_text: ConditionText;
    hover_text: string;
    group: BadgeGroup;
    has_multiple_levels: boolean;
    badge_logo: string;
    badge_logo_raw: string;
    input_group_icon: InputGroupIcon;
    status: 'draft' | 'published' | string; // Using string as a fallback for other potential statuses
    created: boolean;
}

export interface BadgeLevel {
    id: number;
    badge_id: number;
    level: number;
    level_condition: string;
    formatted_condition: string;
    level_data: string;
    vendor_count: number;
}

export interface BadgeLink {
    href: string;
    targetHints?: {
        allow: HttpMethod[];
    };
}

export interface BadgeLinks {
    self: BadgeLink[];
    collection: BadgeLink[];
}

export interface SellerBadge {
    id: number;
    badge_name: string;
    badge_logo: string;
    badge_logo_raw: string;
    default_logo: string;
    formatted_default_logo: string;
    event_type: string;
    formatted_hover_text: string;
    event: BadgeEvent;
    badge_status: 'published' | 'draft' | string; // Using string as a fallback for other potential statuses
    formatted_badge_status: string;
    level_count: number;
    vendor_count: number;
    acquired_level_count: number;
    created_by: number;
    created_at: string; // Could also be `Date` if you plan to parse it
    levels: BadgeLevel[];
    _links: BadgeLinks;
}
