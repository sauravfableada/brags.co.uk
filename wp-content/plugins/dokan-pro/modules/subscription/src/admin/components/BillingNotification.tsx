import { __, sprintf } from '@wordpress/i18n';
import { dateI18n, getSettings } from '@wordpress/date';
import { Info } from 'lucide-react';
import { RawHTML } from '@wordpress/element';

const BillingNotification = ( {
    date,
    type,
}: {
    date: string;
    type: string;
} ) => {
    const lastBilling = sprintf(
        // translators: date
        __( 'Last billing date is %1$s', 'dokan' ),
        date
    );

    const nextBilling = sprintf(
        // translators: date
        __( 'Next billing date is %1$s', 'dokan' ),
        dateI18n(
            getSettings().formats.date,
            date,
            getSettings().timezone.string
        )
    );
    return (
        <div className="flex justify-center items-center gap-3 w-full">
            <Info className="w-5 h-5 text-[#828282] flex-shrink-0" />
            <p className="text-gray-500 text-sm font-medium">
                <RawHTML>
                    { type === 'last' ? lastBilling : nextBilling }
                </RawHTML>
            </p>
        </div>
    );
};

export default BillingNotification;
