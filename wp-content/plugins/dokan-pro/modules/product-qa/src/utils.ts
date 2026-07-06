import { addQueryArgs } from '@wordpress/url';

declare global {
    interface Window {
        dokan: {
            product_edit_nonce: string;
        };
        dokanProductQa: {
            productUrl: string;
        };
    }
}

export const redirectToEditProduct = ( productId: string ) => {
    const productUrl = window.dokanProductQa.productUrl;
    return addQueryArgs( productUrl, {
        product_id: productId,
        action: 'edit',
        _dokan_edit_product_nonce: window.dokan.product_edit_nonce,
    } );
};
