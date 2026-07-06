import { PriceHtml } from '@dokan/components';
import '../../../../src/definitions/window-types';

type PropType = {
    shippingLines;
    currency: string;
};
function ShippingLineItems( { shippingLines, currency }: PropType ) {
    if ( shippingLines.length < 1 ) {
        return null;
    }

    const shippingLineHtml = () => {
        return shippingLines.map( ( shippingLine ) => (
            <div
                className="grid grid-cols-6 gap-4 border-t"
                key={ shippingLine.id }
            >
                <div className="col-span-3 py-2 px-4">
                    <span className="flex items-center gap-2">
                        { shippingLine.method_title }
                    </span>
                </div>
                <div className="col-span-3 text-right py-2 px-4">
                    <PriceHtml
                        price={ shippingLine.total }
                        currencySymbol={
                            window.dokanProductSubscription.currencySymbols[
                                currency
                            ]
                        }
                    />
                </div>
            </div>
        ) );
    };

    return shippingLineHtml();
}

export default ShippingLineItems;
