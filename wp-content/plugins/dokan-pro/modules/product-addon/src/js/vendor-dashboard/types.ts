export interface AddonCategory {
    id: number;
    name: string;
    slug: string;
}

export interface GlobalAddon {
    id: number;
    name: string;
    priority: number;
    all_products: boolean;
    categories: AddonCategory[];
    field_count: number;
}

export interface AddonListProps {
    navigate: ( path: string ) => void;
}

declare global {
    const dokanProductAddon: {
        settingsUrl: string;
        storeUrl: string;
    };
}