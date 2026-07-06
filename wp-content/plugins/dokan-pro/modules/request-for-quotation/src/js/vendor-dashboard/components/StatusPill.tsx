import { twMerge } from 'tailwind-merge';
import { getStatusLabel } from '../hooks/useStatusFilters';

const STATUS_STYLES: Record< string, string > = {
    pending  : 'bg-[#FAEDCD] text-[#8A610F] border-[#8A610F]',
    converted: 'bg-[#D4FBEF] text-[#00563F] border-[#00563F]',
    accepted : 'bg-[#C0E1F8] text-[#1B6384] border-[#1B6384]',
    cancel   : 'bg-[#F8E3E6] text-[#9F2225] border-[#9F2225]',
    approve  : 'bg-[#EFEAFF] text-[#461ACA] border-[#461ACA]',
    expired  : 'bg-[#F1F1F4] text-[#393939] border-[#393939]',
    draft    : 'bg-[#F1F1F4] text-[#393939] border-[#393939]',
    trash    : 'bg-[#F1F1F4] text-[#393939] border-[#393939]',
    reject   : 'bg-[#FDF2F8] text-[#9D174D] border-[#9D174D]',
    updated  : 'bg-[#DBEAFE] text-[#2947BF] border-[#2947BF]',
};

const StatusPill = ( { value, id = 0 }: { value: string; id?: number } ) => (
    <div className="inline-flex items-center min-w-48">
        <span
            className={ twMerge(
                'px-3 py-1 rounded-full text-xs font-medium',
                STATUS_STYLES[ value ] || 'bg-gray-100 text-gray-700',
                ! id && 'border-none'
            ) }
        >
            { getStatusLabel( value as any ) }
        </span>
    </div>
);

export default StatusPill;
