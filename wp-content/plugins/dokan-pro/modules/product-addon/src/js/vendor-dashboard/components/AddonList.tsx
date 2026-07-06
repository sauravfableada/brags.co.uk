import { __ } from '@wordpress/i18n';
import { useEffect, useState, useMemo, useCallback } from '@wordpress/element';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { DataViews } from '@dokan/components';
import apiFetch from '@wordpress/api-fetch';
import { useToast } from '@getdokan/dokan-ui';

import { GlobalAddon, AddonListProps } from '../types';

type SortDirection = 'asc' | 'desc';

interface AddonView {
    perPage: number;
    page: number;
    search: string;
    type: string;
    fields: string[];
    layout: { styles: Record< string, { width: string } > };
    sort?: { field: string; direction: SortDirection };
}

interface AddonField {
    id: string;
    label: string;
    enableSorting?: boolean;
    maxWidth?: number;
    render: ( args: { item: GlobalAddon } ) => JSX.Element;
    getValue?: ( args: { item: GlobalAddon } ) => string | number;
}

const fields: AddonField[] = [
    {
        id: 'name',
        label: __( 'Name', 'dokan' ),
        enableSorting: false,
        render: ( { item }: { item: GlobalAddon } ) => (
            <a
                href={ `${ dokanProductAddon.settingsUrl }?edit=${ item.id }` }
                className="font-bold text-dokan-link hover:underline cursor-pointer relative z-10"
                onClick={ ( e ) => e.stopPropagation() }
            >
                { item.name }
            </a>
        ),
    },
    {
        id: 'priority',
        label: __( 'Priority', 'dokan' ),
        enableSorting: true,
        maxWidth: 100,
        render: ( { item }: { item: GlobalAddon } ) => (
            <span>{ item.priority }</span>
        ),
        getValue: ( { item }: { item: GlobalAddon } ) =>
            Number( item.priority ?? 0 ),
    },
    {
        id: 'categories',
        label: __( 'Product Categories', 'dokan' ),
        enableSorting: false,
        render: ( { item }: { item: GlobalAddon } ) => {
            if ( item.all_products ) {
                return <span>{ __( 'All Products', 'dokan' ) }</span>;
            }

            if ( ! item.categories.length ) {
                return <span>&mdash;</span>;
            }

            return (
                <span>
                    { item.categories.map( ( cat ) => cat.name ).join( ', ' ) }
                </span>
            );
        },
    },
    {
        id: 'field_count',
        label: __( 'Number of Fields', 'dokan' ),
        enableSorting: false,
        maxWidth: 140,
        render: ( { item }: { item: GlobalAddon } ) => (
            <span>{ item.field_count }</span>
        ),
    },
];

const defaultViewFields = fields.map( ( field ) => field.id );

const AddonList = ( { navigate }: AddonListProps ) => {
    const [ data, setData ] = useState< GlobalAddon[] >( [] );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ view, setView ] = useState< AddonView >( {
        perPage: 10,
        page: 1,
        search: '',
        type: 'table',
        fields: defaultViewFields,
        layout: {
            styles: {
                name: { width: '40%' },
                priority: { width: '15%' },
                categories: { width: '30%' },
                field_count: { width: '15%' },
            },
        },
    } );

    const toast = useToast();

    const fetchAddons = useCallback( async () => {
        setIsLoading( true );
        try {
            const response = ( await apiFetch( {
                path: '/dokan/v1/vendor/product-addons',
                method: 'GET',
                parse: false,
            } ) ) as Response;

            const addons = await response.json();

            setData( addons );
        } catch ( error: any ) {
            toast( {
                type: 'error',
                title:
                    error.message ||
                    __( 'Failed to fetch product addons', 'dokan' ),
            } );
        } finally {
            setIsLoading( false );
        }
    }, [ toast ] );

    const actions = useMemo(
        () => [
            {
                id: 'addon-edit',
                label: () => __( 'Edit', 'dokan' ),
                callback: ( [ item ]: GlobalAddon[] ) => {
                    window.location.href = `${ dokanProductAddon.settingsUrl }?edit=${ item.id }`;
                },
            },
            {
                id: 'addon-delete',
                isDestructive: true,
                label: () => __( 'Delete', 'dokan' ),
                callback: async ( [ item ]: GlobalAddon[] ) => {
                    try {
                        await apiFetch( {
                            path: `/dokan/v1/vendor/product-addons/${ item.id }`,
                            method: 'DELETE',
                        } );

                        toast( {
                            type: 'success',
                            title: __(
                                'Product addon deleted successfully',
                                'dokan'
                            ),
                        } );

                        await fetchAddons();
                    } catch ( error: any ) {
                        toast( {
                            type: 'error',
                            title:
                                error.message ||
                                __( 'Failed to delete product addon', 'dokan' ),
                        } );
                    }
                },
            },
        ],
        [ fetchAddons, navigate, toast ]
    );

    useEffect( () => {
        void fetchAddons();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [] );

    const onViewChange = useCallback( ( newView: AddonView ) => {
        setView( ( prev ) =>
            newView.search !== prev.search ? { ...newView, page: 1 } : newView
        );
    }, [] );

    const filteredData = useMemo( () => {
        const term = view.search?.trim().toLowerCase() ?? '';
        const filtered = ! term
            ? data
            : data.filter(
                  ( item ) => item.name?.toLowerCase().includes( term )
              );

        if ( ! view.sort?.field ) {
            return filtered;
        }

        const sortField = fields.find(
            ( field ) => field.id === view.sort?.field
        );
        if ( ! sortField?.getValue ) {
            return filtered;
        }

        const dir = view.sort.direction === 'asc' ? 1 : -1;
        return [ ...filtered ].sort( ( a, b ) => {
            const aValue = sortField.getValue!( { item: a } );
            const bValue = sortField.getValue!( { item: b } );

            if ( aValue === bValue ) {
                return 0;
            }
            return ( aValue > bValue ? 1 : -1 ) * dir;
        } );
    }, [ data, view.search, view.sort ] );

    const paginatedData = useMemo( () => {
        const start = ( view.page - 1 ) * view.perPage;
        return filteredData.slice( start, start + view.perPage );
    }, [ filteredData, view.page, view.perPage ] );

    return (
        <DataViews
            namespace="dokan-product-addon-data-view"
            data={ paginatedData }
            fields={ fields }
            view={ view }
            onChangeView={ onViewChange }
            getItemId={ ( item: GlobalAddon ) => item.id }
            isLoading={ isLoading }
            paginationInfo={ {
                totalItems: filteredData.length,
                totalPages: Math.ceil( filteredData.length / view.perPage ),
            } }
            actions={ actions }
            search={ true }
            searchLabel={ __( 'Search addons by name', 'dokan' ) }
        />
    );
};

export default AddonList;
