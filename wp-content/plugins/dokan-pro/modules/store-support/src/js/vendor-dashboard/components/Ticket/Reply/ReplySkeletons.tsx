export default function ReplySkeletons() {
    return (
        <div className="dokan-support-reply-section space-y-6">
            { /* Replies List Skeleton */ }
            <ul className="dokan-support-commentlist space-y-4 list-none p-0 m-0">
                { [ 1, 2, 3 ].map( ( index ) => (
                    <li
                        key={ index }
                        className="dokan-support-comment-item mb-6"
                    >
                        <div className="dokan-dss-chat-box bg-white border border-gray-300 rounded-lg p-4">
                            <div className="flex space-x-4">
                                { /* Avatar Skeleton */ }
                                <div className="dokan-chat-image-box flex-shrink-0">
                                    <div className="dokan-chat-image-container">
                                        <div className="dokan-chat-image w-12 h-12 bg-gray-200 rounded-full border border-gray-200 animate-pulse"></div>
                                    </div>
                                </div>

                                { /* Chat Content Skeleton */ }
                                <div className="dokan-chat-info-box flex-1">
                                    { /* Sender Info Skeleton */ }
                                    <div className="dokan-chat-sender-info mb-2">
                                        <div className="dokan-chat-user-box flex items-center space-x-2">
                                            <div className="chat-user h-4 bg-gray-200 rounded w-24 animate-pulse"></div>
                                            <div className="h-4 bg-gray-200 rounded w-16 animate-pulse"></div>
                                        </div>
                                    </div>

                                    { /* Message Content Skeleton */ }
                                    <div className="dokan-chat-text dokan-customer-chat-text mb-3">
                                        <div className="bg-gray-50 border border-gray-200 rounded-lg p-3">
                                            <div className="prose max-w-none space-y-2">
                                                <div className="h-3 bg-gray-200 rounded animate-pulse"></div>
                                                <div className="h-3 bg-gray-200 rounded w-4/5 animate-pulse"></div>
                                                <div className="h-3 bg-gray-200 rounded w-3/5 animate-pulse"></div>
                                                { index === 2 && (
                                                    <>
                                                        <div className="h-3 bg-gray-200 rounded w-2/3 animate-pulse"></div>
                                                        <div className="h-3 bg-gray-200 rounded w-1/2 animate-pulse"></div>
                                                    </>
                                                ) }
                                            </div>
                                        </div>
                                    </div>

                                    { /* Timestamp Skeleton */ }
                                    <div className="dokan-chat-time-box">
                                        <div className="dokan-chat-time flex items-center space-x-1">
                                            <div className="h-3 bg-gray-200 rounded w-20 animate-pulse"></div>
                                            <div className="human-diff h-3 bg-gray-200 rounded w-12 animate-pulse"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                ) ) }
            </ul>

            { /* Reply Form Panel Skeleton */ }
            <div className="dokan-panel dokan-panel-default dokan-dss-panel-default bg-white border border-gray-300 rounded-lg">
                { /* Panel Heading Skeleton */ }
                <div className="dokan-dss-panel-heading bg-gray-50 px-4 py-3 border-b border-gray-200">
                    <div className="flex items-center space-x-2">
                        <div className="h-5 bg-gray-200 rounded w-24 font-bold animate-pulse"></div>
                        <div className="h-3 bg-gray-200 rounded w-56 animate-pulse"></div>
                    </div>
                </div>

                { /* Panel Body Skeleton */ }
                <div className="dokan-dss-panel-body dokan-support-reply-form p-4">
                    <div className="space-y-4">
                        { /* Comment Form Skeleton */ }
                        <div className="comment-form-comment">
                            <div className="sr-only h-4 bg-gray-200 rounded w-32 mb-2 animate-pulse"></div>
                            <div className="dokan-dss-comment-textarea w-full h-24 bg-gray-200 rounded border border-gray-300 animate-pulse"></div>
                        </div>

                        { /* Status Change Section Skeleton */ }
                        <div className="status-change-section">
                            <div className="dokan-support-topic-select dokan-form-control w-1/3 h-10 bg-gray-200 rounded border border-gray-300 animate-pulse"></div>
                            <div className="clearfix"></div>
                        </div>

                        { /* Submit Button Section Skeleton */ }
                        <div className="flex justify-end">
                            <div className="submit dokan-btn dokan-btn-theme h-10 bg-gray-200 rounded w-32 animate-pulse"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
