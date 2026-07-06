export type ShippingClass = {
    class: string;
    priority: number;
};

export type TableRateSettingsData = {
    title: string;
    tax_status: string;
    prices_include_tax: string;
    handling_fee: string;
    max_shipping_cost: string;
    calculation_type: string;
    order_handling_fee: string;
    min_cost: string;
    max_cost: string;
    classes_priorities: Record< ShippingClass >;
    default_priority: number;
};

export type TableRateSettingsResponse = {
    success: boolean;
    message: string;
};

export type TableRate = {
    rate_id: string;
    zone_id: string;
    instance_id: string;
    rate_class: string;
    rate_condition: string;
    rate_min: string;
    rate_max: string;
    rate_cost: string;
    rate_cost_per_item: string;
    rate_cost_per_weight_unit: string;
    rate_cost_percent: string;
    rate_label: string;
    rate_priority: string;
    rate_order: string;
    rate_abort: string;
    rate_abort_reason: string;
};

export type UseTableRatesReturn = {
    selectedRows: string[];
    tableData: TableRate[];
    isLoading: boolean;
    isSaving: boolean;
    handleSelectAll: ( checked: boolean ) => void;
    handleSelectRow: ( rateOrder: string, checked: boolean ) => void;
    handleDuplicateRows: () => void;
    handleAddShippingRate: () => void;
    handleDeleteRows: () => Promise< void >;
    handleTableDataUpdate: (
        rateOrder: string,
        field: keyof TableRate,
        value: string
    ) => void;
    handleSave: () => Promise< void >;
    handleOrderUpdate: ( updatedItems: TableRate[] ) => void;
};

export type DistanceRateSettingsData = {
    title: string;
    tax_status: string;
    distance_rate_mode: string;
    distance_rate_avoid: string;
    distance_rate_unit: string;
    distance_rate_show_distance: string;
    distance_rate_show_duration: string;
    distance_rate_address_1: string;
    distance_rate_address_2: string;
    distance_rate_city: string;
    distance_rate_postal_code: string;
    distance_rate_state_province: string;
    distance_rate_country: string;
};

export type DistanceRateSettingsResponse = {
    success: boolean;
    message: string;
    settings: DistanceRateSettingsData;
};

export type DistanceRate = {
    rate_id: string;
    zone_id: string;
    instance_id: string;
    vendor_id: string;
    rate_condition: string;
    rate_min: string;
    rate_max: string;
    rate_cost: string;
    rate_cost_unit: string;
    rate_fee: string;
    rate_break: string;
    rate_abort: string;
    rate_order: string;
};

export type UseDistanceRatesReturn = {
    selectedRows: string[];
    tableData: DistanceRate[];
    isLoading: boolean;
    isSaving: boolean;
    handleSelectAll: ( checked: boolean ) => void;
    handleSelectRow: ( rateOrder: string, checked: boolean ) => void;
    handleDuplicateRows: () => void;
    handleAddDistanceRate: () => void;
    handleDeleteRows: () => Promise< void >;
    handleTableDataUpdate: (
        rateOrder: string,
        field: keyof DistanceRate,
        value: string
    ) => void;
    handleSave: () => Promise< void >;
    handleOrderUpdate: ( updatedItems: DistanceRate[] ) => void;
};
