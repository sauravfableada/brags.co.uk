const CurrentSubscriptionSkeleton = () => {
    return (
        <div className="animate-pulse">
            <div className="h-4 bg-gray-300 rounded w-1/3 mb-3"></div>
            <div className="h-4 bg-gray-300 rounded w-2/3 mb-2"></div>
            <div className="h-4 bg-gray-300 rounded w-1/2 mb-2"></div>
            <div className="h-4 bg-gray-300 rounded w-3/4 mb-2"></div>
            <div className="flex items-center justify-between border border-gray-200 rounded-md p-3.5 mt-5">
                <div className="h-4 bg-gray-300 rounded w-1/3 mb-3"></div>
                <div className="h-10 bg-gray-300 rounded w-24 mt-3"></div>
            </div>
        </div>
    );
};

export default CurrentSubscriptionSkeleton;
