import { register, createReduxStore } from '@wordpress/data';
import reducer from './reducer';
import actions from './actions';
import selectors from './selectors';

// Store name
export const STORE_NAME = 'dokan/vendor-staff';

// Create a Redux store
const staffStore = createReduxStore( STORE_NAME, {
    reducer,
    actions,
    selectors,
} );

// Register the store with WordPress Data
register( staffStore );

export default staffStore;
