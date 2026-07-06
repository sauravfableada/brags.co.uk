import { __ } from '@wordpress/i18n';
import { SimpleInput } from '@getdokan/dokan-ui';
import { TableRateSettingsData } from '../definations';

interface ClassPrioritiesProps {
    settings: TableRateSettingsData;
    handleSettingChange: ( key: string, value: any ) => void;
}

const ClassPrioritySettings = ( {
    settings,
    handleSettingChange,
}: ClassPrioritiesProps ) => {
    const classesPriorities = settings?.classes_priorities ?? [];
    const shippingClassList = dokanTableRateShippingHelper?.shipping_class;

    const getShippingClassList = () => {
        const classList = [];

        if ( ! shippingClassList?.length ) {
            return classList;
        }

        classList.push(
            {
                slug: 'default',
                className: __( 'Default', 'dokan' ),
                priority: settings?.default_priority || 10,
            },
            ...shippingClassList.map( ( classObj ) => ( {
                className: classObj?.name,
                slug: classObj?.slug,
                priority: classesPriorities?.[ classObj?.slug ] || 10,
            } ) )
        );

        return classList;
    };

    const getPriority = ( slug: string ) => {
        if ( slug === 'default' ) {
            return settings?.default_priority;
        }

        return slug in classesPriorities ? classesPriorities?.[ slug ] : 10;
    };

    return (
        <div className="bg-white sm:gap-4">
            <div className="py-6 px-1">
                <h3 className="text-base font-medium text-gray-900 mb-1">
                    { __( 'Class Priorities', 'dokan' ) }
                </h3>
                <p className="text-sm text-gray-500">
                    { dokanTableRateShippingHelper?.shipping_class?.length > 0
                        ? __( 'Set priorities for shipping classes.', 'dokan' )
                        : __( 'Priorities not set yet.', 'dokan' ) }
                </p>
            </div>
            { shippingClassList?.length > 0 && (
                <>
                    <div className="bg-white pb-2 sm:gap-4">
                        <dd className="text-sm text-gray-900 sm:col-span-3 sm:mt-0 pr-2">
                            <div className="relative overflow-x-auto shadow-md sm:rounded-lg">
                                <table
                                    id="shipping-class-table"
                                    className="dataviews-view-table has-background w-full text-sm text-left rtl:text-right text-gray-500"
                                >
                                    <thead>
                                        <tr>
                                            <th
                                                scope="col"
                                                className="px-6 py-3 bg-gray-50"
                                            >
                                                { __(
                                                    'Shipping Class',
                                                    'dokan'
                                                ) }
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-6 py-3 bg-gray-50 text-end"
                                            >
                                                { __( 'Priority', 'dokan' ) }
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        { getShippingClassList()?.map(
                                            ( item ) => (
                                                <tr
                                                    key={ item.slug }
                                                    className="border-t border-gray-200 hover:bg-gray-50"
                                                >
                                                    <td className="py-4 px-6 text-gray-900 align-middle">
                                                        { item.className }
                                                    </td>
                                                    <td className="py-4 px-6 text-end w-48">
                                                        <SimpleInput
                                                            type="number"
                                                            value={ getPriority(
                                                                item?.slug
                                                            ) }
                                                            className="bg-white focus:bg-white text-end"
                                                            onChange={ (
                                                                e
                                                            ) => {
                                                                const priority =
                                                                    parseInt(
                                                                        e.target
                                                                            .value
                                                                    ) || 0;
                                                                if (
                                                                    item?.slug ===
                                                                    'default'
                                                                ) {
                                                                    handleSettingChange(
                                                                        'default_priority',
                                                                        priority
                                                                    );
                                                                    return;
                                                                }
                                                                handleSettingChange(
                                                                    'classes_priorities',
                                                                    {
                                                                        ...classesPriorities,
                                                                        [ item?.slug ]:
                                                                            priority,
                                                                    }
                                                                );
                                                            } }
                                                        />
                                                    </td>
                                                </tr>
                                            )
                                        ) }
                                    </tbody>
                                </table>
                            </div>
                        </dd>
                    </div>

                    <p className="text-base text-gray-500 py-4 px-1">
                        { __(
                            'When calculating shipping, the cart contents will be searched for all shipping classes. If all product shipping classes are identical, the corresponding class will be used.',
                            'dokan'
                        ) }
                    </p>
                    <p className="text-base text-gray-500 px-1">
                        { __(
                            'If there are a mix of classes then the class with the lowest number priority (defined above) will be used.',
                            'dokan'
                        ) }
                    </p>
                </>
            ) }
        </div>
    );
};

export default ClassPrioritySettings;
