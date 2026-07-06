import { useEffect, useState } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import {
    VendorAsyncSelect,
    DokanModal,
    ProductAsyncSelect,
    // @ts-ignore
    // eslint-disable-next-line import/no-unresolved
} from '@dokan/components';
import { Home, Search } from 'lucide-react';
import { SimpleCheckbox, useToast } from '@getdokan/dokan-ui';

type SelectOption = { label: string; value: string };

interface Props {
    isOpen: boolean;
    onClose: () => void;
    onAdded?: () => void;
}

const AddAdvertisementModal = ( { isOpen, onClose, onAdded }: Props ) => {
    const [ selectedVendor, setSelectedVendor ] =
        useState< SelectOption | null >( null );
    const [ selectedProduct, setSelectedProduct ] =
        useState< SelectOption | null >( null );
    const [ reverseWithdrawalEntry, setReverseWithdrawalEntry ] =
        useState( false );
    const [ loading, setLoading ] = useState( false );
    const toast = useToast();

    useEffect( () => {
        // prefetch some vendors when modal opens
        // reset when modal is closed
        if ( ! isOpen ) {
            setSelectedVendor( null );
            setSelectedProduct( null );
            setReverseWithdrawalEntry( false );
        }
    }, [ isOpen ] );

    const handleAdd = async () => {
        if ( ! selectedVendor || ! selectedProduct ) {
            return;
        }

        setLoading( true );
        try {
            const response = await apiFetch( {
                path: addQueryArgs( 'dokan/v1/product_adv/create', {} ),
                method: 'POST',
                data: {
                    product_id: parseInt( selectedProduct.value, 10 ),
                    vendor_id: parseInt( selectedVendor.value, 10 ),
                    reverse_withdrawal_entry: reverseWithdrawalEntry,
                },
            } );
            toast( {
                type: 'success',
                title: __( 'Advertisement added successfully', 'dokan' ),
            } );
            setSelectedProduct( null );
            if ( onAdded ) {
                onAdded();
            }
            onClose();
            return response;
        } catch ( error: any ) {
            toast( {
                type: 'error',
                title:
                    error?.message ||
                    __(
                        'Unable to add advertisement. Please try again.',
                        'dokan'
                    ),
            } );
        } finally {
            setLoading( false );
        }
    };

    return (
        <DokanModal
            isOpen={ isOpen }
            namespace="add-new-advertisement"
            onClose={ onClose }
            onConfirm={ handleAdd }
            dialogTitle={ __( 'Add New Advertisement', 'dokan' ) }
            confirmButtonText={ __( 'Add New', 'dokan' ) }
            confirmButtonVariant="primary"
            loading={ loading }
            dialogContent={
                <div className="space-y-6 w-[500px]">
                    <div>
                        <div className="block text-sm font-medium text-gray-700 mb-2">
                            { __( 'Select Store', 'dokan' ) }
                        </div>
                        <VendorAsyncSelect
                            key="add-vendor-select"
                            icon={ <Home size={ 16 } /> }
                            value={ selectedVendor }
                            onChange={ ( vendor: any ) => {
                                setSelectedVendor( vendor );
                                setSelectedProduct( null );
                            } }
                            placeholder={ __( 'Filter by store', 'dokan' ) }
                            isClearable
                        />
                    </div>

                    <div>
                        <div className="block text-sm font-medium text-gray-700 mb-2">
                            { __( 'Select Product', 'dokan' ) }
                        </div>
                        <ProductAsyncSelect
                            key="add-product-select"
                            icon={ <Search size={ 16 } /> }
                            value={ selectedProduct }
                            onChange={ setSelectedProduct }
                            disabled={ ! selectedVendor }
                            className="!rounded-lg"
                            extraQuery={
                                selectedVendor
                                    ? { author: selectedVendor.value }
                                    : {}
                            }
                            noOptionsMessage={ () =>
                                __(
                                    'No products found',
                                    'dokan'
                                )
                            }
                            placeholder={ __( 'Filter by product', 'dokan' ) }
                            isClearable
                        />
                    </div>

                    <div>
                        <div className="flex items-center gap-1 text-sm font-medium text-gray-700">
                            <SimpleCheckbox
                                className={ 'm-0' }
                                checked={ reverseWithdrawalEntry }
                                onChange={ ( e ) =>
                                    setReverseWithdrawalEntry(
                                        ( e.target as HTMLInputElement ).checked
                                    )
                                }
                                input={ {
                                    id: 'reverseWithdrawalEntry',
                                } }
                            />
                            <label
                                className="m-0 leading-none"
                                htmlFor="reverseWithdrawalEntry"
                            >
                                { __(
                                    'Add Reverse Withdrawal Entry?',
                                    'dokan'
                                ) }
                            </label>
                        </div>
                    </div>
                </div>
            }
        />
    );
};

export default AddAdvertisementModal;
