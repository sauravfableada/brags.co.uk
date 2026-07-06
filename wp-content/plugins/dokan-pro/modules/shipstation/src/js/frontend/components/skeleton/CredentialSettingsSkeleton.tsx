const CredentialSettingsSkeleton = () => {
    return (
        <div className="w-full max-w-4xl">
            <div className="mb-5 flex flex-col md:flex-row justify-between md:items-center">
                <div className="h-6 bg-gray-200 rounded-md w-2/3 animate-pulse mb-3 md:mb-0"></div>
                <div className="h-10 bg-gray-200 rounded-lg w-48 animate-pulse"></div>
            </div>

            <div className="flex flex-col md:flex-row items-start mb-6 w-3/4">
                <div className="h-5 mr-2 mb-3 md:mb-0 bg-gray-200 rounded-md w-5/12 animate-pulse"></div>
                <div className="hidden md:inline-block mr-8 h-5 w-8 bg-gray-200 rounded-full animate-pulse"></div>
                <div className="h-8 bg-gray-200 rounded-md w-full animate-pulse"></div>
                <div className="hidden md:inline-block ml-3 h-6 w-8 bg-gray-200 rounded-md animate-pulse"></div>
            </div>

            <div className="flex flex-col md:flex-row items-start mb-1 w-3/4">
                <div className="h-5 mr-2 mb-3 md:mb-0 bg-gray-200 rounded-md w-1/3 animate-pulse"></div>
                <div className="hidden md:inline-block mr-16 h-5 w-8 bg-gray-200 rounded-full animate-pulse"></div>
                <div className="h-8 bg-gray-200 rounded-md w-full animate-pulse"></div>
                <div className="hidden md:inline-block ml-3 h-6 w-8 bg-gray-200 rounded-md animate-pulse"></div>
            </div>
        </div>
    );
};

export default CredentialSettingsSkeleton;
