interface SettingsSkeletonProps {
    sections?: Array< {
        title?: boolean;
        fields: number;
    } >;
    showSaveButton?: boolean;
}

/**
 * Common settings skeleton component for shipping settings forms
 *
 * @param {SettingsSkeletonProps} props Component props
 * @return {JSX.Element} Settings a skeleton component
 */
const SettingsSkeleton = ( {
    sections = [ { fields: 4 }, { fields: 3 } ],
    showSaveButton = true,
}: SettingsSkeletonProps ): JSX.Element => {
    return (
        <div className="space-y-6 mt-6">
            { sections.map( ( section, sectionIndex ) => (
                <div key={ `section-${ sectionIndex }` }>
                    { section.title !== false && (
                        <div className="space-y-4 mb-4">
                            <div className="h-4 w-full bg-gray-200 rounded animate-pulse" />
                            <div className="h-4 w-[80%] bg-gray-200 rounded animate-pulse" />
                        </div>
                    ) }
                    <div className="space-y-6">
                        { Array.from( { length: section.fields } ).map(
                            ( _, fieldIndex ) => (
                                <div
                                    key={ `field-${ sectionIndex }-${ fieldIndex }` }
                                    className="flex gap-6"
                                >
                                    <div className="h-full">
                                        <div className="flex items-center">
                                            <div className="h-10 w-32 bg-gray-200 rounded animate-pulse" />
                                            <div className="ml-2 h-8 w-8 bg-gray-200 rounded-full animate-pulse" />
                                        </div>
                                    </div>
                                    <div className="h-10 w-full bg-gray-200 rounded animate-pulse" />
                                </div>
                            )
                        ) }
                    </div>
                </div>
            ) ) }

            { showSaveButton && (
                <div className="flex justify-end">
                    <div className="h-10 w-40 bg-gray-200 rounded animate-pulse" />
                </div>
            ) }
        </div>
    );
};

export default SettingsSkeleton;
