import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';
import StaffList from './components/StaffList';
import '../../../../src/definitions/window-types';
import StaffPermission from './components/ManagePermissions';
import CreateStaff from './components/CreateStaff';
import { Fill } from '@wordpress/components';
import { registerPlugin } from '@wordpress/plugins';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { DokanButton } from '@dokan/components';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import { usePermission } from '@dokan/hooks';

const AddButton = () => {
    const isStaff = usePermission( 'vendor_staff' );
    if ( isStaff ) {
        return null;
    }
    return (
        <Fill name="dokan-header-actions">
            { ( { navigate } ) => (
                <DokanButton onClick={ () => navigate( '/staffs/create' ) }>
                    { __( 'Add New Staff', 'dokan' ) }
                </DokanButton>
            ) }
        </Fill>
    );
};

registerPlugin( 'dokan-vendor-staff', {
    render: AddButton,
    scope: 'dokan-vendor-staff',
} );

domReady( () => {
    // Add the staff list component to the vendor staff route
    window.wp.hooks.addFilter(
        'dokan-dashboard-routes',
        'dokan-vendor-staff',
        function ( routes = [] ) {
            routes.push( {
                id: 'dokan-vendor-staff',
                path: 'staffs',
                title: __( 'Staff', 'dokan' ),
                exact: true,
                order: 53,
                parent: '',
                element: StaffList,
            } );
            return routes;
        }
    );

    // create staff
    window.wp.hooks.addFilter(
        'dokan-dashboard-routes',
        'dokan-create-staff',
        function ( routes = [] ) {
            routes.push( {
                id: 'dokan-create-staff',
                path: 'staffs/create',
                title: __( 'Create Staff', 'dokan' ),
                exact: true,
                order: 53,
                parent: '',
                element: CreateStaff,
                backUrl: '/staffs',
            } );
            return routes;
        }
    );

    // edit staff by id route
    window.wp.hooks.addFilter(
        'dokan-dashboard-routes',
        'dokan-update-staff',
        function ( routes = [] ) {
            routes.push( {
                id: 'dokan-update-staff',
                path: 'staffs/update/:id',
                title: __( 'Edit Staff', 'dokan' ),
                exact: true,
                order: 53,
                parent: '',
                element: CreateStaff,
                backUrl: '/staffs',
            } );
            return routes;
        }
    );

    // manage permissions route
    window.wp.hooks.addFilter(
        'dokan-dashboard-routes',
        'dokan-manage-staff-permissions',
        function ( routes = [] ) {
            routes.push( {
                id: 'dokan-manage-staff-permissions',
                path: 'staffs/permissions/:id',
                title: __( 'Manage Permissions', 'dokan' ),
                exact: true,
                order: 53,
                parent: '',
                element: StaffPermission,
                backUrl: '/staffs',
            } );
            return routes;
        }
    );
} );
