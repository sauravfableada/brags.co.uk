import { __ } from '@wordpress/i18n';
import { DokanBadge, DokanTooltip as Tooltip } from '@dokan/components';
import { formatPrice } from '@dokan/utilities';
import { twMerge } from 'tailwind-merge';

function CouponLineItems( { couponLines } ) {
    const couponHtml = () => {
        return couponLines.map( ( coupon ) => {
            return (
                <div key={ coupon.code }>
                    <DokanBadge variant="info" label={ coupon.code } />
                    <Tooltip
                        content={ formatPrice( coupon.discount ) }
                        direction="top"
                        contentClass={ twMerge(
                            '',
                            'bg-gray-800 text-white p-2 rounded-md'
                        ) }
                    >
                        <span className="fa fa-question-circle dokan-vendor-order-page-tips text-sm ml-1"></span>
                    </Tooltip>
                </div>
            );
        } );
    };

    if ( ! couponLines.length ) {
        return null;
    }

    return (
        <div className="mt-4 space-y-2 border-t pt-4">
            <div className="flex flex-col px-4">
                <div>
                    { /* eslint-disable-next-line jsx-a11y/label-has-associated-control */ }
                    <label className="block text-sm text-gray-500 mb-1">
                        <strong>{ __( 'Coupon(s)', 'dokan' ) }</strong>
                    </label>
                </div>
                <div
                    className={ twMerge(
                        'flex flex-row gap-2',
                        couponLines.length ? 'mb-3' : ''
                    ) }
                >
                    { couponHtml() }
                </div>
            </div>
        </div>
    );
}

export default CouponLineItems;
