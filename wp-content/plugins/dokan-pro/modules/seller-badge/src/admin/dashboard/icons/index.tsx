import CountIcon, { IconProps } from './CountIcon';
import CompareIcon from './CompareIcon';
import DocumentIcon from './DocumentIcon';
import OrderIcon from './OrderIcon';
import PriceIcon from './PriceIcon';
import ReviewIcon from './ReviewIcon';
import SupportIcon from './SupportIcon';
import TimingIcon from './TimingIcon';
import TrendingIcon from './TrendingIcon';
import { BadgeEvent } from '../types';

export const iconsMap: Record< string, ( props?: IconProps ) => JSX.Element > =
    {
        count: ( props?: IconProps ) => <CountIcon { ...props } />,
        'icon-count': ( props?: IconProps ) => <CountIcon { ...props } />,
        compare: ( props?: IconProps ) => <CompareIcon { ...props } />,
        'icon-compare': ( props?: IconProps ) => <CompareIcon { ...props } />,
        document: ( props?: IconProps ) => <DocumentIcon { ...props } />,
        'icon-document': ( props?: IconProps ) => <DocumentIcon { ...props } />,
        order: ( props?: IconProps ) => <OrderIcon { ...props } />,
        'icon-order': ( props?: IconProps ) => <OrderIcon { ...props } />,
        price: ( props?: IconProps ) => <PriceIcon { ...props } />,
        'icon-price': ( props?: IconProps ) => <PriceIcon { ...props } />,
        review: ( props?: IconProps ) => <ReviewIcon { ...props } />,
        'icon-review': ( props?: IconProps ) => <ReviewIcon { ...props } />,
        support: ( props?: IconProps ) => <SupportIcon { ...props } />,
        'icon-support': ( props?: IconProps ) => <SupportIcon { ...props } />,
        timing: ( props?: IconProps ) => <TimingIcon { ...props } />,
        'icon-timing': ( props?: IconProps ) => <TimingIcon { ...props } />,
        trending: ( props?: IconProps ) => <TrendingIcon { ...props } />,
        'icon-trending': ( props?: IconProps ) => <TrendingIcon { ...props } />,
    };

export function getIconByKey( key?: string, props?: IconProps ): JSX.Element {
    const normalized = ( key || 'icon-count' ).trim();
    const renderer = iconsMap[ normalized ] || iconsMap[ 'icon-count' ];
    return renderer( props );
}

export function getInputGroupIconElement(
    event: BadgeEvent,
    which: 'data' | 'condition' = 'data',
    props?: IconProps
): JSX.Element {
    const key = event?.input_group_icon?.[ which ] || 'icon-count';
    return getIconByKey( key, props );
}
