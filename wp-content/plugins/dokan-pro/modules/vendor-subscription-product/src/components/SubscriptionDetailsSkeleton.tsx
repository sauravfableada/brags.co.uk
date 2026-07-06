const SubscriptionDetailsSkeleton = () => {
    return (
        <div className="grid md:grid-cols-1 lg:grid-cols-3 gap-3">
            { /* Left Column */ }
            <div className="md:col-span-3 lg:col-span-2 space-y-3">
                { /* Order Items Card */ }
                <div className="bg-white border rounded-lg">
                    <div className="border-b border-gray-200 p-3">
                        <div className="h-6 w-48 bg-gray-200 rounded animate-pulse"></div>
                    </div>
                    <div className="p-3">
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b">
                                        { [ ...Array( 4 ) ].map( ( _, i ) => (
                                            <th key={ i } className="py-2 px-4">
                                                <div className="h-4 bg-gray-200 rounded animate-pulse"></div>
                                            </th>
                                        ) ) }
                                    </tr>
                                </thead>
                                <tbody>
                                    { [ ...Array( 2 ) ].map( ( _, i ) => (
                                        <tr key={ i } className="border-b">
                                            <td className="py-4 px-4">
                                                <div className="flex mt-[10px] items-center gap-3">
                                                    <div className="w-12 h-12 bg-gray-200 rounded animate-pulse"></div>
                                                    <div className="h-4 w-24 bg-gray-200 rounded animate-pulse"></div>
                                                </div>
                                            </td>
                                            <td className="py-4 px-4">
                                                <div className="h-4 w-16 bg-gray-200 rounded animate-pulse"></div>
                                            </td>
                                            <td className="py-4 px-4">
                                                <div className="h-4 w-8 bg-gray-200 rounded animate-pulse"></div>
                                            </td>
                                            <td className="py-4 px-4">
                                                <div className="h-4 w-20 bg-gray-200 rounded animate-pulse ml-auto"></div>
                                            </td>
                                        </tr>
                                    ) ) }
                                </tbody>
                                <tfoot>
                                    { [ ...Array( 4 ) ].map( ( _, i ) => (
                                        <tr key={ i } className="border-b">
                                            <td
                                                colSpan={ 3 }
                                                className="py-3 px-4"
                                            >
                                                <div className="h-4 w-24 bg-gray-200 rounded animate-pulse"></div>
                                            </td>
                                            <td className="py-3 px-4">
                                                <div className="h-4 w-20 bg-gray-200 rounded animate-pulse ml-auto"></div>
                                            </td>
                                        </tr>
                                    ) ) }
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                { /* Address Cards */ }
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                    { [ ...Array( 2 ) ].map( ( _, i ) => (
                        <div key={ i } className="bg-white border rounded-lg">
                            <div className="border-b border-gray-200 p-3">
                                <div className="h-6 w-32 bg-gray-200 rounded animate-pulse"></div>
                            </div>
                            <div className="p-3 space-y-3">
                                { [ ...Array( 6 ) ].map( ( _, j ) => (
                                    <div
                                        key={ j }
                                        className="h-4 w-full bg-gray-200 rounded animate-pulse"
                                    ></div>
                                ) ) }
                            </div>
                        </div>
                    ) ) }
                </div>

                { /* Downloadable Product Permission */ }
                <div className="bg-white border rounded-lg">
                    <div className="border-b border-gray-200 p-3">
                        <div className="h-6 w-64 bg-gray-200 rounded animate-pulse"></div>
                    </div>
                    <div className="p-3 space-y-4">
                        <div className="h-10 w-full bg-gray-200 rounded animate-pulse"></div>
                        <div className="h-10 w-32 bg-gray-200 rounded animate-pulse"></div>
                    </div>
                </div>

                { /* Related Orders */ }
                <div className="bg-white border rounded-lg">
                    <div className="border-b border-gray-200 p-3">
                        <div className="h-6 w-40 bg-gray-200 rounded animate-pulse"></div>
                    </div>
                    <div className="p-3">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b">
                                    { [ ...Array( 5 ) ].map( ( _, i ) => (
                                        <th key={ i }>
                                            <div className="h-4 pt-[10px] mb-[10px] bg-gray-200 rounded animate-pulse"></div>
                                        </th>
                                    ) ) }
                                </tr>
                            </thead>
                            <tbody>
                                { [ ...Array( 3 ) ].map( ( _, i ) => (
                                    <tr key={ i } className="border-b">
                                        { [ ...Array( 5 ) ].map( ( _, j ) => (
                                            <td key={ j } className="py-4">
                                                <div className="h-4 mt-[10px] pb-[10px] bg-gray-200 rounded animate-pulse"></div>
                                            </td>
                                        ) ) }
                                    </tr>
                                ) ) }
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            { /* Right Column */ }
            <div className="md:col-span-3 lg:col-span-1 space-y-3">
                { /* General Details */ }
                <div className="bg-white border rounded-lg">
                    <div className="border-b border-gray-200 p-3">
                        <div className="h-6 w-32 bg-gray-200 rounded animate-pulse"></div>
                    </div>
                    <div className="p-3 space-y-4">
                        <div className="flex justify-between items-center">
                            <div className="h-4 w-32 bg-gray-200 rounded animate-pulse"></div>
                            <div className="h-6 w-24 bg-gray-200 rounded-full animate-pulse"></div>
                        </div>
                        { [ ...Array( 5 ) ].map( ( _, i ) => (
                            <div key={ i } className="space-y-1">
                                <div className="h-4 w-24 bg-gray-200 rounded animate-pulse"></div>
                                <div className="h-4 w-48 bg-gray-200 rounded animate-pulse"></div>
                            </div>
                        ) ) }
                    </div>
                </div>

                { /* Subscription Schedule */ }
                <div className="bg-white border rounded-lg">
                    <div className="border-b border-gray-200 p-3">
                        <div className="h-6 w-48 bg-gray-200 rounded animate-pulse"></div>
                    </div>
                    <div className="p-3 space-y-4">
                        { [ ...Array( 3 ) ].map( ( _, i ) => (
                            <div key={ i } className="space-y-2">
                                <div className="h-4 w-24 bg-gray-200 rounded animate-pulse"></div>
                                <div className="h-10 w-full bg-gray-200 rounded animate-pulse"></div>
                            </div>
                        ) ) }
                        <div className="h-10 w-full bg-gray-200 rounded animate-pulse"></div>
                    </div>
                </div>

                { /* Subscription Notes */ }
                <div className="bg-white border rounded-lg">
                    <div className="border-b border-gray-200 p-3">
                        <div className="h-6 w-40 bg-gray-200 rounded animate-pulse"></div>
                    </div>
                    <div className="p-3 space-y-4">
                        { [ ...Array( 3 ) ].map( ( _, i ) => (
                            <div key={ i } className="p-3 bg-gray-50 rounded">
                                <div className="h-4 w-full bg-gray-200 rounded animate-pulse"></div>
                                <div className="mt-2 flex justify-between">
                                    <div className="h-4 w-24 bg-gray-200 rounded animate-pulse"></div>
                                    <div className="h-4 w-20 bg-gray-200 rounded animate-pulse"></div>
                                </div>
                            </div>
                        ) ) }
                        <div className="space-y-3">
                            <div className="h-24 w-full bg-gray-200 rounded animate-pulse"></div>
                            <div className="h-10 w-full bg-gray-200 rounded animate-pulse"></div>
                            <div className="h-10 w-24 bg-gray-200 rounded animate-pulse"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default SubscriptionDetailsSkeleton;
