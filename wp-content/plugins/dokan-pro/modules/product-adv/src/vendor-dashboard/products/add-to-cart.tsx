import domReady from '@wordpress/dom-ready';
import { addAction, removeAction } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import {
    createRoot,
    useCallback,
    useEffect,
    useState,
} from '@wordpress/element';
// @ts-ignore
import { DokanModal } from '@dokan/components';
import { useDispatch } from '@wordpress/data';
import { DokanToaster, useToast } from '@getdokan/dokan-ui';

declare const dokanProductAdv: {
    ajaxUrl: string;
    nonce: string;
};

interface FilterType {
    productId: number;
    newData: Record< string, any >;
}

const App = () => {
    const [ modalOpen, setModalOpen ] = useState( false );
    const [ productId, setProductId ] = useState( 0 );
    const toast = useToast();
    const [ isLoading, setIsLoading ] = useState( false );
    const { updateProduct } = useDispatch( 'dokan/product-editor' );

    const handleChange = useCallback(
        ( { productId: id, newData }: FilterType ) => {
            if ( ! ( 'dokan_advertise_this_product' in newData ) ) {
                return;
            }

            if ( ! newData.dokan_advertise_this_product ) {
                return;
            }
            const isNewProduct =
                ( window as any ).dokanProductEditor?.is_new_product || false;

            if ( isNewProduct ) {
                updateProduct( id, { dokan_advertise_this_product: false } );
                toast( {
                    type: 'error',
                    title: __(
                        'Please save the product before advertising.',
                        'dokan'
                    ),
                } );
                return;
            }

            setModalOpen( true );
            setProductId( id );
        },
        [ toast, updateProduct ]
    );

    const dispatchAdvertisementChange = async ( id: number ) => {
        const formData = new FormData();
        formData.append( 'action', 'dokan_add_advertise_product_to_cart' );
        formData.append( 'advertise_product_nonce', dokanProductAdv.nonce );
        formData.append( 'product_id', String( id ) );
        try {
            setIsLoading( true );
            const response = await fetch( dokanProductAdv.ajaxUrl, {
                method: 'POST',
                body: formData,
            } );

            const data = await response.json();

            if ( data.success ) {
                toast( {
                    type: 'success',
                    title:
                        data.message ||
                        __( 'Product successfully added to cart.', 'dokan' ),
                } );
                // @ts-expect-error
                window.jQuery( document.body ).trigger( 'wc_fragment_refresh' );
            } else {
                toast( {
                    type: 'error',
                    title:
                        data.message || __( 'Something went wrong.', 'dokan' ),
                } );
            }
        } catch ( error ) {
            toast( {
                type: 'error',
                title: __( 'Something went wrong.', 'dokan' ),
            } );
        } finally {
            setIsLoading( false );
        }
    };

    useEffect( () => {
        addAction(
            'dokan_product_editor_field_changed',
            'dokan-product-adv/field-change-listener',
            handleChange
        );
        addAction(
            'dokan_product_advertise_button_click',
            'dokan-product-adv/button-click-listener',
            ( id: number ) => {
                setModalOpen( true );
                setProductId( id );
            }
        );
        return () => {
            removeAction(
                'dokan_product_editor_field_changed',
                'dokan-product-adv/field-change-listener'
            );
            removeAction(
                'dokan_product_advertise_button_click',
                'dokan-product-adv/button-click-listener'
            );
        };
    }, [ handleChange ] );

    return (
        <>
            <DokanModal
                isOpen={ modalOpen }
                onClose={ () => setModalOpen( false ) }
                namespace="product-adv-action-modal-confirmation"
                onConfirm={ async () => {
                    await dispatchAdvertisementChange( productId );
                } }
                dialogTitle={ __( 'Advertise Product', 'dokan-lite' ) }
                confirmButtonText={ __( 'Advertise', 'dokan-lite' ) }
                confirmationTitle={ __( 'Confirm Advertise', 'dokan-lite' ) }
                confirmationDescription={ __(
                    'Are you sure you want to advertise this product?',
                    'dokan-lite'
                ) }
                isLoading={ isLoading }
            />
            <DokanToaster />
        </>
    );
};

domReady( () => {
    const root = document.createElement( 'div' );
    root.id = 'dokan-product-adv-action-modal-root';
    root.className = 'dokan-layout';
    document.body.appendChild( root );
    createRoot( root ).render( <App /> );
} );
