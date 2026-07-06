import { Slot } from '@wordpress/components';
import BookingList from './BookingList';
import type { ViewType } from '../types';

const BookingListWrapper = ( {
    navigate,
    activeTab = 'products',
}: {
    navigate: any;
    activeTab?: ViewType;
} ) => {
    return (
        <div>
            <Slot
                name="dokan.booking.dashboard.list.header"
                fillProps={ {
                    navigate,
                } }
            />
            <BookingList navigate={ navigate } activeTab={ activeTab } />
            <Slot
                name="dokan.booking.dashboard.list.footer"
                fillProps={ {
                    navigate,
                } }
            />
        </div>
    );
};

export default BookingListWrapper;
