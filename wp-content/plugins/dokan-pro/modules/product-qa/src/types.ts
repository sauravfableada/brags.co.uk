export type Answer = {
    id: number;
    question_id: number;
    answer: string;
    user_id: number;
    created_at: string;
    updated_at: string;
    human_readable_created_at: string;
    human_readable_updated_at: string;
    user_display_name: string;
};

export type Product = {
    id: number;
    title: string;
    image: string;
};

type Vendor = {
    id: number;
    name: string;
    avatar: string;
};

export type Question = {
    id: number;
    product_id: number;
    question: string;
    user_id: number;
    read: number;
    status: 'visible' | 'hidden' | string;
    created_at: string;
    updated_at: string;
    answer?: Answer;
    user_display_name: string;
    human_readable_created_at: string;
    human_readable_updated_at: string;
    display_human_readable_created_at: boolean;
    display_human_readable_updated_at: boolean;
    product: Product;
    vendor: Vendor;
};
