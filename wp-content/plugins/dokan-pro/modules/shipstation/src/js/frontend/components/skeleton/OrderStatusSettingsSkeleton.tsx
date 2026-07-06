const OrderStatusSettingsSkeleton = () => {
    return (
        <div className="w-full max-w-4xl">
            <div className="flex flex-col md:flex-row items-start mb-6 w-5/6">
                <div className="h-5 mr-2 mb-3 md:mb-0 bg-gray-200 rounded-md w-1/3 animate-pulse"></div>
                <div className="hidden md:inline-block mr-6 h-5 w-8 bg-gray-200 rounded-full animate-pulse"></div>
                <div className="h-10 bg-gray-200 rounded-md w-full animate-pulse"></div>
            </div>

            <div className="flex flex-col md:flex-row items-start mb-6 w-5/6">
                <div className="h-5 mr-2 mb-3 md:mb-0 bg-gray-200 rounded-md w-1/4 animate-pulse"></div>
                <div className="hidden md:inline-block mr-16 h-5 w-8 bg-gray-200 rounded-full animate-pulse"></div>
                <div className="h-10 bg-gray-200 rounded-md w-full animate-pulse"></div>
            </div>

            <div className="flex items-center mb-1 w-5/6">
                <div className="ml-0 md:ml-52 h-10 bg-gray-200 rounded-lg w-32 animate-pulse"></div>
            </div>
        </div>
    );
};

export default OrderStatusSettingsSkeleton;
