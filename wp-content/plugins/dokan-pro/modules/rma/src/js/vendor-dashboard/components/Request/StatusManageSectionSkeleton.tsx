import { __ } from '@wordpress/i18n';

import { Card } from '@getdokan/dokan-ui';

export default function StatusManageSectionSkeleton() {
    return (
        <Card>
            <Card.Header className="px-4 py-2 flex justify-between items-center">
                <Card.Title className="p-0 m-0 mb-0">
                    { __( 'Status', 'dokan' ) }
                </Card.Title>
            </Card.Header>
            <Card.Body className="px-4 py-4">
                { /* Last Updated Section */ }
                <div className="flex justify-start mb-4">
                    <div className="text-gray-900 text-sm font-medium mr-2">
                        { __( 'Last Updated:', 'dokan' ) }
                    </div>
                    <div className="animate-pulse">
                        <div className="h-4 bg-gray-200 rounded w-32"></div>
                    </div>
                </div>

                { /* Status Dropdown Skeleton */ }
                <div className="mb-4">
                    <div className="text-gray-900 text-sm font-medium mb-1">
                        { __( 'Change Status', 'dokan' ) }
                    </div>
                    <div className="animate-pulse">
                        <div className="h-10 bg-gray-200 rounded w-full"></div>
                    </div>
                </div>

                { /* Buttons Section */ }
                <div className="w-full flex justify-between gap-6">
                    { /* Action Button Placeholder */ }
                    <div className="animate-pulse">
                        <div className="h-10 bg-gray-200 rounded w-32"></div>
                    </div>

                    { /* Update Button Placeholder */ }
                    <div className="animate-pulse">
                        <div className="h-10 bg-gray-200 rounded w-24"></div>
                    </div>
                </div>
            </Card.Body>
        </Card>
    );
}
