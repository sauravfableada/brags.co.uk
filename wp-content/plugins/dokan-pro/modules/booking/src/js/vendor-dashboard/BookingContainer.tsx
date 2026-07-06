import { Slot } from '@wordpress/components';
import { DokanToaster } from '@getdokan/dokan-ui';
import BookingListWrapper from './components/BookingListWrapper';
import type { ViewType } from './types';

const BookingContainer = ( {
    navigate,
    activeTab = 'products',
}: {
    navigate?: any;
    activeTab?: ViewType;
} ) => {
    return (
        <div id="dokan-booking-dashboard">
            <DokanToaster />
            <Slot name="dokan.booking.dashboard.header" />

            <BookingListWrapper
                navigate={ navigate }
                activeTab={ activeTab }
            />

            <Slot name="dokan.booking.dashboard.footer" />
        </div>
    );
};

export default BookingContainer;
