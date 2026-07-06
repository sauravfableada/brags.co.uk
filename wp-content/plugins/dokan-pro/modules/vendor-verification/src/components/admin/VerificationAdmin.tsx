import { useState } from '@wordpress/element';
import VerificationList from './VerificationList';

const VerificationAdmin = () => {
    const [ currentStatus, setCurrentStatus ] = useState( 'pending' );

    const handleStatusChange = ( status: string ) => {
        setCurrentStatus( status );
    };

    return (
        <div className="dokan-verification-admin">
            <div className="dokan-admin-content">
                <VerificationList
                    currentStatus={ currentStatus }
                    onStatusChange={ handleStatusChange }
                />
            </div>
        </div>
    );
};

export default VerificationAdmin;
