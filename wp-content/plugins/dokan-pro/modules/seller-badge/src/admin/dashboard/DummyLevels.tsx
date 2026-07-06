import { __ } from '@wordpress/i18n';

export default function DummyLevels() {
    return (
        <div className="dokan-logical-container mt-[11px]">
            <ul className="list-none p-0 m-0">
                <li className="flex items-center gap-[63px] mb-[20px]">
                    <div className="rounded-2xl px-4 py-1.5 inline-block text-xs font-semibold !text-[#575757] border border-[#E9E9E9]">
                        { __( 'Level 1', 'dokan' ) }
                    </div>
                    <div className="w-[141px] h-[3px] rounded-full bg-[#E9E9E9]"></div>
                    <div className="rounded-2xl px-4 py-1.5 inline-block  border border-[#E9E9E9]">
                        <div className="w-[214px] h-[1px] rounded-full bg-[#E9E9E9] m-[10px]"></div>
                    </div>
                </li>
                <li className="flex items-center gap-[63px] mb-[20px]">
                    <div className="rounded-2xl px-4 py-1.5 inline-block text-xs font-semibold !text-[#575757] border border-[#E9E9E9]">
                        { __( 'Level 2', 'dokan' ) }
                    </div>
                    <div className="w-[141px] h-[3px] rounded-full bg-[#E9E9E9]"></div>
                    <div className="rounded-2xl px-4 py-1.5 inline-block  border border-[#E9E9E9]">
                        <div className="w-[214px] h-[1px] rounded-full bg-[#E9E9E9] m-[10px]"></div>
                    </div>
                </li>
                <li className="flex items-center gap-[63px] mb-[20px]">
                    <div className="rounded-2xl px-4 py-1.5 inline-block text-xs font-semibold !text-[#575757] border border-[#E9E9E9]">
                        { __( 'Level 3', 'dokan' ) }
                    </div>
                    <div className="w-[141px] h-[3px] rounded-full bg-[#E9E9E9]"></div>
                    <div className="rounded-2xl px-4 py-1.5 inline-block  border border-[#E9E9E9]">
                        <div className="w-[214px] h-[1px] rounded-full bg-[#E9E9E9] m-[10px]"></div>
                    </div>
                </li>
            </ul>
        </div>
    );
}
