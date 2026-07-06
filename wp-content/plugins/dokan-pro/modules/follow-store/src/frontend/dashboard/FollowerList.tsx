import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';
import { useToast, DokanToaster } from '@getdokan/dokan-ui';
import { DataViews, DateTimeHtml } from '@dokan/components';
import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

interface Follower {
    id: number;
    first_name: string;
    last_name: string;
    full_name: string;
    avatar_url: string;
    avatar_url_2x: string;
    followed_at: string;
    formatted_followed_at: string;
}

function FollowerList() {
    const [ data, setData ] = useState< Follower[] >( [] );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ totalItems, setTotalItems ] = useState( 0 );
    const toast = useToast();

    const [ view, setView ] = useState( {
        perPage: 10,
        page: 1,
        search: '',
        type: 'table' as const,
        fields: [ 'full_name', 'followed_at' ],
        layout: {
            styles: {
                full_name: {
                    width: '50%',
                },
                followed_at: {
                    width: '50%',
                },
            },
        },
    } );

    const fields = [
        {
            id: 'full_name',
            label: __( 'Name', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: Follower } ) => (
                <div className="flex items-center gap-2">
                    <img
                        src={ item.avatar_url }
                        srcSet={ `${ item.avatar_url_2x } 2x` }
                        alt={ item.full_name }
                        className="w-8 h-8 rounded-full"
                    />
                    <span>{ item.full_name }</span>
                </div>
            ),
        },
        {
            id: 'followed_at',
            label: __( 'Followed At', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: Follower } ) => (
                <DateTimeHtml date={ item.followed_at } />
            ),
        },
    ];

    const fetchFollowers = useCallback( async () => {
        setIsLoading( true );

        const queryArgs: Record< string, any > = {
            per_page: view.perPage,
            page: view.page,
        };

        if ( view.search ) {
            queryArgs.search = view.search;
        }

        try {
            const response = ( await apiFetch( {
                path: addQueryArgs(
                    '/dokan/v1/follow-store/followers',
                    queryArgs
                ),
                method: 'GET',
                parse: false,
            } ) ) as Response;

            const followers: Follower[] = await response.json();
            const total = parseInt(
                response.headers.get( 'X-WP-Total' ) ?? '0'
            );

            setData( followers );
            setTotalItems( total );
        } catch ( error: any ) {
            toast( {
                type: 'error',
                title:
                    error?.message ||
                    __( 'Failed to fetch followers', 'dokan' ),
            } );
        } finally {
            setIsLoading( false );
        }
    }, [ view.page, view.perPage, view.search ] );

    useEffect( () => {
        void fetchFollowers();
    }, [ view.page, view.perPage, view.search ] );

    const paginationInfo = {
        totalItems,
        totalPages: Math.ceil( totalItems / view.perPage ),
    };

    return (
        <div className="dokan-follow-store-list-container">
            <DataViews
                data={ data }
                namespace="dokan-follow-store-data-view"
                fields={ fields }
                getItemId={ ( item: Follower ) => String( item.id ) }
                onChangeView={ setView }
                paginationInfo={ paginationInfo }
                view={ view }
                isLoading={ isLoading }
                search={ true }
            />
            <DokanToaster />
        </div>
    );
}

export default FollowerList;
