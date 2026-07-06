export interface AdvLocalized {
    advertise_product_nonce: string;
    advertise_active: string;
    checkout_url: string;
    on_error_message: string;
    on_success_message: string;
    product_not_published: string;
    on_load_advertisement_status: string;
}

export interface ProductAdvertisement {
    already_advertised: boolean;
    expire_date?: string;
}

export interface ProductItem {
    id: number;
    status: string;
    advertisement?: ProductAdvertisement | null;
    [ key: string ]: unknown;
}

export interface Field {
    id: string;
    label: string;
    enableSorting: boolean;
    render: ( props: { item: ProductItem } ) => React.ReactElement | null;
}
