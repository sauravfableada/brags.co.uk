import { __ } from '@wordpress/i18n';

interface TableSkeletonProps {
    columns?: number;
    rows?: number;
}

/**
 * Get width class based on column index
 *
 * @param {number} index Column index
 * @return {string} Width class
 */
const getWidthClass = ( index: number ): string => {
    if ( index === 0 ) {
        return 'w-4';
    } else if ( index === 1 ) {
        return 'w-8';
    } else if ( index === 2 ) {
        return 'w-20';
    }
    return 'w-12';
};

/**
 * Get height class based on column index and total columns
 *
 * @param {number} index Column index
 * @param {number} total Total columns
 * @return {string} Height class
 */
const getHeightClass = ( index: number, total: number ): string => {
    if ( index > 1 && index < total - 2 ) {
        return 'h-8';
    }
    return 'h-4';
};

const TableSkeleton = ( {
    columns = 10,
    rows = 4,
}: TableSkeletonProps ): JSX.Element => {
    // Generate header cells
    const headerCells = [];
    for ( let i = 0; i < columns; i++ ) {
        headerCells.push(
            <div
                key={ `header-${ i }` }
                className={ `h-4 ${ getWidthClass( i ) } bg-gray-200 rounded` }
            />
        );
    }

    // Generate rows
    const tableRows = [];
    for ( let r = 0; r < rows; r++ ) {
        const rowCells = [];
        for ( let c = 0; c < columns; c++ ) {
            rowCells.push(
                <div
                    key={ `cell-${ r }-${ c }` }
                    className={ `${ getHeightClass(
                        c,
                        columns
                    ) } ${ getWidthClass( c ) } bg-gray-200 rounded` }
                />
            );
        }
        tableRows.push(
            <div
                key={ `row-${ r }` }
                className={ `grid grid-cols-${ columns } gap-1 p-2 border-t border-gray-200 items-center` }
            >
                { rowCells }
            </div>
        );
    }

    return (
        <div className="mt-6 w-full space-y-6 animate-pulse">
            <div className="space-y-4">
                { /* Rates Title Skeleton */ }
                <div className="space-y-4 mb-4">
                    <div className="h-4 w-full bg-gray-200 rounded animate-pulse" />
                    <div className="h-4 w-[80%] bg-gray-200 rounded animate-pulse" />
                </div>

                { /* Table Skeleton */ }
                <div className="border border-gray-200 rounded-lg overflow-hidden">
                    { /* Table Header */ }
                    <div
                        className={ `grid grid-cols-${ columns } gap-1 p-2 bg-gray-50` }
                    >
                        { headerCells }
                    </div>

                    { /* Table Rows */ }
                    { tableRows }
                </div>

                { /* Action Buttons Skeleton */ }
                <div className="flex space-x-4 mt-4 justify-end">
                    <div className="h-10 w-32 bg-gray-200 rounded" />
                    <div className="h-10 w-40 bg-gray-200 rounded" />
                    <div className="h-10 w-36 bg-gray-200 rounded" />
                    <div className="h-10 w-36 bg-gray-200 rounded" />
                </div>
            </div>
        </div>
    );
};

export default TableSkeleton;
