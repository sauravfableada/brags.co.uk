import apiFetch from '@wordpress/api-fetch';

// API call to cancel vendor subscription
export const cancelVendorSubscription = async (vendorId: number ) => {
    const data = { action: 'cancel' };
    return await apiFetch( {
        path: `dokan/v1/vendor-subscription/update/${ vendorId }`,
        method: 'PUT',
        data,
    } );
};

export const assignSubscription = async ( vendor, subscriptionId ) => {
    console.log(vendor, subscriptionId);
    const data = {
        ...vendor,
        assigned_subscription: subscriptionId,
        subscription_nonce: window?.dokanAdminDashboard?.nonce,
    };
    console.log(data);
    return await apiFetch( {
        path: `dokan/v1/stores/${ vendor?.id }`,
        method: 'POST',
        data,
    } );
};
