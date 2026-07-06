export default function StaffFormSkeleton() {
    return (
        <div>
            { /* First Name Field */ }
            <div className="mb-2">
                <div className="block text-sm font-medium mb-1">
                    <div className="animate-pulse">
                        <div className="h-4 bg-gray-200 rounded w-24"></div>
                    </div>
                </div>
                <div className="animate-pulse">
                    <div className="h-10 bg-gray-200 rounded w-full"></div>
                </div>
            </div>

            { /* Last Name Field */ }
            <div className="mb-2">
                <div className="block text-sm font-medium mb-1">
                    <div className="animate-pulse">
                        <div className="h-4 bg-gray-200 rounded w-24"></div>
                    </div>
                </div>
                <div className="animate-pulse">
                    <div className="h-10 bg-gray-200 rounded w-full"></div>
                </div>
            </div>

            { /* Email Address Field */ }
            <div className="mb-2">
                <div className="block text-sm font-medium mb-1">
                    <div className="animate-pulse">
                        <div className="h-4 bg-gray-200 rounded w-32"></div>
                    </div>
                </div>
                <div className="animate-pulse">
                    <div className="h-10 bg-gray-200 rounded w-full"></div>
                </div>
            </div>

            { /* Phone Number Field */ }
            <div className="mb-2">
                <div className="block text-sm font-medium mb-1">
                    <div className="animate-pulse">
                        <div className="h-4 bg-gray-200 rounded w-28"></div>
                    </div>
                </div>
                <div className="animate-pulse">
                    <div className="h-10 bg-gray-200 rounded w-full"></div>
                </div>
            </div>

            { /* Submit Button */ }
            <div className="pt-4 flex gap-4 justify-end">
                <div className="animate-pulse">
                    <div className="h-10 bg-gray-200 rounded w-32"></div>
                </div>
            </div>
        </div>
    );
}
