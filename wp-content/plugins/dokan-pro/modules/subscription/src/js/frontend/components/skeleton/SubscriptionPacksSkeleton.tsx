const SubscriptionPacksSkeleton = () => {
    return (
        <div className="grid md:grid-cols-2 gap-5 place-items-center">
            { [ ...Array( 6 ) ].map( ( _, index ) => (
                <div
                    key={ index }
                    className="border p-4 rounded-md shadow-sm animate-pulse flex flex-col items-center justify-center text-center w-full max-w-md"
                >
                    <div className="h-6 bg-gray-300 rounded w-2/3 mb-3"></div>
                    <div className="h-8 bg-gray-300 rounded w-1/2 mb-4"></div>
                    <div className="h-4 bg-gray-300 rounded w-full mb-2"></div>
                    <div className="h-4 bg-gray-300 rounded w-3/4 mb-2"></div>
                    <div className="h-4 bg-gray-300 rounded w-1/2 mb-4"></div>
                    <div className="h-10 bg-gray-300 rounded w-full"></div>
                </div>
            ) ) }
        </div>
    );
};

export default SubscriptionPacksSkeleton;
