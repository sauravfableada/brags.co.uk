import { __, sprintf } from '@wordpress/i18n';
import { RawHTML } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { PaymentDescriptionProps } from '../../types';

const PaymentDescription = ( {
    className = '',
    isTestMode = false,
    description = '',
}: PaymentDescriptionProps ) => {
    return (
        <div
            className={ `bg-gray-50 p-4 rounded-lg border border-gray-200 mb-4 ${ className }` }
        >
            { /* Gateway Description */ }
            <div className="flex items-start gap-3 mb-4 text-justify">
                <p className="text-gray-700">
                    { decodeEntities( description ) }
                </p>
            </div>

            { /* Test Card Information */ }
            { isTestMode && (
                <div className="space-y-4 text-justify">
                    <RawHTML>
                        { sprintf(
                            /* translators: 1) opening strong tag, 2) closing strong tag, 3) opening anchor tag with link to stripe testing doc, 4) closing anchor tag  */
                            __(
                                '%1$sTest mode:%2$s use the test VISA card 4242424242424242 with any expiry date and CVC. Other payment methods may redirect to a Stripe test page to authorize payment. For example, 4000002500003155 is a 3D secure test card. More test card numbers are listed %3$shere%4$s.',
                                'dokan'
                            ),
                            '<strong>',
                            '</strong>',
                            '<a href="https://stripe.com/docs/testing" target="_blank">',
                            '</a>'
                        ) }
                    </RawHTML>
                </div>
            ) }
        </div>
    );
};

export default PaymentDescription;
