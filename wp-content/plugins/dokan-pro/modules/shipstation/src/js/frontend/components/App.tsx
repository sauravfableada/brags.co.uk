import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Fill } from '@wordpress/components';
// @ts-ignore
// eslint-disable-next-line import/no-unresolved
import coreStore from '@dokan/stores/core';
import { DokanToaster } from '@getdokan/dokan-ui';
import { User } from '../definition/CurrentUserTypes';
import CredentialSettings from './CredentialSettings';
import OrderStatusSettings from './OrderStatusSettings';

const App = () => {
    const currentUser: User = useSelect( ( select ) => {
        return select( coreStore ).getCurrentUser();
    }, [] );

    const vendorId = currentUser?.id ?? 0;

    return (
        <div>
            <Fill name="dokan-layout-content-area-before">
                <p className="mb-6">
                    { __(
                        'ShipStation allows you to retrieve & manage orders, then print labels & packing slips with ease.',
                        'dokan'
                    ) }
                </p>
            </Fill>
            <CredentialSettings vendorId={ vendorId } />
            <OrderStatusSettings vendorId={ vendorId } />
            <DokanToaster />
        </div>
    );
};

export default App;
