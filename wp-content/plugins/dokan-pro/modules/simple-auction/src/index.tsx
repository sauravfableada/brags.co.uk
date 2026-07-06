import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';
import AuctionList from './AuctionList';
import AuctionActivity from './AuctionActivity';
import { addFilter } from '@wordpress/hooks';

domReady( () => {
    addFilter(
        'dokan-dashboard-routes',
        'dokan-pro/auction-routes',
        ( routes: any[] ) => {
            routes.push(
                {
                    id: 'dokan-auction',
                    title: __( 'Auctions', 'dokan' ),
                    element: <AuctionList />,
                    path: '/auction',
                    exact: true,
                    order: 185,
                    capabilities: [ 'dokan_view_auction_menu' ],
                },
                {
                    id: 'dokan-auction-activity',
                    title: __( 'Auctions Activity', 'dokan' ),
                    element: <AuctionActivity />,
                    path: '/auction-activity',
                    exact: true,
                    order: 185,
                    capabilities: [ 'dokan_view_auction_menu' ],
                }
            );

            return routes;
        }
    );
} );
