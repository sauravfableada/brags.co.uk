export function StatusFilterSkeleton() {
    return (
        <div className="flex items-center space-x-1">
            { [ ...Array( 4 ) ].map( ( _, index ) => (
                <div key={ index } className="flex items-center">
                    <div className="h-4 w-16 bg-gray-200 rounded animate-pulse" />
                    { index < 3 && (
                        <div className="border-r h-3 mx-1" aria-hidden="true" />
                    ) }
                </div>
            ) ) }
        </div>
    );
}
