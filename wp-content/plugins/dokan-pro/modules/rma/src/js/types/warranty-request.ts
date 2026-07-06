export type WarrantyProduct = {
    id: number;
    title: string;
    thumbnail: string | false;
    quantity: string;
    url: string;
    price: number;
    item_id: string;
    tax: number;
};

export type WarrantyRequest = {
    reasons_label: string;
    id: number;
    order_id: number;
    vendor: {
        store_id: number;
        store_name: string;
        store_url: string;
    };
    customer: {
        id: number;
        name: string;
    };
    type: string;
    type_label: string;
    status: string;
    reasons: string;
    details: string;
    note: string;
    created_at: string;
    items: Array< WarrantyProduct >;
    is_order_deleted: boolean;
    is_refund_pending?: boolean;
};

export type WarrantyRequestConversation = {
    id: number;
    from: string;
    to: string;
    message: string;
    created_at: string;
};

export type WarrantyRequestStatuses = {
    [ key: string ]: string;
};
