import { Fill, Slot } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { DokanTooltip } from '@dokan/components';
import { RawHTML } from '@wordpress/element';
import { truncate } from '@dokan/utilities';

function SubscriptionInfo() {
    return (
        <Fill name="dokan-admin-vendor-after-info-product-publishing">
            { ( { vendor } ) => {
                return (
                    <>
                        <div className="mb-4">
                            <h4 className="text-[#828282] text-xs font-normal mb-2">
                                { __( 'Subscription:', 'dokan' ) }
                            </h4>
                            <DokanTooltip
                                content={
                                    <RawHTML>
                                        { vendor?.current_subscription?.label
                                            ? vendor?.current_subscription
                                                  ?.label
                                            : __(
                                                  'No Subscription Added',
                                                  'dokan'
                                              ) }
                                    </RawHTML>
                                }
                            >
                                <div className="text-[#393939] text-sm font-normal">
                                    <RawHTML>
                                        { vendor?.current_subscription?.label
                                            ? truncate(
                                                  vendor?.current_subscription
                                                      ?.label,
                                                  30
                                              )
                                            : __(
                                                  'No Subscription Added',
                                                  'dokan'
                                              ) }
                                    </RawHTML>
                                </div>
                            </DokanTooltip>
                        </div>
                        <Slot
                            name={ `dokan-admin-vendor-after-info-subscription` }
                            fillProps={ { vendor } }
                        />
                    </>
                );
            } }
        </Fill>
    );
}

export default SubscriptionInfo;
