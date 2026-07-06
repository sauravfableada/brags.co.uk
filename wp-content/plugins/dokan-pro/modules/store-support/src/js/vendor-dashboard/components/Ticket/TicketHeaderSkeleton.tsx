export default function TicketHeaderSkeleton() {
    return (
        <div className="dokan-support-single-title space-y-4">
            { /* Header Skeleton */ }
            <div className="dokan-dss-chat-header bg-white border border-gray-300 rounded-lg p-4">
                <div className="flex justify-between items-start">
                    <div className="dokan-chat-title-box flex-1">
                        <div className="h-6 bg-gray-200 rounded w-64 mb-2 animate-pulse"></div>
                        <div className="h-4 bg-gray-200 rounded w-16 animate-pulse"></div>
                    </div>
                    <div className="dokan-chat-status-box">
                        <div className="h-6 bg-gray-200 rounded w-16 animate-pulse"></div>
                    </div>
                </div>
            </div>

            { /* Order Reference Skeleton */ }
            <div className="order-reference bg-gray-50 border border-gray-200 rounded-lg p-4">
                <div className="h-5 bg-gray-200 rounded w-48 animate-pulse"></div>
            </div>
        </div>
    );
}
