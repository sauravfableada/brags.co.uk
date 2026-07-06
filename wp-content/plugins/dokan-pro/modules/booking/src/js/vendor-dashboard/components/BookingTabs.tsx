import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import dokanCore from '@dokan/stores/core';
// @ts-ignore
import { Tabs, TabsList, TabsTrigger } from '@wedevs/plugin-ui';
import type { ViewType } from '../types';

interface BookingTabsProps {
    activeTab: ViewType;
    navigate: ( path: string ) => void;
}

const BookingTabs = ( { activeTab, navigate }: BookingTabsProps ) => {
    const hasCapManageBookings = useSelect(
        ( select ) => select( dokanCore ).hasCap( 'dokan_manage_bookings' ),
        []
    );
    const hasCapManageResources = useSelect(
        ( select ) =>
            select( dokanCore ).hasCap( 'dokan_manage_booking_resource' ),
        []
    );
    const hasCapManageCalendar = useSelect(
        ( select ) =>
            select( dokanCore ).hasCap( 'dokan_manage_booking_calendar' ),
        []
    );

    const tabs: {
        key: ViewType;
        label: string;
        path: string;
        show: boolean;
    }[] = [
        {
            key: 'products',
            label: __( 'All Booking Product', 'dokan' ),
            path: '/booking',
            show: true,
        },
        {
            key: 'bookings',
            label: __( 'Manage Bookings', 'dokan' ),
            path: '/booking/my-bookings',
            show: hasCapManageBookings,
        },
        {
            key: 'calendar',
            label: __( 'Calendar', 'dokan' ),
            path: '/booking/calendar',
            show: hasCapManageCalendar,
        },
        {
            key: 'resources',
            label: __( 'Manage Resources', 'dokan' ),
            path: '/booking/resources',
            show: hasCapManageResources,
        },
    ];

    const visibleTabs = tabs.filter( ( tab ) => tab.show );

    return (
        <Tabs
            value={ activeTab }
            onValueChange={ ( value: ViewType ) => {
                const tab = visibleTabs.find( ( t ) => t.key === value );
                if ( tab ) {
                    navigate( tab.path );
                }
            } }
        >
            <TabsList variant="line">
                { visibleTabs.map( ( tab ) => (
                    <TabsTrigger
                        key={ tab.key }
                        value={ tab.key }
                        className="focus:outline-none text-base"
                    >
                        { tab.label }
                    </TabsTrigger>
                ) ) }
            </TabsList>
        </Tabs>
    );
};

export default BookingTabs;
