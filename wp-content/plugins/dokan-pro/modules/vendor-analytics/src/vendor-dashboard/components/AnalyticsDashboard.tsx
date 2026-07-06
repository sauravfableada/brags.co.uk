import { __ } from '@wordpress/i18n';
import { useState, useEffect, useCallback } from '@wordpress/element';
import {
    Tabs,
    TabsList,
    TabsTrigger,
    TabsContent,
} from '@wedevs/plugin-ui';

import GeneralTab from './GeneralTab';
import TableTab from './TableTab';
import LocationTab from './LocationTab';
import type { TabKey } from '../types/analytics';

const VALID_TABS: TabKey[] = [
    'general',
    'pages',
    'geographic',
    'system',
    'promotions',
    'keyword',
];

const tabs: { value: TabKey; label: string }[] = [
    {
        value: 'general',
        label: __( 'General', 'dokan' ),
    },
    {
        value: 'pages',
        label: __( 'Top Pages', 'dokan' ),
    },
    {
        value: 'geographic',
        label: __( 'Location', 'dokan' ),
    },
    {
        value: 'system',
        label: __( 'System', 'dokan' ),
    },
    {
        value: 'promotions',
        label: __( 'Promotions', 'dokan' ),
    },
    {
        value: 'keyword',
        label: __( 'Keyword', 'dokan' ),
    },
];

interface AnalyticsDashboardProps {
    navigate?: ( path: string | object, options?: object ) => void;
    location?: { search: string };
}

export default function AnalyticsDashboard( {
    navigate,
    location,
}: AnalyticsDashboardProps ) {
    // Read initial tab from URL query params.
    const getTabFromUrl = useCallback( (): TabKey => {
        const searchStr = location?.search || window.location.search;
        const params = new URLSearchParams( searchStr );
        const tab = params.get( 'tab' ) as TabKey | null;

        return tab && VALID_TABS.includes( tab ) ? tab : 'general';
    }, [ location?.search ] );

    const [ activeTab, setActiveTab ] = useState< TabKey >( getTabFromUrl );

    // Sync active tab when URL changes externally.
    useEffect( () => {
        setActiveTab( getTabFromUrl() );
    }, [ getTabFromUrl ] );

    const onTabChange = useCallback(
        ( tabValue: string ) => {
            setActiveTab( tabValue as TabKey );

            if ( navigate ) {
                navigate( `?tab=${ tabValue }` );
            }
        },
        [ navigate ]
    );

    const renderContent = ( tabKey: TabKey ) => {
        switch ( tabKey ) {
            case 'general':
                return <GeneralTab />;
            case 'geographic':
                return <LocationTab />;
            default:
                return <TableTab tab={ tabKey } />;
        }
    };

    return (
        <div className="dokan-vendor-analytics-wrapper">
            <Tabs value={ activeTab } onValueChange={ onTabChange }>
                <TabsList variant="line">
                    { tabs.map( ( tab ) => (
                        <TabsTrigger
                            className="focus:outline-none"
                            key={ tab.value }
                            value={ tab.value }
                        >
                            { tab.label }
                        </TabsTrigger>
                    ) ) }
                </TabsList>

                { tabs.map( ( tab ) => (
                    <TabsContent key={ tab.value } value={ tab.value }>
                        <div className="pt-4">
                            { renderContent( tab.value ) }
                        </div>
                    </TabsContent>
                ) ) }
            </Tabs>
        </div>
    );
}
