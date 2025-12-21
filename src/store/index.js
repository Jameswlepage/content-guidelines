/**
 * WordPress dependencies
 */
import { createReduxStore, register } from '@wordpress/data';

/**
 * Internal dependencies
 */
import reducer from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';
import * as resolvers from './resolvers';

/**
 * Store name.
 */
export const STORE_NAME = 'content-guidelines';

/**
 * Store configuration.
 */
const storeConfig = {
	reducer,
	actions,
	selectors,
	resolvers,
};

/**
 * Create and register the store.
 */
const store = createReduxStore( STORE_NAME, storeConfig );
register( store );

export default store;
