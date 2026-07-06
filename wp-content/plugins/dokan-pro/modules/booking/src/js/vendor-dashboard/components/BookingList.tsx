import { Fill, Slot } from '@wordpress/components';
import BookingCalendarPage from './BookingCalendarPage';
import BookingTabs from './BookingTabs';
import ProductsTab from './ProductsTab';
import BookingsTab from './BookingsTab';
import ResourcesTab from './ResourcesTab';
import type { ViewType } from '../types';

const BookingList = ( {
    navigate,
    activeTab = 'products',
}: {
    navigate: any;
    activeTab?: ViewType;
} ) => {
    let activeTabContent = <ProductsTab />;

    if ( activeTab === 'calendar' ) {
        activeTabContent = <BookingCalendarPage />;
    } else if ( activeTab === 'bookings' ) {
        activeTabContent = <BookingsTab />;
    } else if ( activeTab === 'resources' ) {
        activeTabContent = <ResourcesTab />;
    }

    return (
        <>
            <Fill name="dokan-before-header">
                <div className="col-span-4">
                    <BookingTabs
                        activeTab={ activeTab }
                        navigate={ navigate }
                    />
                </div>
            </Fill>
            <Slot name="dokan.booking.list.before" />
            { activeTabContent }
            <Slot name="dokan.booking.list.after" />
        </>
    );
};

export default BookingList;
