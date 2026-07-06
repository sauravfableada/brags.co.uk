import { __ } from '@wordpress/i18n';
import { PriceHtml, DokanTooltip as Tooltip } from '@dokan/components';
import { twMerge } from 'tailwind-merge';

function Shipping( {
    amount,
    shippingLines,
}: {
    amount: number;
    shippingLines;
} ) {
    if ( shippingLines.length === 0 ) {
        return null;
    }

    return (
        <div className="flex justify-between px-4">
            <div>
                { /* eslint-disable-next-line jsx-a11y/label-has-associated-control */ }
                <label className="text-sm text-gray-500 mb-1">
                    { __( 'Shipping', 'dokan' ) }
                </label>
                &nbsp;
                <Tooltip
                    content={ __(
                        'This is the shipping and handling total costs for the order.',
                        'dokan'
                    ) }
                    direction="top"
                    contentClass={ twMerge(
                        '',
                        'bg-gray-800 text-white p-2 rounded-md'
                    ) }
                >
                    <span className="fa fa-question-circle dokan-vendor-order-page-tips text-sm"></span>
                </Tooltip>
                :
            </div>

            <span className="flex">
                <PriceHtml price={ amount } />
            </span>
        </div>
    );
}

export default Shipping;
