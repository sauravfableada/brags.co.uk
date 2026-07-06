export interface Staff {
    ID: string;
    first_name: string;
    last_name: string;
    user_email: string;
    phone: string;
    user_registered: string;
}

export interface CapabilityCategory {
    [ key: string ]: string;
}

export type CapabilityItem = {
    capability: string;
    access: boolean;
};
