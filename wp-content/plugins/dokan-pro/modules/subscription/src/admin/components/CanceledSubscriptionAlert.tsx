import { Info } from 'lucide-react';
import { RawHTML } from '@wordpress/element';

function CanceledSubscriptionAlert( {message} ) {
    return (
        <div className="flex justify-center items-center gap-3 w-full">
            <Info className="w-5 h-5 text-[#828282] flex-shrink-0" />
            <p className="text-gray-500 text-sm font-medium">
                <RawHTML>
                    { message }
                </RawHTML>
            </p>
        </div>
    );
}

export default CanceledSubscriptionAlert;
