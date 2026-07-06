import { PriceHtml } from '@dokan/components';
import { DokanSubscription } from '../Types';
import '../../../../src/definitions/window-types';
import { __ } from '@wordpress/i18n';
import { Image } from 'lucide-react';

type SubscriptionLineItems = {
    subscription: DokanSubscription;
};
function SubscriptionLineItems( { subscription }: SubscriptionLineItems ) {
    const LineItemsHtml = () => {
        return subscription.line_items.map( ( lineItem ) => {
            return (
                <div
                    className="grid grid-cols-12 border-t py-2 px-4"
                    key={ lineItem.id }
                >
                    <div className="col-span-4 text-left">
                        <div className="flex items-center gap-2">
                            { lineItem.image.src ? (
                                <img
                                    src={ lineItem.image.src ?? '#' }
                                    alt="Product"
                                    className="w-12 h-12"
                                />
                            ) : (
                                <div className="min-w-12 min-h-12 flex justify-center items-center border bg-gray-50 rounded">
                                    <Image />
                                </div>
                            ) }

                            <span>{ lineItem.name }</span>
                        </div>
                    </div>
                    <div className="col-span-2 text-left flex items-center">
                        <PriceHtml
                            price={ lineItem.price }
                            currencySymbol={
                                window.dokanProductSubscription.currencySymbols[
                                    subscription.currency
                                ]
                            }
                        />
                    </div>
                    <div className="col-span-2 text-center flex items-center justify-center">
                        { lineItem.quantity }
                    </div>
                    <div className="col-span-2 text-right flex flex-row-reverse items-center">
                        <PriceHtml
                            price={ lineItem.subtotal }
                            currencySymbol={
                                window.dokanProductSubscription.currencySymbols[
                                    subscription.currency
                                ]
                            }
                        />
                        { Number( lineItem.subtotal ) !==
                            Number( lineItem.total ) && (
                            <span className="text-sm text-gray-500">
                                <PriceHtml
                                    price={
                                        Number( lineItem.subtotal ) -
                                        Number( lineItem.total )
                                    }
                                    currencySymbol={
                                        window.dokanProductSubscription
                                            .currencySymbols[
                                            subscription.currency
                                        ]
                                    }
                                />
                                &nbsp;
                                { __( 'discount', 'dokan' ) }
                            </span>
                        ) }
                    </div>
                    <div className="col-span-2 text-right flex flex-row-reverse items-center">
                        <PriceHtml
                            price={ lineItem.total_tax ?? 0 }
                            currencySymbol={
                                window.dokanProductSubscription.currencySymbols[
                                    subscription.currency
                                ]
                            }
                        />
                    </div>
                </div>
            );
        } );
    };
    return LineItemsHtml();
}

export default SubscriptionLineItems;
