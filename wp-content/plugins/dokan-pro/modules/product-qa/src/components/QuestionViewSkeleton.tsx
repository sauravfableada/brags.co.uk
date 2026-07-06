import { Card } from '@getdokan/dokan-ui';

const Skeleton = ( {
    className = '',
    width = 'w-full',
}: {
    className?: string;
    width?: string;
} ) => (
    <div
        className={ `animate-pulse bg-gray-300 rounded ${ width } ${ className }` }
    ></div>
);

const QuestionViewSkeleton = () => {
    return (
        <>
            <div className="grid grid-cols-3 gap-4">
                <Card className="col-span-2">
                    <Card.Header>
                        <Skeleton className="h-6" width="w-32" />
                    </Card.Header>
                    <Card.Body>
                        <div className="space-y-4">
                            <div className="flex items-center space-x-2">
                                <Skeleton className="h-4" width="w-16" />
                                <Skeleton className="h-4" width="w-48" />
                            </div>
                            <div className="flex items-center space-x-2">
                                <Skeleton className="h-4" width="w-20" />
                                <Skeleton className="h-4" width="w-32" />
                            </div>
                            <div className="space-y-2">
                                <div className="flex items-center space-x-2">
                                    <Skeleton className="h-4" width="w-16" />
                                </div>
                                <Skeleton className="h-4" width="w-full" />
                                <Skeleton className="h-4" width="w-3/4" />
                            </div>
                        </div>
                    </Card.Body>
                </Card>
                <Card className="col-span-1">
                    <Card.Header>
                        <Skeleton className="h-6" width="w-16" />
                    </Card.Header>
                    <Card.Body>
                        <div className="space-y-2">
                            <div className="flex items-center space-x-2">
                                <Skeleton className="h-4" width="w-16" />
                                <Skeleton className="h-4" width="w-24" />
                            </div>
                            <div className="flex items-center space-x-2">
                                <Skeleton className="h-4" width="w-24" />
                                <Skeleton className="h-4" width="w-20" />
                            </div>
                        </div>
                        <Skeleton className="h-10 mt-4" width="w-full" />
                    </Card.Body>
                </Card>
            </div>
            <Card className="mt-4">
                <Card.Header>
                    <Skeleton className="h-6" width="w-16" />
                </Card.Header>
                <Card.Body className="flex flex-col gap-4">
                    <div className="space-y-3">
                        <Skeleton className="h-32" width="w-full" />
                        <div className="flex justify-end">
                            <Skeleton className="h-10" width="w-20" />
                        </div>
                    </div>
                </Card.Body>
            </Card>
        </>
    );
};

export default QuestionViewSkeleton;
