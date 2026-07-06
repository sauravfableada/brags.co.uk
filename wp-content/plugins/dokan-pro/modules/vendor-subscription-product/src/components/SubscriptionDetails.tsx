import '../../../../src/definitions/window-types';
import SubscriptionBody from './SubscriptionBody';
import SubscriptionDetailsSkeleton from './SubscriptionDetailsSkeleton';
import { useSubscriptionDetails } from '../hooks/SubscriptionDetailsHooks';
import { DokanToaster } from '@getdokan/dokan-ui';

function SubscriptionDetails( props: any ) {
    const { params, navigate } = props;
    const { subscriptionId } = params;

    const subscription = useSubscriptionDetails( subscriptionId );

    if ( subscription?.error && subscription?.error?.status ) {
        props.navigate( `/404` );
    }

    const Subscription = () => {
        return subscription.isLoading ||
            ! subscription.orders ||
            ! subscription.statuses ||
            ! subscription.downloadableProducts ||
            ! subscription.subscription ? (
            <SubscriptionDetailsSkeleton />
        ) : (
            <SubscriptionBody
                subscription={ subscription }
                navigate={ navigate }
            />
        );
    };

    return (
        <div className="dokan-react-user-subscription">
            <DokanToaster />
            <Subscription />
        </div>
    );
}

export default SubscriptionDetails;
