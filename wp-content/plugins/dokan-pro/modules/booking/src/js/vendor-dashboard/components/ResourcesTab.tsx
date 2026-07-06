import { __ } from '@wordpress/i18n';
import { useMemo, useState } from '@wordpress/element';
import { DataViews, DokanModal } from '@dokan/components';
import { useToast } from '@getdokan/dokan-ui';
import apiFetch from '@wordpress/api-fetch';
import { Fill } from '@wordpress/components';
import { Plus } from 'lucide-react';
import { useSelect } from '@wordpress/data';
import dokanCore from '@dokan/stores/core';
import type { BookingResource } from '../types';
import { useBookingResources } from '../hooks';

const ResourcesTab = () => {
    // @ts-ignore
    const bookingUrl: string = window.dokanBooking?.bookingUrl || '';

    const [ view, setView ] = useState( {
        perPage: 10,
        page: 1,
        search: '',
        type: 'table',
        status: 'all',
        layout: {
            styles: {
                name: {
                    width: '50%',
                },
                parent_products: {
                    width: '50%',
                },
            },
        },
    } );
    const [ showResourceModal, setShowResourceModal ] = useState( false );
    const [ newResourceName, setNewResourceName ] = useState( '' );
    const [ isCreatingResource, setIsCreatingResource ] = useState( false );

    const { data, isLoading, totalItems, fetchResources } = useBookingResources(
        { view }
    );

    const hasCapManageResources = useSelect(
        ( select ) =>
            select( dokanCore ).hasCap( 'dokan_manage_booking_resource' ),
        []
    );

    const toast = useToast();

    // ── Fields ───────────────────────────────────────────────────────────────

    const fields = useMemo(
        () => [
            {
                id: 'name',
                label: __( 'Resource Name', 'dokan' ),
                enableSorting: false,
                render: ( { item }: { item: BookingResource } ) => (
                    <a
                        href={ `${ bookingUrl }resources/edit/?id=${ item.id }` }
                        className="text-dokan-link cursor-pointer font-medium"
                    >
                        { item.name }
                    </a>
                ),
            },
            {
                id: 'parent_products',
                label: __( 'Parent Products', 'dokan' ),
                enableSorting: false,
                render: ( { item }: { item: BookingResource } ) => (
                    <div className="flex flex-wrap gap-1">
                        { item.parent_products?.length > 0
                            ? item.parent_products.map( ( product, index ) => (
                                  <span key={ product.id }>
                                      <a
                                          href={ `${ bookingUrl }edit/?product_id=${ product.id }` }
                                          className="text-dokan-link cursor-pointer"
                                      >
                                          { product.title }
                                      </a>
                                      { index <
                                          item.parent_products.length - 1 &&
                                          ', ' }
                                  </span>
                              ) )
                            : '—' }
                    </div>
                ),
            },
        ],
        [ bookingUrl ]
    );

    // ── Actions ──────────────────────────────────────────────────────────────

    const actions = useMemo(
        () => [
            {
                id: 'resource-edit',
                label: () => __( 'Edit', 'dokan' ),
                callback: ( [ item ]: BookingResource[] ) => {
                    window.location.href = `${ bookingUrl }resources/edit/?id=${ item.id }`;
                },
            },
            {
                id: 'resource-delete',
                isEligible: () => hasCapManageResources,
                isDestructive: true,
                label: __( 'Remove', 'dokan' ),
                callback: async ( items: BookingResource[] ) => {
                    try {
                        await apiFetch( {
                            path: `/dokan/v1/booking/resources/${ items[ 0 ].id }`,
                            method: 'DELETE',
                        } );

                        await fetchResources();
                        toast( {
                            type: 'success',
                            title: __(
                                'Resource removed successfully',
                                'dokan'
                            ),
                        } );
                    } catch ( error: any ) {
                        toast( {
                            type: 'error',
                            title:
                                error.message ||
                                __( 'Failed to remove resource', 'dokan' ),
                        } );
                    }
                },
            },
        ],
        [ bookingUrl, hasCapManageResources, fetchResources, toast ]
    );

    // ── Handlers ─────────────────────────────────────────────────────────────

    const handleCreateResource = async () => {
        if ( ! newResourceName.trim() ) {
            return;
        }

        setIsCreatingResource( true );

        try {
            await apiFetch( {
                path: '/dokan/v1/booking/resources',
                method: 'POST',
                data: { name: newResourceName.trim() },
            } );

            setNewResourceName( '' );
            setShowResourceModal( false );
            await fetchResources();
            toast( {
                type: 'success',
                title: __( 'Resource created successfully', 'dokan' ),
            } );
        } catch ( error: any ) {
            toast( {
                type: 'error',
                title:
                    error.message || __( 'Failed to create resource', 'dokan' ),
            } );
        } finally {
            setIsCreatingResource( false );
        }
    };

    // ── Render ────────────────────────────────────────────────────────────────

    return (
        <>
            { hasCapManageResources && (
                <Fill name="dokan-header-actions">
                    <button
                        onClick={ () => setShowResourceModal( true ) }
                        className="dokan-btn dokan-btn-theme inline-flex items-center gap-1"
                    >
                        <Plus size={ 16 } />
                        { __( 'Add New Resource', 'dokan' ) }
                    </button>
                </Fill>
            ) }

            <DataViews
                data={ data }
                namespace="booking-resources-data-view"
                fields={ fields }
                getItemId={ ( item: any ) => item.id }
                onChangeView={ setView }
                paginationInfo={ {
                    totalItems,
                    totalPages: Math.ceil( totalItems / view.perPage ),
                } }
                view={ view }
                actions={ actions }
                isLoading={ isLoading }
                search={ true }
            />

            <DokanModal
                className="min-w-96"
                isOpen={ !! showResourceModal }
                onConfirm={ handleCreateResource }
                namespace="booking-resource-create"
                onClose={ () => {
                    setShowResourceModal( false );
                    setNewResourceName( '' );
                } }
                dialogTitle={ __( 'Add New Resource', 'dokan' ) }
                confirmButtonText={ __( 'OK', 'dokan' ) }
                cancelButtonText={ __( 'Cancel', 'dokan' ) }
                confirmButtonVariant="primary"
                confirmButtonDisabled={ ! newResourceName.trim() }
                loading={ isCreatingResource }
                dialogContent={
                    <div className="flex flex-col gap-4">
                        <p className="text-sm text-gray-600">
                            { __(
                                'Enter a name for the new resource',
                                'dokan'
                            ) }
                        </p>
                        <input
                            type="text"
                            className="dokan-form-control w-full"
                            value={ newResourceName }
                            onChange={ ( e ) =>
                                setNewResourceName( e.target.value )
                            }
                            placeholder={ __( 'Resource name', 'dokan' ) }
                            onKeyDown={ ( e ) => {
                                if ( e.key === 'Enter' ) {
                                    e.preventDefault();
                                    void handleCreateResource();
                                }
                            } }
                            autoFocus
                        />
                    </div>
                }
            />
        </>
    );
};

export default ResourcesTab;
