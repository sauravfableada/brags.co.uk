const StoreSupportSingleSkeleton = () => {
    const renderSkeletonMessage = () => (
        <div className="flex items-start p-4">
            <div className="w-10 h-10 rounded-full bg-gray-200 mr-4 shrink-0"></div>
            <div className="flex-1">
                <div className="flex justify-between items-start mb-3">
                    <div>
                        <div className="h-4 bg-gray-200 rounded w-24 mb-2"></div>
                        <div className="h-3 bg-gray-200 rounded w-16"></div>
                    </div>
                    <div className="h-3 bg-gray-200 rounded w-20 self-center"></div>
                </div>
                <div className="space-y-2">
                    <div className="h-4 bg-gray-200 rounded"></div>
                    <div className="h-4 bg-gray-200 rounded w-5/6"></div>
                </div>
            </div>
        </div>
    );

    return (
        <div className="animate-pulse">
            { /* Back Button Skeleton */ }
            <div className="h-6 w-28 bg-gray-200 rounded mb-6"></div>

            { /* Ticket Title Skeleton */ }
            <div className="h-8 w-3/4 bg-gray-200 rounded mb-6"></div>

            <div className="flex gap-8">
                { /* Main Content Skeleton */ }
                <div className="flex-1 w-3/4 flex flex-col gap-4">
                    { /* Conversation Thread Skeleton */ }
                    <div className="divide-y bg-white divide-gray-200 border border-gray-200 rounded-md shadow-sm">
                        { renderSkeletonMessage() }
                        { renderSkeletonMessage() }
                    </div>

                    { /* Reply Box Skeleton */ }
                    <div className="mt-8">
                        <div className="h-5 w-32 bg-gray-200 rounded mb-2"></div>
                        <div className="bg-white rounded-lg shadow-sm">
                            <div className="h-40 bg-gray-50 rounded-t-lg"></div>
                            <div className="bg-gray-50 p-4 rounded-b-lg flex justify-end border-t border-gray-200">
                                <div className="h-10 w-40 bg-gray-200 rounded-md"></div>
                            </div>
                        </div>
                    </div>
                </div>

                { /* Sidebar Skeleton */ }
                <div className="w-1/4">
                    <div className="bg-white p-6 rounded-lg shadow-sm">
                        { /* Header Skeleton */ }
                        <div className="flex justify-between items-center mb-6">
                            <div className="h-7 w-32 bg-gray-200 rounded"></div>
                            <div className="h-9 w-24 bg-gray-200 rounded-md"></div>
                        </div>

                        { /* Ticket Details Skeleton */ }
                        <ul className="flex flex-col gap-4 text-sm mb-6">
                            { [...Array(5)].map((_, i) => (
                                <li key={i} className="flex items-center">
                                    <div className="w-5 h-5 mr-3 bg-gray-200 rounded shrink-0"></div>
                                    <div className="h-4 bg-gray-200 rounded w-20"></div>
                                    <div className="ml-auto h-4 bg-gray-200 rounded w-16"></div>
                                </li>
                            ))}
                        </ul>

                        { /* Divider */ }
                        <div className="border-t border-gray-200 my-6"></div>

                        { /* Email Notification Skeleton */ }
                        <div className="flex justify-between items-start">
                            <div>
                                <div className="h-5 w-32 bg-gray-200 rounded mb-1"></div>
                                <div className="h-3 w-40 bg-gray-200 rounded"></div>
                            </div>
                            <div className="w-12 h-6 bg-gray-200 rounded-full"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default StoreSupportSingleSkeleton;
