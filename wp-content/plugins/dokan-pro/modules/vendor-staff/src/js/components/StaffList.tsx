import {
    DataViews,
    DokanLink,
    Forbidden,
    DokanButton,
    ShortContent,
    // @ts-ignore
    // eslint-disable-next-line import/no-unresolved
} from '@dokan/components';
import { Staff } from '../types';
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { useToast, DokanToaster } from '@getdokan/dokan-ui';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { usePermission } from '@dokan/hooks';

const StaffList = ( { navigate } ) => {
    const toast = useToast();
    const isStaff = usePermission( 'vendor_staff' );

    const [ staffs, setStaffs ] = useState< Staff[] >( [] );
    const [ isLoading, setIsLoading ] = useState( true );
    const [ totalItems, setTotalItems ] = useState( 0 );

    const fields = [
        {
            id: 'full_name',
            label: __( 'Name', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: Staff } ) => (
                <DokanLink
                    as="div"
                    onClick={ () => navigate( `/staffs/update/${ item.ID }` ) }
                    className="font-bold cursor-pointer"
                >
                    <ShortContent
                        content={ `${ item.first_name } ${ item.last_name }` }
                        maxLength={ 30 }
                        useRawHTML={ false }
                    />
                </DokanLink>
            ),
        },
        {
            id: 'email',
            label: __( 'Email', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: Staff } ) => item.user_email,
        },
        {
            id: 'phone',
            label: __( 'Phone', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: Staff } ) => item.phone,
        },
        {
            id: 'user_registered',
            label: __( 'Registered Date', 'dokan' ),
            enableSorting: false,
            render: ( { item }: { item: Staff } ) => item.user_registered,
        },
    ];

    const [ view, setView ] = useState( {
        perPage: 10,
        page: 1,
        type: 'table',
        fields: fields.map( ( field ) => field.id ),
        layout: {
            styles: {
                full_name: { width: '20%' },
                email: { width: '20%' },
                phone: { width: '20%' },
                user_registered: { width: '20%' },
            },
        },
    } );

    const deleteStaffHandler = async ( staff: Staff ) => {
        try {
            await apiFetch( {
                path: `dokan/v1/vendor-staff/${ staff.ID }`,
                method: 'DELETE',
                data: { id: staff.ID, force: true },
            } );
            toast( {
                type: 'success',
                title: __( 'Staff deleted successfully', 'dokan' ),
            } );
            void fetchStaffList();
        } catch ( err ) {
            toast( {
                type: 'error',
                title: __( 'Failed to delete staff', 'dokan' ),
            } );
        }
    };

    const actions = [
        {
            id: 'staff-update',
            label: () => __( 'Edit', 'dokan' ),
            callback: ( [ item ]: Staff[] ) => {
                navigate( `/staffs/update/${ item.ID }` );
            },
        },
        {
            id: 'staff-manage',
            label: () => __( 'Manage', 'dokan' ),
            callback: ( [ item ]: Staff[] ) => {
                navigate( `/staffs/permissions/${ item.ID }` );
            },
        },
        {
            id: 'staff-delete',
            label: () => __( 'Delete', 'dokan' ),
            isDestructive: true,
            confirmTitle: __(
                'Are you sure you want to delete this staff member?',
                'dokan'
            ),
            confirmDescription: __(
                'This action is permanent. Once deleted, the staff member’s profile, permissions, and associated records will be permanently removed.',
                'dokan'
            ),
            callback: ( [ item ]: Staff[] ) => {
                void deleteStaffHandler( item );
            },
        },
    ];

    const fetchStaffList = async () => {
        setIsLoading( true );
        try {
            const path = addQueryArgs( '/dokan/v1/vendor-staff', {
                per_page: view.perPage,
                page: view.page,
                orderby: 'registered',
                order: 'desc',
            } );
            const response = await apiFetch< any >( {
                path,
                parse: false,
            } );
            const data: Staff[] = await response.json();
            setStaffs( data );
            setTotalItems(
                parseInt( response.headers.get( 'X-WP-Total' ) || '0' )
            );
        } catch ( err ) {
            toast( {
                type: 'error',
                title: __( 'Failed to fetch staff list', 'dokan' ),
            } );
        } finally {
            setIsLoading( false );
        }
    };

    useEffect( () => {
        void fetchStaffList();
    }, [ view.page, view.perPage ] );

    const NavigateToStaffList = () => (
        <DokanButton variant="primary" onClick={ () => navigate( '/staffs' ) }>
            { __( 'Back to List', 'dokan' ) }
        </DokanButton>
    );

    if ( isStaff ) {
        return <Forbidden navigateButton={ <NavigateToStaffList /> } />;
    }

    return (
        <div className="dokan-vendor-staff-list">
            <DataViews
                namespace="staff-data-view"
                data={ staffs }
                fields={ fields }
                view={ view }
                actions={ actions }
                isLoading={ isLoading }
                search={ false }
                paginationInfo={ {
                    totalItems,
                    totalPages: Math.ceil( totalItems / view.perPage ),
                } }
                getItemId={ ( item: Staff ) => item.ID }
                onChangeView={ setView }
            />
            <DokanToaster />
        </div>
    );
};

export default StaffList;
