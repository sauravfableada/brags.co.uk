import { __, sprintf } from '@wordpress/i18n';
import { useState, useCallback } from '@wordpress/element';
import { Megaphone, Loader2 } from 'lucide-react';
// @ts-ignore
import { DokanModal, DokanTooltip } from '@dokan/components';
import { advData, wpAjaxPost, extractErrorMessage } from './helpers';
import type { ProductItem } from './types';

export function AdvertiseButton( { item }: { item: ProductItem } ) {
    const adv = item.advertisement;
    const isAdvertised = !! adv?.already_advertised;

    const [ loading, setLoading ] = useState( false );
    const [ confirmOpen, setConfirmOpen ] = useState( false );
    const [ confirmHtml, setConfirmHtml ] = useState( '' );
    const [ errorOpen, setErrorOpen ] = useState( false );
    const [ errorMsg, setErrorMsg ] = useState( '' );

    const tooltipTitle =
        isAdvertised && adv?.expire_date
            ? sprintf(
                  /* translators: %s: advertisement expiry date */
                  __( 'Expires on: %s', 'dokan' ),
                  adv.expire_date
              )
            : __( 'Advertise', 'dokan' );

    // Step 1: click → fetch pricing info from server
    const handleClick = useCallback( async () => {
        if ( loading || isAdvertised ) {
            return;
        }

        if ( item.status !== 'publish' ) {
            setErrorMsg(
                advData().product_not_published ??
                    __(
                        'Products must be published before you can advertise.',
                        'dokan'
                    )
            );
            setErrorOpen( true );
            return;
        }

        setLoading( true );

        try {
            const response = await wpAjaxPost(
                'dokan_get_advertisement_status',
                {
                    product_id: item.id,
                    advertise_product_nonce: advData().advertise_product_nonce,
                }
            );
            setConfirmHtml( response.advertisement_text );
            setConfirmOpen( true );
        } catch ( err ) {
            setErrorMsg( extractErrorMessage( err ) );
            setErrorOpen( true );
        } finally {
            setLoading( false );
        }
    }, [ item, loading, isAdvertised ] );

    // Step 2: confirmed → add to cart / create free ad
    const handleConfirm = useCallback( async () => {
        setConfirmOpen( false );
        setLoading( true );

        try {
            const response = await wpAjaxPost(
                'dokan_add_advertise_product_to_cart',
                {
                    product_id: item.id,
                    advertise_product_nonce: advData().advertise_product_nonce,
                }
            );

            if ( true === response.free_purchase ) {
                window.location.reload();
            } else {
                window.location.replace( advData().checkout_url );
            }
        } catch ( err ) {
            setErrorMsg( extractErrorMessage( err ) );
            setErrorOpen( true );
            setLoading( false );
        }
    }, [ item ] );

    const color = isAdvertised ? '#9ca3af' : '#7047eb';

    return (
        <>
            <DokanTooltip content={ tooltipTitle }>
                <span
                    role="button"
                    tabIndex={ isAdvertised ? -1 : 0 }
                    className="inline-flex items-center gap-1.5"
                    style={ {
                        color,
                        cursor:
                            isAdvertised || loading ? 'default' : 'pointer',
                        pointerEvents: loading ? 'none' : undefined,
                        opacity: loading ? 0.6 : 1,
                    } }
                    onClick={ handleClick }
                    onKeyDown={ ( e ) => {
                        if ( e.key === 'Enter' || e.key === ' ' ) {
                            handleClick();
                        }
                    } }
                >
                    { loading ? (
                        <Loader2 size={ 14 } className="animate-spin" />
                    ) : (
                        <Megaphone size={ 14 } />
                    ) }
                    <span>{ __( 'Promote', 'dokan' ) }</span>
                </span>
            </DokanTooltip>

            { /* Confirmation modal: shows advertisement_text HTML from server */ }
            <DokanModal
                isOpen={ confirmOpen }
                namespace="product-adv-confirm"
                className="max-w-md w-full"
                onClose={ () => setConfirmOpen( false ) }
                onConfirm={ handleConfirm }
                confirmButtonText={ __( 'Confirm', 'dokan' ) }
                dialogHeader={
                    <div className="px-6 py-4 border-b border-gray-200">
                        <h2 className="text-base font-semibold text-gray-900">
                            { __( 'Advertise Product', 'dokan' ) }
                        </h2>
                    </div>
                }
                dialogContent={
                    <div
                        className="text-sm text-gray-700"
                        dangerouslySetInnerHTML={ { __html: confirmHtml } }
                    />
                }
            />

            { /* Error modal */ }
            <DokanModal
                isOpen={ errorOpen }
                namespace="product-adv-error"
                className="max-w-md w-full"
                onClose={ () => setErrorOpen( false ) }
                onConfirm={ () => setErrorOpen( false ) }
                confirmButtonText={ __( 'OK', 'dokan' ) }
                confirmButtonVariant="primary"
                hideCancelButton={ true }
                dialogHeader={
                    <div className="px-6 py-4 border-b border-gray-200">
                        <h2 className="text-base font-semibold text-red-600">
                            { advData().on_error_message ??
                                __( 'Something went wrong.', 'dokan' ) }
                        </h2>
                    </div>
                }
                dialogContent={
                    <p className="text-sm text-gray-700">{ errorMsg }</p>
                }
            />
        </>
    );
}
