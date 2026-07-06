export type AvatarUrls = Record< number, string >;

export type Meta = Record< string, string >;

export type WooCommerceMeta = Record< string, any >;

export interface TargetHints {
    allow: string[];
}

export interface Link {
    href: string;
    targetHints?: TargetHints;
}

export interface Links {
    self: Link[];
    collection: Link[];
}

export interface User {
    id: number;
    name: string;
    url: string;
    description: string;
    link: string;
    slug: string;
    avatar_urls: AvatarUrls;
    meta: Meta;
    is_super_admin: boolean;
    woocommerce_meta: WooCommerceMeta;
    _links: Links;
}
