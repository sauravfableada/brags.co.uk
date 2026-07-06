import type { GeneralSummary } from '../types/analytics';

interface StatsSidebarProps {
    summary: GeneralSummary[];
    isLoading: boolean;
}

export default function StatsSidebar( {
    summary,
    isLoading,
}: StatsSidebarProps ) {
    if ( isLoading ) {
        return (
            <div className="space-y-3">
                { Array.from( { length: 6 } ).map( ( _, i ) => (
                    <div
                        key={ i }
                        className="h-12 bg-gray-100 rounded animate-pulse"
                    />
                ) ) }
            </div>
        );
    }

    if ( ! summary || summary.length === 0 ) {
        return null;
    }

    return (
        <ul className="space-y-2 list-none m-0 p-0">
            { summary.map( ( item ) => (
                <li
                    key={ item.key }
                    className="flex items-center justify-between p-3 bg-gray-50 rounded border border-gray-200 border-solid"
                >
                    <span className="text-sm text-gray-600">
                        { item.label }
                    </span>
                    <strong className="text-base text-gray-900">
                        { item.value }
                    </strong>
                </li>
            ) ) }
        </ul>
    );
}
