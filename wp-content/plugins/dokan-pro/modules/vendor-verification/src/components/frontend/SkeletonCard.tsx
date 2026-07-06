import { Card } from '@getdokan/dokan-ui';

const SkeletonCard = () => {
    return (
        <Card>
            <Card.Header>
                <div className="h-6 w-36 bg-gray-200 rounded animate-pulse"></div>
            </Card.Header>
            <Card.Body>
                <div className="space-y-4">
                    { /* PayPal Method */ }
                    <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-3">
                            <div className="h-8 w-8 bg-gray-200 rounded animate-pulse"></div>
                            <div>
                                <div className="h-4 w-16 bg-gray-200 rounded animate-pulse mb-2"></div>
                                <div className="h-4 w-36 bg-gray-200 rounded animate-pulse"></div>
                            </div>
                        </div>
                        <div className="h-10 w-20 bg-gray-200 rounded animate-pulse"></div>
                    </div>

                    { /* Bank Transfer Method */ }
                    <div className="flex items-center justify-between border-t pt-4">
                        <div className="flex items-center space-x-3">
                            <div className="h-8 w-8 bg-gray-200 rounded animate-pulse"></div>
                            <div>
                                <div className="h-4 w-24 bg-gray-200 rounded animate-pulse mb-2"></div>
                                <div className="h-4 w-48 bg-gray-200 rounded animate-pulse"></div>
                            </div>
                        </div>
                        <div className="h-10 w-20 bg-gray-200 rounded animate-pulse"></div>
                    </div>
                </div>
            </Card.Body>
        </Card>
    );
};

export default SkeletonCard;
