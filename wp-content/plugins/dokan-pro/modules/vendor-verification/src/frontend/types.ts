export type FileDocument = {
    id: string;
    name: string;
    url: string;
};

export interface VarificationFormData {
    vendor_id: number;
    method_id: number;
    documents: string[];
    [ key: string ]: any;
}

export type LastStatus = {
    id: number;
    status: 'approved' | 'rejected' | 'pending' | 'cancelled'; // Extend as needed
    label: string;
    note: string;
    documents: FileDocument[];
};

export type VerificationMethod = {
    id: number;
    title: string;
    help_text: string;
    status: boolean;
    required: boolean;
    kind: string;
    seller_address: string;
    last_verification: LastStatus;
};

export type SocialProvider = {
    id: string;
    title: string;
    is_connected: boolean;
    connect_url?: string;
    disconnect_url?: string;
    photo_url?: string;
    profile_url?: string;
    display_name?: string;
    email?: string;
};

export type PhoneVerification = {
    is_configured: boolean;
    active_gateway: string;
    phone_status?: string;
    phone_no?: string;
};

export type VerificationResponse = {
    verification_methods: VerificationMethod[];
    social_providers: SocialProvider[];
    phone_verification: PhoneVerification;
};

export type SubmitResponse = {
    id: number;
    status: LastStatus[ 'status' ];
    status_title: string;
    documents: string[];
    note: string;
    document_urls: Record<
        string,
        {
            title: string;
            url: string;
        }
    >;
};
