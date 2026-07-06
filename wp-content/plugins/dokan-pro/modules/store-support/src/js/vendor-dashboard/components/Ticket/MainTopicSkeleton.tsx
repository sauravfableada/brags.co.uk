export default function MainTopicSkeleton() {
    return (
        <div className="dokan-dss-chat-box main-topic bg-white border border-gray-300 rounded-lg p-6">
            <div className="flex space-x-4">
                { /* Avatar Skeleton */ }
                <div className="dokan-chat-image-box flex-shrink-0">
                    <div className="w-20 h-20 bg-gray-200 rounded-full animate-pulse"></div>
                </div>

                { /* Content Skeleton */ }
                <div className="dokan-chat-info-box flex-1">
                    <div className="dokan-chat-sender-info mb-3">
                        <div className="h-5 bg-gray-200 rounded w-32 mb-1 animate-pulse"></div>
                        <div className="h-4 bg-gray-200 rounded w-48 animate-pulse"></div>
                    </div>

                    <div className="dokan-chat-text mb-4">
                        <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <div className="space-y-2">
                                <div className="h-4 bg-gray-200 rounded animate-pulse"></div>
                                <div className="h-4 bg-gray-200 rounded w-3/4 animate-pulse"></div>
                                <div className="h-4 bg-gray-200 rounded w-1/2 animate-pulse"></div>
                            </div>
                        </div>
                    </div>

                    <div className="dokan-chat-time-box">
                        <div className="h-3 bg-gray-200 rounded w-24 animate-pulse"></div>
                    </div>
                </div>
            </div>
        </div>
    );
}
